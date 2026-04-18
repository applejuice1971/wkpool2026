#!/usr/bin/env python3
import argparse
import os
import re
import subprocess
from pathlib import Path
from typing import Dict, List, Optional, Tuple

WORKDIR = Path('/home/pi/.openclaw/workspace/sites/wkpool-backup/unpacked/wkpool2026')
ENV_PATH = WORKDIR / '.env'


def load_env(path: Path) -> None:
    if not path.exists():
        return
    for raw in path.read_text().splitlines():
        line = raw.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        os.environ.setdefault(key.strip(), value.strip().strip('"\''))


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


def fetch_pending_imports(limit: int) -> List[Dict[str, str]]:
    output = run_sql(
        "SELECT id, source_path, source_type FROM prediction_imports "
        "WHERE status IN ('received','failed') ORDER BY created_at ASC LIMIT %d;" % limit
    )
    rows = []
    for line in output.splitlines():
        parts = line.split('\t')
        if len(parts) >= 3:
            rows.append({'id': parts[0], 'source_path': parts[1], 'source_type': parts[2]})
    return rows


def fetch_matches() -> List[Dict[str, str]]:
    output = run_sql(
        "SELECT id, stage, home_team, away_team, DATE_FORMAT(match_date, '%Y-%m-%d %H:%i:%s') FROM matches ORDER BY match_date ASC, id ASC;"
    )
    rows = []
    for line in output.splitlines():
        parts = line.split('\t')
        if len(parts) >= 5:
            rows.append({
                'id': parts[0],
                'stage': parts[1],
                'home_team': parts[2],
                'away_team': parts[3],
                'match_date': parts[4],
            })
    return rows


def fetch_participant_id_by_name(name: str) -> Optional[str]:
    if not name:
        return None
    output = run_sql(
        f"SELECT id FROM participants WHERE LOWER(name) = LOWER('{sql_escape(name)}') LIMIT 1;"
    )
    return output.splitlines()[0].strip() if output else None


def extract_text_from_pdf(file_path: Path) -> str:
    result = subprocess.run(['pdftotext', str(file_path), '-'], check=True, capture_output=True, text=True)
    return result.stdout


def parse_name(text: str) -> Optional[str]:
    patterns = [
        r'Naam\s*[:\-]?\s*(.+)',
        r'Deelnemer\s*[:\-]?\s*(.+)',
    ]
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            candidate = match.group(1).strip().splitlines()[0].strip()
            return candidate[:120] if candidate else None
    return None


def normalize_token(value: str) -> str:
    return re.sub(r'[^a-z0-9]+', '', value.lower())


def score_candidates(line: str) -> Optional[Tuple[int, int]]:
    match = re.search(r'(\d{1,2})\s*[-:]\s*(\d{1,2})', line)
    if match:
        return int(match.group(1)), int(match.group(2))
    return None


def match_line_to_fixture(line: str, matches: List[Dict[str, str]]) -> Tuple[Optional[Dict[str, str]], Optional[Tuple[int, int]], float, str]:
    score = score_candidates(line)
    if not score:
        return None, None, 0.0, 'Geen scorepatroon gevonden'

    normalized_line = normalize_token(line)
    best_match = None
    best_score = 0.0
    for fixture in matches:
        home = normalize_token(fixture['home_team'])
        away = normalize_token(fixture['away_team'])
        hit_count = sum(token in normalized_line for token in [home, away] if token)
        confidence = hit_count / 2 if hit_count else 0.0
        if confidence > best_score:
            best_score = confidence
            best_match = fixture

    if best_match and best_score >= 0.5:
        note = 'Automatisch gematcht op teamnamen'
        return best_match, score, best_score * 100.0, note

    return None, score, best_score * 100.0, 'Wedstrijdmatch onzeker'


def clear_existing_rows(import_id: str) -> None:
    run_sql(f"DELETE FROM prediction_import_rows WHERE import_id = {int(import_id)};")


