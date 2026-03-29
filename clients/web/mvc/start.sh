#!/usr/bin/env bash
set -euo pipefail

# Einfacher Startscript für die lokale Entwicklung.
# Der eingebaute PHP-Server nutzt public/ als Webroot.

HOST="127.0.0.1"
PORT="8000"

if [[ "${1:-}" != "" ]]; then
  PORT="$1"
fi

echo "TaskHost startet auf http://${HOST}:${PORT}"
php -S "${HOST}:${PORT}" -t public public/router.php
