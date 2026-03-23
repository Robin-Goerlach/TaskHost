# TaskHost Frontend

TaskHost Frontend ist eine vollständige Weboberfläche für das zuvor erstellte PHP-Backend der Wunderlist-inspirierten Anwendung.
Die Oberfläche ist **buildlos** aufgebaut: HTML, CSS und JavaScript-Module reichen aus. Dadurch kann das Frontend sehr schnell direkt neben dem PHP-Backend betrieben werden, ohne zusätzlich React/Vite/Node im Runtime-Betrieb zu benötigen.

## Funktionsumfang

Das Frontend bildet die fachlichen Kernfunktionen der Anwendung ab:

- Registrierung, Login, Logout
- automatische Annahme von Einladungstokens über `#invite/<token>`
- Sidebar mit smarten Ansichten
  - Heute
  - Geplant
  - Wichtig
  - Mir zugewiesen
  - Erledigt
- Ordner anlegen, bearbeiten, löschen und in der Reihenfolge verschieben
- Listen anlegen, bearbeiten, archivieren, löschen und in der Reihenfolge verschieben
- Listen teilen
  - Mitglieder anzeigen
  - Einladungen erzeugen
  - Mitglieder entfernen
- Aufgaben anlegen, bearbeiten, löschen
- Aufgaben als wichtig markieren
- Aufgaben erledigen / wiederherstellen
- Aufgaben zwischen Listen verschieben
- Aufgaben-Reihenfolge per Hoch/Runter-Steuerung ändern
- Unteraufgaben anlegen, abhaken, bearbeiten, löschen
- Notizen pflegen
- Kommentare anlegen und löschen
- Erinnerungen anlegen, bearbeiten, löschen
- Anhänge hochladen, herunterladen, löschen
- Suche über erreichbare Aufgaben

## Warum kein Framework im Frontend?

Für diesen Ausbauschritt ist die buildlose Variante bewusst gewählt:

- leichter lokal zu testen
- direkt neben der PHP-API betreibbar
- kein zusätzlicher Node-Build für die erste produktive Fassung nötig
- klare und nachvollziehbare Codebasis
- gute Grundlage, um später bei Bedarf gezielt auf React oder eine andere UI-Architektur umzusteigen

Das ist kein Notbehelf, sondern eine bewusste V1-Entscheidung.

## Verzeichnisstruktur

```text
index.html
assets/
  app.css
src/
  main.js
  app.js
  api/
    client.js
    taskhost-api.js
  ui/
    templates.js
  utils/
    date.js
docs/
  FRONTEND_ARCHITECTURE.md
```

## Inbetriebnahme

### 1. Backend starten

Zuerst das PHP-Backend starten, z. B.:

```bash
cd taskhost-api
php -S 127.0.0.1:8080 -t public
```

### 2. Frontend konfigurieren

In `index.html` ist standardmäßig eingetragen:

```html
<script>
  window.TASKHOST_CONFIG = {
    apiBaseUrl: 'http://127.0.0.1:8080/api/v1',
    appName: 'TaskHost',
  };
</script>
```

Wenn API und Frontend auf anderen Hosts laufen, hier die API-URL anpassen.

### 3. Frontend bereitstellen

Du kannst das Frontend mit jedem einfachen statischen Webserver ausliefern.
Zum Beispiel:

```bash
cd taskhost-frontend
python3 -m http.server 4173
```

Danach im Browser öffnen:

```text
http://127.0.0.1:4173
```

## Hinweise zur API-Anbindung

Das Frontend nutzt Bearer Tokens aus dem Backend und speichert sie lokal im Browser.
Anhänge werden per `FormData` hochgeladen und für Downloads per Blob verarbeitet, damit auch mit Bearer-Authentifizierung sauber geladen werden kann.

## Bewusste Grenzen dieser Fassung

Diese Oberfläche ist fachlich sehr weit, aber einige Dinge sind weiterhin spätere Ausbaustufen:

- kein Offline-Sync
- kein WebSocket/Realtime-Collaboration-Layer
- keine Drag-and-Drop-Reihenfolge, sondern explizite Reihenfolgen-Steuerung
- keine Push-Benachrichtigungen
- keine Rich-Text-Notizen
- kein separates Rollen-/Rechtemanagement im Frontend jenseits der Serverantworten

## Empfohlene nächste Schritte

1. Frontend direkt in den TaskHost-Backend-Deploy integrieren
2. OpenAPI-Spezifikation ergänzen und API-Client ableiten
3. End-to-End-Tests für Login, Listen, Aufgaben und Anhänge aufsetzen
4. optional später React- oder Vue-Migration, falls sehr komplexe UI-Interaktionen wachsen
