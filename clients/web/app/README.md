# TaskHost Client

The TaskHost client is intentionally kept path-based so it can be deployed below
service folders such as:

- `https://app.sasd.de/taskhost`
- `https://app.sasd.de/taskhost_old`
- `https://app.sasd.de/health`

## Important configuration rule

Only one file needs to be changed when the API service path changes:

```text
config/taskhost.config.js
```

Example:

```js
window.TASKHOST_CONFIG = {
  apiBaseUrl: 'https://api.sasd.de/taskhost',
  appName: 'TaskHost',
};
```

If the API folder is renamed to `taskhost_old`, change only that URL.

## API root expectation

The client expects only the **service root**, not the full versioned API path.
It appends `/v1` internally.

Examples:

- `https://api.sasd.de/taskhost`
- `https://api.sasd.de/taskhost_old`
- `http://127.0.0.1:8080`

## Deployment note

The client does not currently require a dedicated `public/` webroot. It is
served directly from its service folder below `app.sasd.de/<service-name>`.

## Local development

```bash
php -S 127.0.0.1:4173 router.php
```
