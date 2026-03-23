# Async Mail & Queue

## Ziel

Diese Ausbaustufe ergänzt TaskHost um eine robuste asynchrone Infrastruktur für Einladungsmails und Mail-Reminder, ohne den synchronen Kern der REST-API unnötig zu verkomplizieren.

## Leitidee

Die REST-API verschickt E-Mails nicht direkt im Request. Stattdessen werden Mail-Nachrichten in einer Outbox-Tabelle gespeichert und als Queue-Jobs verarbeitet. Dadurch bleibt das API-Verhalten stabil, auch wenn ein Mail-Transport temporär gestört ist.

## Bausteine

### `mail_messages`

Enthält die zu versendenden Nachrichten inklusive Betreff, Text/HTML-Body, Status und Provider-Rückmeldung.

### `queue_jobs`

Enthält asynchrone Jobs mit Status, Versuchen, Backoff und optionalem Dedupe-Key.

### `task_reminders`

Reminder sind jetzt benutzerbezogen. Das ist wichtig, weil Erinnerungen in geteilten Listen nicht global an einer Aufgabe hängen dürfen, sondern einer konkreten Person zugeordnet sein müssen.

## Fluss für Einladungen

1. `POST /api/v1/lists/{id}/share`
2. Einladung wird in `list_invitations` gespeichert.
3. Eine Mail-Nachricht wird in `mail_messages` angelegt.
4. Ein `send_mail`-Job wird in `queue_jobs` eingestellt.
5. Der Worker sendet die E-Mail später aus.

## Fluss für Reminder

1. Benutzer legt Reminder mit `channel=email` oder `channel=both` an.
2. `php bin/worker.php reminders:enqueue` sammelt fällige Reminder ein.
3. Für jeden Reminder wird eine Mail-Nachricht erzeugt.
4. Ein `send_mail`-Job wird angelegt.
5. Der Worker sendet die Nachricht und markiert den Reminder als versendet.

## Mail-Transporte

### `file`

Für lokale Entwicklung. Nachrichten werden als `.eml` in `storage/mail/` geschrieben.

### `native`

Verwendet PHP `mail()` und ist für einfache Server-Setups gedacht.

### `null`

Für Tests oder Trockenläufe. Nachrichten gelten als erfolgreich verarbeitet, werden aber nicht real versendet.

## Retry-Verhalten

Fehlgeschlagene Jobs gehen mit Backoff zurück in die Queue. Nach Erreichen von `max_attempts` werden sie als `failed` markiert. Fehlertext wird sowohl im Job als auch bei der Mail-Nachricht protokolliert.

## Bewusste Grenzen

- Noch kein SMTP-Client mit Provider-spezifischer Konfiguration
- Noch keine Push-Worker
- Noch kein generischer Audit-Feed
- Noch keine periodische Cron-Definition im Projekt selbst; Ausführung erfolgt bewusst über CLI/Server-Scheduler
