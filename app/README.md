# TaskHost Frontend

TaskHost Frontend is the browser UI for the TaskHost PHP backend. It is intentionally kept as a modular JavaScript application without a mandatory build step.

## Included Features

- registration, login, logout
- automatic invitation acceptance via `#invite/<token>`
- smart views
  - Today
  - Planned
  - Important
  - Assigned to me
  - Completed
- folder creation, update, deletion, and ordering
- list creation, update, archive, deletion, and ordering
- sharing UI
  - members overview
  - invitation creation
  - invitation resend
  - member removal
  - invitation-link copy flow
- tasks
  - create, update, delete
  - star / unstar
  - complete / restore
  - move between lists
- subtasks
- notes
- comments
- reminders
  - `in_app`, `email`, `both`
  - queue-aware status rendering where available
- attachments
- search

## Why no required build step?

For this stage, the buildless approach is intentional:
- easy to run locally next to the PHP API
- fewer moving parts for the first robust version
- readable code structure
- good basis for a later React/Vue migration if UI complexity grows further

## Structure

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

## Local Start

### 1. Start the backend

```bash
cd ../api
php -S 127.0.0.1:8080 -t public
```

### 2. Start the frontend

```bash
python3 -m http.server 4173
```

Then open:
- frontend: `http://127.0.0.1:4173`
- backend: `http://127.0.0.1:8080/api/v1`

By default, `index.html` is configured for this local split setup.

## Useful NPM Script

Syntax check for the JavaScript modules:

```bash
npm run check
```
