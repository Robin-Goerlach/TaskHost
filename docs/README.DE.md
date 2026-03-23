# TaskHost API (PHP)

> PHP-Backend für eine listenbasierte Aufgabenverwaltung im Stil von Wunderlist – in diesem Stand **noch ohne Mail-Queue**.

## Überblick

Dieses Repository enthält das Backend von **TaskHost**, einer Aufgaben- und Listenverwaltung mit Fokus auf einer klaren REST-Struktur, nachvollziehbarer Architektur und einer soliden Grundlage für spätere Erweiterungen.

Der hier dokumentierte Stand beschreibt bewusst eine Version **vor Einführung einer Mail-Queue**. Asynchrone Mail-Verarbeitung ist in diesem Entwicklungsstand also **noch nicht enthalten**. Falls E-Mail-Funktionen bereits vorgesehen sind, werden sie in diesem Stand entweder noch gar nicht oder nur direkt/synchron umgesetzt.

Das Projekt ist so gedacht, dass es als robuste Basis für weitere Module dienen kann, zum Beispiel für Benutzerverwaltung, Aufgabenlisten, Freigaben, Kommentare, Erinnerungen oder spätere Hintergrundprozesse.

---

## Projektstatus

Der aktuelle Stand konzentriert sich auf die fachlichen Kernfunktionen einer Aufgabenverwaltung und auf eine saubere Backend-Struktur.

**Wichtig für diesen Stand:**

- **keine Mail-Queue**
- **keine asynchrone Hintergrundverarbeitung für E-Mails**
- Fokus auf **klare API-Endpunkte**, **Geschäftslogik** und **persistente Datenhaltung**
- Architektur so vorbereitet, dass spätere Erweiterungen möglich bleiben

---

## Ziele des Projekts

TaskHost soll als technisch sauberes Backend für eine moderne Aufgabenverwaltung dienen. Die API ist darauf ausgelegt, eine Web-Oberfläche, ein mobiles Frontend oder andere Clients zuverlässig zu versorgen.

Dabei stehen folgende Ziele im Vordergrund:

- klare Trennung von HTTP-Schicht, Fachlogik und Datenzugriff
- verständliche und wartbare PHP-Struktur
- REST-orientierte Schnittstellen mit JSON
- sichere und erweiterbare Grundlage für spätere Versionen
- kleine, robuste V1 statt frühzeitiger Überfrachtung

---

## Möglicher Funktionsumfang dieses Entwicklungsstands

Je nach exakt erreichtem Code-Stand umfasst diese Version typischerweise die Grundlagen einer Aufgabenverwaltung, zum Beispiel:

- Benutzerregistrierung und/oder Anmeldung
- Verwaltung von Projekten, Listen oder Aufgabenbereichen
- Anlegen, Bearbeiten, Lesen und Löschen von Aufgaben
- Statuswechsel von Aufgaben, z. B. offen/erledigt
- Zuordnung von Aufgaben zu Benutzern oder Listen
- API-Antworten im JSON-Format
- grundlegende Validierung und Fehlerbehandlung

Falls einzelne dieser Punkte in deinem aktuellen Code-Stand noch nicht enthalten sind, kannst du die Liste problemlos kürzen.

---

## Was in dieser Version bewusst **nicht** enthalten ist

Diese README beschreibt ausdrücklich den Stand **ohne Mail-Queue**. Nicht Bestandteil dieses Stands sind daher insbesondere:

- asynchrone E-Mail-Verarbeitung
- Queue-Worker
- Retry-Mechanismen für E-Mail-Versand
- Dead-Letter-Handling
- separates Job-/Worker-Subsystem

Diese Punkte können in einem späteren Architektur- oder Release-Schritt ergänzt werden.

---

## Technischer Ansatz

Die API ist als klassischer PHP-Service gedacht, mit einer klaren Trennung der Verantwortlichkeiten. Typischerweise bedeutet das:

- **HTTP-Schicht** für Request/Response-Verarbeitung
- **Controller** für Routing-nahe Steuerung
- **Services** für Fachlogik
- **Repositories** oder Datenzugriffsklassen für Persistenz
- **Security-Komponenten** für Authentifizierung und Autorisierung
- **Infrastructure-Schicht** für Datenbank, Konfiguration und technische Hilfsdienste

