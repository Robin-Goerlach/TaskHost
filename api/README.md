# TaskHost API

TaskHost API ist ein sauber strukturiertes REST-Backend in PHP für eine Wunderlist-inspirierte Aufgabenverwaltung.
Der Schwerpunkt liegt auf einer tragfähigen Server-Architektur, die Du später mit Web-Frontend, Mobile-App oder weiteren Services verbinden kannst.

## Enthaltene Funktionen

- Registrierung, Login, Logout, Bearer-Token-Authentifizierung
- Persönliches Profil (`/me`)
- Ordner für Listen
- Listen anlegen, ändern, löschen, archivieren
- Listen teilen
  - direkte Freigabe an bestehende Benutzer
  - Einladungen für E-Mail-Adressen, die noch kein Konto haben
  - Annahme offener Einladungen
  - erneuter Versand offener Einladungen
- Rollen auf Listenebene (`owner`, `editor`, `viewer`)
- Aufgaben
  - anlegen, ändern, löschen
  - verschieben
  - zuweisen
  - priorisieren per Stern
  - Fälligkeit
  - Wiederholung (`day`, `week`, `month`, `year`)
  - erledigen / wiederherstellen
- Unteraufgaben
- Notizen pro Aufgabe
- Kommentare pro Aufgabe
- Erinnerungen pro Benutzer
  - `in_app`, `email`, `both`
  - Mail-Reminder werden über Queue/Worker ausgeliefert
- Anhänge mit Upload auf lokales Dateisystem
- Smarte Ansichten
  - Heute
  - Geplant
  - Wichtig
  - Mir zugewiesen
  - Erledigt
- Suche über erreichbare Aufgaben
- Asynchrone Infrastruktur
  - Mail-Outbox
  - Queue-Jobs
  - Worker-CLI
  - Retry mit Backoff
  - File-Mailer für sichere lokale Entwicklung
  - Native-Mailer via PHP `mail()` für einfache Server-Setups

## Was bewusst noch nicht enthalten ist

Dieses Backend deckt jetzt neben dem fachlichen Kern auch Mail-Queue und Reminder-Auslieferung ab. Für vollständige 1:1-Parität mit dem historischen Wunderlist-Produkt fehlen weiterhin einige Themen, die besser als eigener Ausbauschritt kommen sollten:

- Push-Benachrichtigungen
- Konfliktauflösung für Offline-Sync
- Activity Feed / Audit Trail pro Änderung
- Export/Import im Wunderlist- oder Microsoft-To-Do-Format
- OAuth / SSO
- Rate Limiting

## Projektstruktur

```text
public/
  index.php
bin/
  migrate.php
  seed.php
  worker.php
  doctor.php
migrations/
  010_sqlite_full_schema_and_views.sql
  010_mysql_full_schema_and_views.sql
  020_sqlite_async_mail_and_queue.sql
  020_mysql_async_mail_and_queue.sql
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

## Starten

### 1. Konfiguration

```bash
cp .env.example .env
```

Für einen sicheren ersten lokalen Start bleibt `MAIL_TRANSPORT=file`. Dann werden E-Mails als `.eml`-Dateien unter `storage/mail/` abgelegt, statt sie sofort nach außen zu versenden.

### 2. Umfeld prüfen

```bash
php bin/doctor.php
```

### 3. Datenbank initialisieren

```bash
php bin/migrate.php
php bin/seed.php
```

Hinweis: `php bin/migrate.php` führt das aktuelle Full-Schema aus. Das Legacy-Upgrade-Skript `020_*` ist nur für ältere Datenbanken gedacht und kann bei Bedarf manuell mit `--legacy-upgrade` ergänzt werden.

### 4. Entwicklungsserver starten

```bash
php -S 127.0.0.1:8080 -t public
```

### 5. Reminder-Queue und Mail-Queue verarbeiten

Einmalige Ausführung:

```bash
php bin/worker.php reminders:enqueue --limit=100
php bin/worker.php queue:drain --queue=mail --limit=50
```

Dauerbetrieb als Worker:

```bash
php bin/worker.php queue:work --queue=mail --limit=50 --sleep=10
```

## Demo-Zugang nach `seed.php`

- E-Mail: `alice@example.com`
- Passwort: `ChangeMe123!`

## Wichtige Endpunkte

### Auth

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`

### Ordner

- `GET /api/v1/folders`
- `POST /api/v1/folders`
- `PATCH /api/v1/folders/{id}`
- `DELETE /api/v1/folders/{id}`

### Listen

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

### Aufgaben

- `GET /api/v1/lists/{id}/tasks`
- `POST /api/v1/lists/{id}/tasks`
- `GET /api/v1/tasks/{id}`
- `PATCH /api/v1/tasks/{id}`
- `DELETE /api/v1/tasks/{id}`
- `POST /api/v1/tasks/{id}/complete`
- `POST /api/v1/tasks/{id}/restore`

### Unteraufgaben

- `GET /api/v1/tasks/{id}/subtasks`
- `POST /api/v1/tasks/{id}/subtasks`
- `PATCH /api/v1/subtasks/{id}`
- `DELETE /api/v1/subtasks/{id}`

### Notizen

- `GET /api/v1/tasks/{id}/note`
- `PUT /api/v1/tasks/{id}/note`

### Kommentare

- `GET /api/v1/tasks/{id}/comments`
- `POST /api/v1/tasks/{id}/comments`
- `DELETE /api/v1/comments/{id}`

### Erinnerungen

- `GET /api/v1/tasks/{id}/reminders`
- `POST /api/v1/tasks/{id}/reminders`
- `PATCH /api/v1/reminders/{id}`
- `DELETE /api/v1/reminders/{id}`

### Anhänge

- `GET /api/v1/tasks/{id}/attachments`
- `POST /api/v1/tasks/{id}/attachments`
- `GET /api/v1/attachments/{id}/download`
- `DELETE /api/v1/attachments/{id}`

### Smarte Ansichten & Suche

- `GET /api/v1/views/today`
- `GET /api/v1/views/planned`
- `GET /api/v1/views/starred`
- `GET /api/v1/views/assigned`
- `GET /api/v1/views/completed`
- `GET /api/v1/search?q=bericht`

## Beispiele

### Login

```bash
curl -X POST http://127.0.0.1:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"ChangeMe123!"}'
```

### Liste teilen und Einladungsmail in die Queue stellen

```bash
curl -X POST http://127.0.0.1:8080/api/v1/lists/1/share \
  -H "Authorization: Bearer DEIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "email":"bob@example.com",
    "role":"editor",
    "notify":true
  }'
```

### Mail-Reminder anlegen

```bash
curl -X POST http://127.0.0.1:8080/api/v1/tasks/1/reminders \
  -H "Authorization: Bearer DEIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "remind_at":"2026-03-24T08:30:00+01:00",
    "channel":"email"
  }'
```

### Fällige Reminder einsammeln und Mail-Jobs abarbeiten

```bash
php bin/worker.php reminders:enqueue --limit=100
php bin/worker.php queue:drain --queue=mail --limit=50
```
