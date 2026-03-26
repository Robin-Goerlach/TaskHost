# Architekturentscheidungen

## Ziel

Dieses Backend bildet die fachlichen Kernobjekte einer pfadbasierten Anwendung ab, ohne bereits unnötig komplex zu werden.  
Der Schwerpunkt liegt auf einer stabilen REST-Schnittstelle, klarer Schichtung und einer Struktur, die später gut auf MySQL, Queue-Worker, Mailversand und Frontend-Anbindung erweitert werden kann.

## Schichten

### HTTP Entry Point

`index.php` im API-Root ist der produktive HTTP-Einstiegspunkt.  
Ein klassischer `public/`-Webroot wird im Shared-Hosting-Zielbild bewusst nicht verwendet. Dort wird die Anwendung gebootstrapped und die Anfrage an Router und Controller übergeben.

### Bootstrap

`src/Bootstrap.php` verdrahtet Repositories, Services, Controller und Router explizit.  
Für V1 wurde bewusst kein externer DI-Container eingebaut, damit die Codebasis leicht nachvollziehbar bleibt.

### HTTP-Schicht

- `Request`
- `Response`
- `Router`

Diese Schicht kapselt Transportdetails und hält Controller schlank.

### Controller

Controller übernehmen nur:
- Annahme der HTTP-Anfrage
- Weitergabe an Services
- Ausgabe einer JSON-Response

### Services

Services enthalten die Fachlogik:
- Rechteprüfung
- Validierung
- wiederkehrende Aufgaben
- Einladungsannahme
- Upload-Abläufe

### Repositories

Repositories kapseln den Datenzugriff über PDO.  
Dadurch bleibt die Fachlogik von SQL getrennt und die Persistenz später austauschbar.

## Wichtige Entscheidungen

## 1. Bearer Tokens statt JWT

Für V1 werden opaque Bearer Tokens in der Datenbank gespeichert.

Vorteile:
- serverseitig widerrufbar
- einfach zu verstehen
- keine Signatur- und Refresh-Logik in V1 nötig

Nachteil:
- jeder Request braucht einen Datenbankzugriff zur Token-Prüfung

Das ist für eine erste robuste Version akzeptabel.

## 2. PDO statt Framework

Es wurde bewusst kein Laravel/Symfony eingesetzt.

Gründe:
- geringer Overhead
- leicht lesbarer Kern
- passend zu einem kontrollierten SASD-V1-Grundgerüst
- gute Grundlage, um Architektur bewusst selbst zu beherrschen

## 3. Dateien lokal speichern

Anhänge werden zunächst im lokalen Dateisystem abgelegt.

Vorteile:
- einfach
- lokal testbar
- kein Cloud-Zwang

Später möglich:
- S3-kompatibler Storage
- Virenscan
- Größenlimits
- Vorschaubilder

## 4. Rollenmodell auf Listenebene

Unterstützt werden:
- `owner`
- `editor`
- `viewer`

Das genügt für die Kernlogik:
- Eigentümer verwaltet Liste und Mitglieder
- Editor darf Inhalte ändern
- Viewer darf lesen

## 5. Einladungen als eigener Datensatz

Wenn ein Benutzerkonto zur Ziel-E-Mail bereits existiert, wird sofort eine Mitgliedschaft angelegt.  
Wenn noch kein Konto existiert, wird eine Einladung mit Token angelegt.

So kann das Backend später problemlos um Mailversand erweitert werden.

## 6. Wiederkehrende Aufgaben

Beim Abschließen einer wiederkehrenden Aufgabe wird:
- die aktuelle Aufgabe als erledigt markiert
- eine neue Folgeaufgabe mit neuer Fälligkeit erzeugt

Das ist für To-do-Systeme meist verständlicher als das Überschreiben derselben Aufgabe.

## Ausbaustufen

### Sinnvolle nächste Schritte

1. E-Mail-Service für Einladungen und Erinnerungen  
2. Queue/Worker für Reminder-Versand  
3. Rate Limiting und Abuse Protection  
4. Audit Log / Activity Feed  
5. Soft Delete und Restore für Listen/Aufgaben  
6. OpenAPI-Spezifikation  
7. PHPUnit-Tests  
8. Frontend mit React oder Vanilla JS gegen dieses API

## Sicherheitsnotizen

Bereits berücksichtigt:
- Passwort-Hashing
- Bearer-Authentifizierung
- Rollenprüfung
- keine direkten Dateinamen als Speicherziel
- Foreign Keys in der Datenbank
- Trennung von Request- und Fachlogik

Noch sinnvoll:
- Request-Rate-Limits
- Request-Size-Limits
- MIME-Validierung mit `finfo`
- CSRF nur relevant bei Cookie-Auth, hier aktuell nicht nötig
- strukturierte Security-Logs
- Mail-Verification vor Kollaboration nach außen
