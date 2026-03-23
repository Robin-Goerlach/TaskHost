# TaskHost API

TaskHost API is a structured PHP REST backend for a Wunderlist-inspired task-management application.
The backend is designed to remain readable, extensible, and suitable as the server-side core for a browser frontend, additional clients, or later service integrations.

## Included Features

- registration, login, logout, bearer-token authentication
- profile endpoint (`/me`)
- folders for organizing lists
- list creation, update, deletion, archiving
- list sharing
  - direct sharing with existing users
  - invitations for email addresses without an account yet
  - accepting open invitations
  - resending open invitations
- list-level roles (`owner`, `editor`, `viewer`)
- tasks
  - create, update, delete
  - move between lists
  - assign to users
  - star / unstar
  - due dates
  - recurrence (`day`, `week`, `month`, `year`)
  - complete / restore
- subtasks
- task notes
- task comments
- user-specific reminders
  - `in_app`, `email`, `both`
  - mail reminders delivered through queue/worker processing
- attachments stored on the local filesystem
- smart views
  - Today
  - Planned
  - Important
  - Assigned to me
  - Completed
- search across accessible tasks
- asynchronous infrastructure
  - mail outbox
  - queue jobs
  - worker CLI
  - retry with backoff
  - file mailer for safe local development
  - native mailer via PHP `mail()` for simple server setups
- installer/bootstrap helper
- first PHPUnit unit tests for selected core classes

## Intentional Gaps

This backend already covers the functional core plus mail queue and reminder delivery. For full product maturity, some topics remain separate future steps:

- push notifications
- offline sync conflict resolution
- activity feed / audit trail
- export/import
- OAuth / SSO
- rate limiting
- broader automated test coverage

## Project Structure

```text
public/
  index.php
bin/
  install.php
  migrate.php
  seed.php
  worker.php
  doctor.php
migrations/
  010_sqlite_full_schema_and_views.sql
  010_mysql_full_schema_and_views.sql
  020_sqlite_async_mail_and_queue.sql
  020_mysql_async_mail_and_queue.sql
tests/
  bootstrap.php
  Unit/
src/
  Bootstrap.php
  Application.php
  Controller/
  Http/
  Infrastructure/
  Repository/
  Security/
  Service/
storage/
  uploads/
  mail/
```

## Getting Started

### 1. Install dependencies

```bash
composer install
```

### 2. Configuration

```bash
cp .env.example .env
```

The default `.env.example` uses a local SQLite database under `storage/taskhost.sqlite` and keeps `MAIL_TRANSPORT=file` for a safe first start.

### 3. Installer

```bash
php bin/install.php --migrate
```

Optional demo data:

```bash
php bin/install.php --migrate --seed
```

Useful installer options:

```bash
php bin/install.php --help
php bin/install.php --force-copy-env
php bin/install.php --skip-doctor
```

### 4. Start the development server

```bash
php -S 127.0.0.1:8080 -t public
```

### 5. Process reminders and mail queue

One-off processing:

```bash
php bin/worker.php reminders:enqueue --limit=100
php bin/worker.php queue:drain --queue=mail --limit=50
```

Long-running worker:

```bash
php bin/worker.php queue:work --queue=mail --limit=50 --sleep=10
```

## Testing

The repository now includes a first PHPUnit unit-test suite.

Run all unit tests:

```bash
composer test
```

Run PHPUnit directly:

```bash
vendor/bin/phpunit --configuration phpunit.xml
```

Current focus:
- `PasswordHasher`
- `TokenService`
- `DateTimeHelper`
- `MailTemplateService`

These tests intentionally target stable core behavior first, before broader repository and API flow tests are added.

## Demo Access after `seed.php`

- email: `alice@example.com`
- password: `ChangeMe123!`

## Important Endpoints

### Auth

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`

### Folders

- `GET /api/v1/folders`
- `POST /api/v1/folders`
- `PATCH /api/v1/folders/{id}`
- `DELETE /api/v1/folders/{id}`

### Lists

- `GET /api/v1/lists`
- `POST /api/v1/lists`
- `GET /api/v1/lists/{id}`
- `PATCH /api/v1/lists/{id}`
- `DELETE /api/v1/lists/{id}`
- `GET /api/v1/lists/{id}/members`
- `POST /api/v1/lists/{id}/share`
- `GET /api/v1/lists/{id}/invitations`
- `POST /api/v1/lists/{id}/invitations/{invitationId}/resend`
- `DELETE /api/v1/lists/{id}/members/{userId}`
- `POST /api/v1/invitations/{token}/accept`

### Tasks

- `GET /api/v1/lists/{id}/tasks`
- `POST /api/v1/lists/{id}/tasks`
- `GET /api/v1/tasks/{id}`
- `PATCH /api/v1/tasks/{id}`
- `DELETE /api/v1/tasks/{id}`
- `POST /api/v1/tasks/{id}/complete`
- `POST /api/v1/tasks/{id}/restore`

### Subtasks

- `GET /api/v1/tasks/{id}/subtasks`
- `POST /api/v1/tasks/{id}/subtasks`
- `PATCH /api/v1/subtasks/{id}`
- `DELETE /api/v1/subtasks/{id}`

### Notes

- `GET /api/v1/tasks/{id}/note`
- `PUT /api/v1/tasks/{id}/note`

### Comments

- `GET /api/v1/tasks/{id}/comments`
- `POST /api/v1/tasks/{id}/comments`
- `DELETE /api/v1/comments/{id}`

### Reminders

- `GET /api/v1/tasks/{id}/reminders`
- `POST /api/v1/tasks/{id}/reminders`
- `PATCH /api/v1/reminders/{id}`
- `DELETE /api/v1/reminders/{id}`

### Attachments

- `GET /api/v1/tasks/{id}/attachments`
- `POST /api/v1/tasks/{id}/attachments`
- `GET /api/v1/attachments/{id}/download`
- `DELETE /api/v1/attachments/{id}`

### Smart Views and Search

- `GET /api/v1/views/today`
- `GET /api/v1/views/planned`
- `GET /api/v1/views/starred`
- `GET /api/v1/views/assigned`
- `GET /api/v1/views/completed`
- `GET /api/v1/search?q=report`