Dieses Vorgehen hilft dabei, die Anwendung übersichtlich zu halten und spätere Refactorings, Tests und Erweiterungen zu erleichtern.

---

## Technologie-Stack

Der konkrete Stack kann je nach aktuellem Stand leicht abweichen. Für das Projekt ist typischerweise vorgesehen:

- **PHP 8.2+**
- **Composer**
- **MySQL oder MariaDB**
- JSON-basierte REST-Kommunikation
- Routing über einen zentralen Einstiegspunkt
- optional `.env`-basierte Konfiguration

Wenn du bereits konkrete Bibliotheken einsetzt, kannst du sie hier ergänzen, zum Beispiel:

- `vlucas/phpdotenv`
- `firebase/php-jwt`
- `monolog/monolog`
- `phpunit/phpunit`

---

## Beispielhafte Projektstruktur

Die genaue Ordnerstruktur hängt vom tatsächlichen Repository ab. Für eine saubere PHP-API bietet sich etwa folgende Struktur an:

```text
.
├── cli/
├── config/
├── public/
│   └── index.php
├── src/
│   ├── Http/
│   ├── Controller/
│   ├── Service/
│   ├── Repository/
│   ├── Security/
│   └── Infrastructure/
├── tests/
├── var/
├── vendor/
├── .env
├── .env.example
├── composer.json
└── README.md
```

Falls dein aktueller Stand noch einfacher aufgebaut ist, ist das kein Problem. Die README kann trotzdem so formuliert bleiben, weil sie die Zielrichtung sauber erklärt.

---

## Installation

### 1. Repository klonen

```bash
git clone git@github.com:Robin-Goerlach/TaskHost.git
cd TaskHost
```

### 2. Abhängigkeiten installieren

```bash
composer install
```

### 3. Konfiguration anlegen

Falls das Projekt mit Umgebungsvariablen arbeitet, kann eine lokale Konfigurationsdatei z. B. so erstellt werden:

```bash
cp .env.example .env
```

Beispiel für mögliche Einträge:

```env
APP_ENV=dev
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=taskhost
DB_USER=taskhost
DB_PASSWORD=change-me

JWT_SECRET=please-change-this-secret
```

> Die konkreten Variablennamen sollten an deinen tatsächlichen Code angepasst werden.

### 4. Datenbank einrichten

Lege eine Datenbank für das Projekt an und spiele das vorhandene Schema oder die SQL-Dateien ein, falls diese bereits im Repository enthalten sind.

Typischerweise werden dabei Tabellen für Benutzer, Aufgaben, Listen oder Projekte angelegt.

### 5. Webserver konfigurieren

Richte den Webserver so ein, dass Anfragen an den zentralen Einstiegspunkt der API weitergeleitet werden. Je nach Projektstruktur ist das meist `public/index.php` oder `index.php` im Projektwurzelverzeichnis.

---

## Lokale Entwicklung

Für die lokale Entwicklung empfiehlt sich eine klare Trennung zwischen Konfiguration, Routing, Fachlogik und Datenbankzugriff.

Zusätzlich hilfreich:

- aussagekräftige Fehlerantworten im Entwicklungsmodus
- strukturierte Logs
- reproduzierbare SQL-Skripte
- einfache Testdaten für lokale Entwicklung

Wenn du bereits Composer-Skripte definiert hast, kannst du hier später noch einen Block wie diesen ergänzen:

```bash
composer test
composer analyse
composer cs
```

---

## API-Grundprinzipien

Die API ist auf eine saubere JSON-Kommunikation ausgelegt.

### Typische Eigenschaften

- Requests und Responses im JSON-Format
- sinnvolle HTTP-Statuscodes
- Validierung eingehender Nutzdaten
- nachvollziehbare Fehlermeldungen
- saubere Trennung zwischen fachlichen und technischen Fehlern

### Beispiel für eine JSON-Antwort

```json
{
  "status": "success",
  "data": {
    "id": 42,
    "title": "API README erstellen",
    "isDone": false
  }
}
```

