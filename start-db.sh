#!/bin/bash
set -euo pipefail

ROOT="/home/pi/.openclaw/workspace/sites/wkpool-backup/unpacked/wkpool2026"
RUNTIME="$ROOT/.runtime"
DBDIR="$RUNTIME/db"
SOCK="$RUNTIME/mysql.sock"
PIDFILE="$RUNTIME/mysql.pid"
DBLOG="$RUNTIME/mariadb.log"
DBPID="$RUNTIME/mysql-server.pid"

mkdir -p "$RUNTIME"

if [ ! -d "$DBDIR/mysql" ]; then
  mariadb-install-db --datadir="$DBDIR" --auth-root-authentication-method=normal >/tmp/wkpool-db-init.log 2>&1
fi

if [ ! -f "$DBPID" ] || ! kill -0 "$(cat "$DBPID" 2>/dev/null)" 2>/dev/null; then
  nohup /usr/sbin/mariadbd \
    --datadir="$DBDIR" \
    --socket="$SOCK" \
    --port=3308 \
    --bind-address=127.0.0.1 \
    --pid-file="$PIDFILE" \
    --skip-networking=0 \
    --user=pi \
    >"$DBLOG" 2>&1 &
  echo $! > "$DBPID"
fi

for _ in $(seq 1 30); do
  if mysqladmin --protocol=TCP --host=127.0.0.1 --port=3308 --user=root ping >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

mysql --protocol=TCP --host=127.0.0.1 --port=3308 --user=root <<'SQL'
CREATE DATABASE IF NOT EXISTS wkpool2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'wkpool'@'127.0.0.1' IDENTIFIED BY 'NewPWStrong';
ALTER USER 'wkpool'@'127.0.0.1' IDENTIFIED BY 'NewPWStrong';
GRANT ALL PRIVILEGES ON wkpool2026.* TO 'wkpool'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

if ! mysql --protocol=TCP --host=127.0.0.1 --port=3308 --user=root -NBe "USE wkpool2026; SHOW TABLES LIKE 'participants';" 2>/dev/null | grep -q participants; then
  php "$ROOT/bootstrap_db.php"
fi
