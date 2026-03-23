# TaskHost Documentation (EN)

TaskHost is a task-management application with a separate **PHP backend** in `api/` and **JavaScript frontend** in `app/`.

## Included
- REST API for auth, lists, tasks, subtasks, notes, comments, reminders, and attachments
- sharing and invitations
- async mail outbox and queue
- frontend with smart views, task details, and share/reminder UI
- installer in `api/bin/install.php`
- PHPUnit unit tests in `api/tests/`

## Quick Start

```bash
cd api
composer install
cp .env.example .env
php bin/install.php --migrate
php -S 127.0.0.1:8080 -t public
```

In a second terminal:

```bash
cd app
python3 -m http.server 4173
```

Then open:
- Frontend: `http://127.0.0.1:4173`
- API: `http://127.0.0.1:8080/api/v1`

## Tests

```bash
cd api
composer test
```

See the root README for the fuller project overview.
