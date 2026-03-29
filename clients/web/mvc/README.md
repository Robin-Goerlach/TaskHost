# TaskHost PHP MVC MySQL

TaskHost ist eine kleine, direkt nutzbare Aufgabenverwaltung im Stil eines klassischen Wunderlist-Workflows.
Diese Variante nutzt **MySQL** und bleibt trotzdem bewusst **framework-frei**, damit du die Architektur gut verstehen und erweitern kannst.

## Wichtiger Hinweis zur Konfiguration

Du fragtest nach einer Umschaltung in `.enc`.
In PHP-Projekten wird dafür normalerweise **`.env`** verwendet, nicht `.enc`.

Die MySQL-Zugangsdaten trägst du also in eine Datei namens **`.env`** ein.

## Funktionen

- Benutzerregistrierung und Login
- Mehrere Aufgabenlisten pro Benutzer
- Aufgaben anlegen, abhaken, bearbeiten und löschen
- Priorität, Fälligkeitsdatum und Notizen
- Dashboard mit Übersicht
- MVC-Struktur mit Routing, Controllern, Repositories und Views
- MySQL über PDO
- CSRF-Schutz, Passwort-Hashing und Eigentümer-Prüfungen

## Voraussetzungen

- PHP **8.1** oder neuer
- PHP-Erweiterung **pdo_mysql**
- MySQL oder MariaDB

## 1. `.env` anlegen

Kopiere zuerst die Beispieldatei:

```bash
cp .env.example .env
```

Passe danach die Werte in `.env` an, zum Beispiel:

```env
APP_NAME=TaskHost
APP_ENV=development
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskhost
DB_USERNAME=taskhost_user
DB_PASSWORD=dein_passwort
```

## 2. Datenbank anlegen

Beispiel in MySQL:

```sql
CREATE DATABASE taskhost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'taskhost_user'@'localhost' IDENTIFIED BY 'dein_passwort';
GRANT ALL PRIVILEGES ON taskhost.* TO 'taskhost_user'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Tabellen anlegen

Die Anwendung bringt ein SQL-Schema und ein kleines Migrationsskript mit:

```bash
php migrate.php
```

Alternativ kannst du auch `database/schema.sql` direkt in MySQL importieren.

## 4. Entwicklungsserver starten

```bash
./start.sh
```

oder direkt:

```bash
php -S 127.0.0.1:8000 -t public public/router.php
```

Danach im Browser öffnen:

```text
http://127.0.0.1:8000
```

## Projektstruktur

```text
app/
  Bootstrap.php          # Verdrahtung der Anwendung
  Config/Env.php         # .env-Loader
  Controllers/           # HTTP-Logik
  Core/                  # Infrastruktur: Router, Views, Security
  Database/Connection.php# PDO-Aufbau für MySQL
  Repositories/          # Datenbankzugriff über PDO
  Services/              # Fachlogik
  Support/               # Hilfsfunktionen
  Views/                 # PHP-Templates
public/
  index.php              # Einstiegspunkt
  router.php             # Router für PHP Built-in Server
database/
  schema.sql             # Tabellenstruktur
migrate.php              # legt Tabellen an
.env.example             # Beispiel-Konfiguration
```

## Sicherheit / Architektur

Die Anwendung enthält bereits einige wichtige Grundlagen:

- Passwortspeicherung nur gehasht (`password_hash`)
- Prepared Statements via PDO
- CSRF-Token für schreibende Formulare
- Benutzer dürfen nur ihre eigenen Listen und Aufgaben sehen oder bearbeiten
- HTML-Ausgabe wird escaped

## Reset für lokale Entwicklung

Wenn du die Testdaten komplett löschen willst:

```bash
./reset-data.sh
```

## Erweiterungsideen

- Listen teilen mit anderen Benutzern
- Tags
- Erinnerungen per Mail
- REST-API ergänzen
- Archiv / Papierkorb
- Drag & Drop Sortierung

## Lizenz

Du kannst das Projekt als Ausgangsbasis für deine eigene Weiterentwicklung verwenden.