### Beispiel für eine Fehlerantwort

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "title": "The title field is required."
  }
}
```

---

## Beispielhafte Endpunkte

Die genauen Routen hängen vom aktuellen Stand deiner Implementierung ab. Für eine Wunderlist-ähnliche API sind typischerweise Endpunkte wie diese sinnvoll:

```text
GET    /health
POST   /api/v1/auth/register
POST   /api/v1/auth/login
GET    /api/v1/users/me

GET    /api/v1/lists
POST   /api/v1/lists
GET    /api/v1/lists/{id}
PATCH  /api/v1/lists/{id}
DELETE /api/v1/lists/{id}

GET    /api/v1/tasks
POST   /api/v1/tasks
GET    /api/v1/tasks/{id}
PATCH  /api/v1/tasks/{id}
DELETE /api/v1/tasks/{id}
```

Wenn dein aktueller Code andere Begriffe verwendet, z. B. `projects`, `task-lists` oder `todos`, solltest du die Route-Namen entsprechend anpassen.

---

## Beispiel-Request

```http
POST /api/v1/tasks
Content-Type: application/json
Authorization: Bearer <token>

{
  "title": "README für GitHub schreiben",
  "description": "Version ohne Mail-Queue dokumentieren",
  "isDone": false
}
```

---

## Sicherheit

Schon in einem frühen Entwicklungsstand sollte die API nicht nur funktionieren, sondern auch sauber abgesichert sein. Dazu gehören insbesondere:

- serverseitige Validierung aller Eingaben
- Schutz vor SQL-Injection durch parametrisierte Queries
- saubere Passwortverarbeitung mit sicheren Hash-Verfahren
- kontrollierter Umgang mit Fehlermeldungen
- Trennung zwischen öffentlicher API und internen technischen Details
- vorsichtiger Umgang mit Debug-Ausgaben in produktiven Umgebungen

Falls Authentifizierung bereits vorhanden ist, kann hier zusätzlich dokumentiert werden, ob Sessions, Tokens oder JWT verwendet werden.

---

## Tests

Automatisierte Tests helfen dabei, die API zuverlässig weiterzuentwickeln und spätere Umbauten sicherer zu machen.

Sinnvoll sind insbesondere:

- Unit-Tests für Services
- Tests für Validierungslogik
- Repository- oder Datenbank-nahe Tests
- API-Tests für zentrale Endpunkte

Wenn PHPUnit bereits integriert ist, kannst du den Abschnitt später konkretisieren.

---

## Roadmap

Mögliche nächste Schritte für spätere Versionen:

- Mail-Versand integrieren oder ausbauen
- **Mail-Queue** für asynchrone Verarbeitung ergänzen
- Worker-Prozesse einführen
- Erinnerungen und Benachrichtigungen ausbauen
- Kommentare, Labels und Prioritäten ergänzen
- Freigaben oder Team-Funktionalität hinzufügen
- OpenAPI/Swagger-Dokumentation ergänzen
- Testabdeckung erhöhen
- Logging und Monitoring ausbauen

---

## Hinweise zur Weiterentwicklung

Für dieses Projekt ist eine inkrementelle Entwicklung sinnvoll: erst eine kleine, robuste Basis, danach gezielte Erweiterungen.

Gerade Funktionen wie Mail-Queue, Worker, Hintergrundjobs oder umfangreiche Benachrichtigungen sollten erst dann ergänzt werden, wenn die Kernarchitektur stabil ist und die API-Fachlogik sauber funktioniert.

So bleibt das Projekt verständlich, testbar und langfristig wartbar.

---

## Lizenz

Füge hier die tatsächlich verwendete Lizenz des Projekts ein, zum Beispiel:

```text
MIT License
```

oder

```text
GNU Affero General Public License v3.0
```

---

## Zusammenfassung

TaskHost API ist als PHP-Backend für eine moderne, listenbasierte Aufgabenverwaltung gedacht. Diese README beschreibt bewusst einen frühen bis mittleren Entwicklungsstand, in dem die fachlichen Kernfunktionen im Vordergrund stehen und **eine Mail-Queue noch nicht Bestandteil des Systems ist**.

Dadurch bleibt der Stand übersichtlich, nachvollziehbar und gut als Basis für weitere Ausbaustufen geeignet.
