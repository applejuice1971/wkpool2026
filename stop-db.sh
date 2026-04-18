#!/bin/bash
set -euo pipefail

ROOT="/home/pi/.openclaw/workspace/sites/wkpool-backup/unpacked/wkpool2026"
PIDFILE="$ROOT/.runtime/mysql.pid"
DBPID="$ROOT/.runtime/mysql-server.pid"

for f in "$PIDFILE" "$DBPID"; do
  if [ -f "$f" ]; then
    kill "$(cat "$f")" 2>/dev/null || true
  fi
done
