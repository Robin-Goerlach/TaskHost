# TaskHost PHP MVC

TaskHost ist eine kleine, direkt nutzbare Aufgabenverwaltung im Stil eines klassischen Wunderlist-Workflows.
Die Anwendung wurde bewusst **ohne Framework** gebaut, damit du die Architektur gut nachvollziehen und später leicht erweitern kannst.

## Funktionen

- Benutzerregistrierung und Login
- Mehrere Aufgabenlisten pro Benutzer
- Aufgaben anlegen, abhaken, bearbeiten und löschen
- Priorität, Fälligkeitsdatum und Notizen
- Dashboard mit Übersicht
- MVC-Struktur mit Routing, Controllern, Repositories und Views
- SQLite-Datenbank für schnellen Einstieg
- CSRF-Schutz, Passwort-Hashing und Eigentümer-Prüfungen

## Voraussetzungen

- PHP **8.1** oder neuer

Weitere PHP-Datenbanktreiber sind **nicht notwendig**, weil die Anwendung ihre Daten standardmäßig in einer JSON-Datei speichert.

## Schnellstart

### 1. Projekt entpacken / öffnen

```bash
cd taskhost-php-mvc
```

### 2. Lokalen Entwicklungsserver starten

```bash
php -S 127.0.0.1:8000 -t public public/router.php
```

Danach im Browser öffnen:

```text
http://127.0.0.1:8000
```

## Wichtige Hinweise

- Beim ersten Start erzeugt die Anwendung automatisch die Datendatei unter `var/data/storage.json`.
- Das Verzeichnis `var/data` muss für den Webserver beschreibbar sein.
- Für Apache ist eine `.htaccess` im `public`-Verzeichnis enthalten.

## Projektstruktur

```text
app/
  Bootstrap.php          # Verdrahtung der Anwendung
  Controllers/           # HTTP-Logik
  Core/                  # Infrastruktur: Router, DB, Views, Security
  Repositories/          # Datenbankzugriff
  Services/              # Fachlogik
  Support/               # Hilfsfunktionen
  Views/                 # PHP-Templates
public/
  index.php              # Einstiegspunkt
  router.php             # Router für PHP Built-in Server
  assets/css/app.css     # Styling
var/data/
  storage.json           # Wird automatisch erzeugt
```

## Standard-Zugänge

Es werden **keine festen Standardzugänge** mitgeliefert.
Bitte registriere beim ersten Start einfach deinen ersten Benutzer.

## Beispiel-Workflow

1. Registrieren
2. Anmelden
3. Neue Liste anlegen, z. B. `Privat` oder `Arbeit`
4. Liste öffnen
5. Aufgaben mit Priorität und Termin anlegen
6. Aufgaben abhaken, bearbeiten oder löschen

## Erweiterungsideen

- Tags
- Erinnerungen per Mail
- Teilen von Listen mit anderen Benutzern
- JSON-API zusätzlich zur Web-Oberfläche
- Drag & Drop Sortierung
- Archiv / Papierkorb

## Sicherheit / Architektur

Die Anwendung enthält bereits einige wichtige Grundlagen:

- Passwortspeicherung nur gehasht (`password_hash`)
- Prepared Statements via PDO
- CSRF-Token für schreibende Formulare
- Benutzer dürfen nur ihre eigenen Listen und Aufgaben sehen/bearbeiten
- HTML-Ausgabe wird escaped

## Apache Beispiel

Der DocumentRoot sollte auf `public/` zeigen.

## Lizenz

Du kannst das Projekt als Ausgangsbasis für deine eigene Weiterentwicklung verwenden.