def insert_import_row(import_id: str, match_id: Optional[str], raw_label: str, score: Optional[Tuple[int, int]], confidence: float, status: str, notes: str) -> None:
    home = 'NULL' if score is None else str(score[0])
    away = 'NULL' if score is None else str(score[1])
    match_sql = 'NULL' if match_id is None else str(int(match_id))
    run_sql(
        "INSERT INTO prediction_import_rows (import_id, match_id, raw_label, predicted_home_score, predicted_away_score, confidence, status, notes) VALUES "
        f"({int(import_id)}, {match_sql}, '{sql_escape(raw_label[:255])}', {home}, {away}, {confidence:.2f}, '{sql_escape(status)}', '{sql_escape(notes[:255])}');"
    )


def update_import(import_id: str, status: str, extracted_name: Optional[str], extracted_text: str, notes: str, participant_id: Optional[str]) -> None:
    participant_sql = 'NULL' if participant_id is None else str(int(participant_id))
    name_sql = 'NULL' if not extracted_name else f"'{sql_escape(extracted_name[:120])}'"
    run_sql(
        "UPDATE prediction_imports SET "
        f"status = '{sql_escape(status)}', participant_id = {participant_sql}, extracted_name = {name_sql}, extracted_text = '{sql_escape(extracted_text)}', notes = '{sql_escape(notes)}' "
        f"WHERE id = {int(import_id)};"
    )


def main() -> None:
    parser = argparse.ArgumentParser(description='Parse imported WK pool prediction forms.')
    parser.add_argument('--limit', type=int, default=10)
    args = parser.parse_args()

    load_env(ENV_PATH)
    matches = fetch_matches()
    imports = fetch_pending_imports(args.limit)

    if not imports:
        print('Geen open imports om te verwerken.')
        return

    for item in imports:
        import_id = item['id']
        source_path = WORKDIR / item['source_path']
        source_type = item['source_type'].lower()

        clear_existing_rows(import_id)

        if source_type != 'pdf':
            update_import(import_id, 'review_needed', None, '', 'Automatische parsing werkt nu alleen voor PDF-bestanden. Handmatige controle nodig.', None)
            print(f'Import {import_id}: review nodig, type {source_type} wordt nog niet automatisch geparsed.')
            continue

        try:
            text = extract_text_from_pdf(source_path)
        except Exception as exc:
            update_import(import_id, 'failed', None, '', f'PDF extractie mislukt: {exc}', None)
            print(f'Import {import_id}: PDF extractie mislukt.')
            continue

        extracted_name = parse_name(text)
        participant_id = fetch_participant_id_by_name(extracted_name) if extracted_name else None

        parsed_count = 0
        review_needed = 0
        notes = []

        for line in text.splitlines():
            clean = ' '.join(line.split())
            if len(clean) < 6:
                continue
            match_info, score, confidence, note = match_line_to_fixture(clean, matches)
            if score is None:
                continue

            status = 'matched' if match_info is not None and confidence >= 50 else 'review_needed'
            if status == 'review_needed':
                review_needed += 1
            parsed_count += 1
            insert_import_row(import_id, match_info['id'] if match_info else None, clean, score, confidence, status, note)

        if parsed_count == 0:
            update_import(import_id, 'review_needed', extracted_name, text, 'Geen voorspellingen herkend in het PDF-bestand.', participant_id)
            print(f'Import {import_id}: geen voorspellingen herkend.')
            continue

        if not extracted_name:
            notes.append('Naam niet automatisch herkend')
        if participant_id is None and extracted_name:
            notes.append('Deelnemer nog niet gevonden in participants')
        if review_needed > 0:
            notes.append(f'{review_needed} rij(en) hebben handmatige controle nodig')

        final_status = 'parsed' if review_needed == 0 else 'review_needed'
        update_import(import_id, final_status, extracted_name, text, '; '.join(notes) or f'{parsed_count} rij(en) herkend', participant_id)
        print(f'Import {import_id}: {parsed_count} rij(en) verwerkt, status {final_status}.')


if __name__ == '__main__':
    main()
