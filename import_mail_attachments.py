#!/usr/bin/env python3
import argparse
import email
import imaplib
import os
import re
from email.header import decode_header
from pathlib import Path
from typing import Optional

import subprocess

WORKDIR = Path('/home/pi/.openclaw/workspace/sites/wkpool-backup/unpacked/wkpool2026')
ENV_PATH = WORKDIR / '.env'
MAIL_ENV_PATH = Path('/home/pi/.openclaw/workspace/secrets/strato-mail.env')
ALLOWED_EXTENSIONS = {'.pdf', '.jpg', '.jpeg', '.png'}


def load_env(path: Path) -> None:
    if not path.exists():
        return
    for raw in path.read_text().splitlines():
        line = raw.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        os.environ.setdefault(key.strip(), value.strip().strip('"\''))


def decode_mime_header(value: Optional[str]) -> str:
    if not value:
        return ''
    parts = decode_header(value)
    decoded = []
    for text, encoding in parts:
        if isinstance(text, bytes):
            decoded.append(text.decode(encoding or 'utf-8', errors='replace'))
        else:
            decoded.append(text)
    return ''.join(decoded)


def slugify(name: str) -> str:
    name = re.sub(r'[^a-zA-Z0-9._-]+', '-', name.strip())
    name = re.sub(r'-{2,}', '-', name).strip('-')
    return name or 'attachment'


def sql_escape(value: str) -> str:
    return value.replace('\\', '\\\\').replace("'", "\\'")


def run_sql(sql: str) -> str:
    cmd = [
        'mariadb',
        '--protocol=TCP',
        '-h', os.environ.get('DB_HOST', '127.0.0.1'),
        '-P', os.environ.get('DB_PORT', '3306'),
        '-u', os.environ.get('DB_USERNAME', ''),
        f"-p{os.environ.get('DB_PASSWORD', '')}",
        os.environ.get('DB_DATABASE', ''),
        '-N',
        '-B',
        '-e', sql,
    ]
    result = subprocess.run(cmd, check=True, capture_output=True, text=True)
    return result.stdout.strip()


def ensure_schema() -> None:
    run_sql("""
        CREATE TABLE IF NOT EXISTS prediction_imports (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            participant_id INT UNSIGNED NULL,
            source_filename VARCHAR(255) NOT NULL,
            source_path VARCHAR(255) NOT NULL,
            source_type ENUM('pdf','jpg','jpeg','png') NOT NULL,
            status ENUM('received','parsed','imported','review_needed','failed') NOT NULL DEFAULT 'received',
            extracted_name VARCHAR(120) NULL,
            extracted_text MEDIUMTEXT NULL,
            notes TEXT NULL,
            imported_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_prediction_imports_status (status),
            KEY idx_prediction_imports_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)


def save_import(filename: str, file_path: Path, ext: str, note: str) -> bool:
    rel_path = str(file_path.relative_to(WORKDIR))
    existing = run_sql(f"SELECT id FROM prediction_imports WHERE source_path = '{sql_escape(rel_path)}' LIMIT 1;")
    if existing:
        return False

    run_sql(
        "INSERT INTO prediction_imports (source_filename, source_path, source_type, status, notes) "
        f"VALUES ('{sql_escape(filename)}', '{sql_escape(rel_path)}', '{sql_escape(ext.lstrip('.').lower())}', 'received', '{sql_escape(note)}');"
    )
    return True


def main() -> None:
    parser = argparse.ArgumentParser(description='Import prediction form attachments from Strato IMAP into WK pool.')
    parser.add_argument('--mailbox', default='INBOX')
    parser.add_argument('--subject-filter', default='')
    parser.add_argument('--from-filter', default='')
    parser.add_argument('--limit', type=int, default=10)
    args = parser.parse_args()

    load_env(MAIL_ENV_PATH)
    load_env(ENV_PATH)

    uploads_dir = WORKDIR / 'uploads' / 'prediction-imports'
    uploads_dir.mkdir(parents=True, exist_ok=True)

    imap_host = os.environ['STRATO_IMAP_HOST']
    imap_port = int(os.environ.get('STRATO_IMAP_PORT', '993'))
    mail_user = os.environ['STRATO_MAIL_USER']
    mail_password = os.environ['STRATO_MAIL_PASSWORD']

    ensure_schema()

    mail = imaplib.IMAP4_SSL(imap_host, imap_port)
    mail.login(mail_user, mail_password)
    mail.select(args.mailbox)

    criteria = ['ALL']
    if args.subject_filter:
        criteria.extend(['SUBJECT', f'"{args.subject_filter}"'])
    if args.from_filter:
        criteria.extend(['FROM', f'"{args.from_filter}"'])

    typ, data = mail.search(None, *criteria)
    if typ != 'OK':
        raise SystemExit('IMAP search failed')

    ids = data[0].split()[-args.limit:]
    imported_count = 0

    for msg_id in reversed(ids):
        typ, msg_data = mail.fetch(msg_id, '(RFC822)')
        if typ != 'OK' or not msg_data or not msg_data[0]:
            continue

        msg = email.message_from_bytes(msg_data[0][1])
        subject = decode_mime_header(msg.get('Subject'))
        sender = decode_mime_header(msg.get('From'))
        date = decode_mime_header(msg.get('Date'))

        for part in msg.walk():
            filename = decode_mime_header(part.get_filename())
            if not filename:
                continue
            ext = Path(filename).suffix.lower()
            if ext not in ALLOWED_EXTENSIONS:
                continue

            payload = part.get_payload(decode=True)
            if not payload:
                continue

            safe_name = slugify(Path(filename).stem)
            target_name = f"{msg_id.decode()}-{safe_name}{ext}"
            target_path = uploads_dir / target_name
            target_path.write_bytes(payload)

            note = f'Ontvangen via mail | onderwerp: {subject or "(geen onderwerp)"} | afzender: {sender or "onbekend"} | datum: {date or "onbekend"}'
            if save_import(filename, target_path, ext, note):
                imported_count += 1
                print(f'Imported: {target_path.name}')
            else:
                print(f'Skipped existing: {target_path.name}')

    mail.close()
    mail.logout()
    print(f'Done, {imported_count} new attachment(s) opgeslagen.')


if __name__ == '__main__':
    main()
