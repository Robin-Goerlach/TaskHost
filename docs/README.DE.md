# TaskHost Dokumentation (DE)

TaskHost ist eine Aufgabenverwaltungsanwendung mit getrenntem **PHP-Backend** in `api/` und **JavaScript-Frontend** in `app/`.

## Enthalten
- REST API für Auth, Listen, Aufgaben, Unteraufgaben, Notizen, Kommentare, Reminder und Anhänge
- Freigaben und Einladungen
- asynchrone Mail-Outbox und Queue
- Frontend mit Smart Views, Aufgaben-Details und Share-/Reminder-Oberflächen
- Installer in `api/bin/install.php`
- PHPUnit-Unit-Tests in `api/tests/`

## Schnelleinstieg

```bash
cd api
composer install
cp .env.example .env
php bin/install.php --migrate
php -S 127.0.0.1:8080 -t public
```

In einem zweiten Terminal:

```bash
cd app
python3 -m http.server 4173
```

Danach:
- Frontend: `http://127.0.0.1:4173`
- API: `http://127.0.0.1:8080/api/v1`

## Tests

```bash
cd api
composer test
```

Die Root-README enthält die ausführlichere Projektübersicht.
