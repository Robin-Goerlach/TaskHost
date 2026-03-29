#!/usr/bin/env bash
set -euo pipefail

# Leert die MySQL-Tabellen der Anwendung.
# Die Zugangsdaten werden aus der .env-Datei gelesen.
# Vorsicht: Alle Benutzer, Listen und Aufgaben werden gelöscht.

if [[ ! -f .env ]]; then
  echo ".env wurde nicht gefunden. Bitte zuerst .env anlegen."
  exit 1
fi

set -a
source ./.env
set +a

mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" <<'SQL'
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE tasks;
TRUNCATE TABLE task_lists;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;
SQL

echo "MySQL-Daten wurden gelöscht."
