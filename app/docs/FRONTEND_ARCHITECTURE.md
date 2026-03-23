# Frontend-Architektur

## Ziel

Dieses Frontend soll die fachlichen Möglichkeiten des TaskHost-Backends möglichst vollständig nutzbar machen,
ohne für die erste robuste Auslieferung noch zusätzliche Build-Werkzeuge oder Framework-Runtime einzuführen.

## Architekturprinzipien

### 1. Buildlose Auslieferung

Die Anwendung besteht aus:

- statischem HTML
- zentralem CSS
- nativen ES-Modulen im Browser

Vorteile:

- extrem einfache Bereitstellung
- geringe Betriebsabhängigkeiten
- gute Nachvollziehbarkeit für spätere Erweiterungen
- direkte Einbettung in ein bestehendes PHP-Deployment möglich

### 2. API-zentrierte Oberfläche

Die Fachlogik liegt im Backend. Das Frontend übernimmt:

- Zustandsdarstellung
- Benutzerinteraktion
- Formularverarbeitung
- API-Aufrufe
- Anzeige von Fehlern und Rückmeldungen

### 3. Klar getrennte Schichten

#### `src/api/`

Kapselt HTTP-Details und konkrete TaskHost-Endpunkte.

#### `src/ui/`

Erzeugt HTML für Auth, Sidebar, Aufgabenbereich, Detailspalte und Modaldialoge.

#### `src/utils/`

Enthält Helfer für Datumsformatierung, HTML-Escaping und Umwandlungen für Formulare.

#### `src/app.js`

Steuert den Zustand, die Eventverarbeitung und die Orchestrierung aller Abläufe.

## Zustandsmodell

Der zentrale Zustand enthält u. a.:

- Session / Benutzer
- Ordner und Listen
- aktuelle Ansicht (Liste oder Smart View)
- aktuelle Aufgabenliste
- aktuell gewählte Aufgabe
- Zusatzdaten zur Aufgabe
  - Unteraufgaben
  - Notiz
  - Kommentare
  - Erinnerungen
  - Anhänge
- Modalzustand
- Suche
- Toasts / Fehlermeldungen

## Warum keine viele Einzel-Eventlistener?

Die Oberfläche wird zentral neu gerendert. Daher wird mit Event Delegation gearbeitet:

- Klicks werden am Root abgefangen
- Form-Submits werden am Root abgefangen
- Such-Inputs werden zentral verarbeitet

Das hält die Implementierung trotz vieler UI-Bausteine relativ übersichtlich.

## Sicherheit und Robustheit

Bereits berücksichtigt:

- Escape von HTML-Ausgaben
- Bearer Token nur in Requests, nicht in URLs
- Datei-Downloads über Blob statt unsicherer Direktlinks mit Tokenparametern
- serverseitige Rechteprüfung bleibt führend
- Fehlermeldungen werden sichtbar gemacht, aber der Server bleibt Autorität

## Sinnvolle Erweiterungen

- clientseitige Rollenanzeige pro Button/Abschnitt
- differenziertere Ladezustände
- Undo-Aktionen
- Drag-and-Drop für Aufgaben und Listen
- dedizierter Router für Deep Links
- End-to-End-Testautomatisierung
- komponentenorientierte Migration nach React, falls später nötig
