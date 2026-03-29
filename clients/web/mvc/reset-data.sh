#!/usr/bin/env bash
set -euo pipefail

# Setzt die lokale Datendatei zurück.
# Vorsicht: Alle Benutzer, Listen und Aufgaben werden gelöscht.

rm -f var/data/storage.json
echo "Datendatei wurde gelöscht. Beim nächsten Start wird sie neu angelegt."
