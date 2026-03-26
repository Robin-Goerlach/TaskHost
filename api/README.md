# TaskHost API

TaskHost API supports **two deployment profiles** on purpose.

## 1. Current production profile: path-based shared hosting

This is the profile currently targeted for IONOS-style shared hosting:

- `https://api.sasd.de/taskhost/v1/...`
- `https://api.sasd.de/health/v1/...`

In this model the productive front controller is the **root-level `index.php`**
next to `src/`, `vendor/`, `storage/` and the root-level `.htaccess` protects
internal directories.

## 2. Optional future profile: classic `public/` webroot

A fully documented optional front controller also exists below:

- `public/index.php`
- `public/.htaccess`
- `public/router.php`

This profile is meant for a future setup such as `https://taskhost.sasd.de`
where the web server can point directly to `public/` as the document root.

## Prominent architectural reminder

**TaskHost currently runs productively without a classic `public/` webroot.**
That is unusual and must be repeated explicitly in future documentation,
operations notes and deployment instructions.

At the same time, the optional `public/` webroot now exists to keep a clean
migration path open if the hosting model changes later.

## Canonical API root

The canonical API surface is available below:

```text
/v1
```

A compatibility route set for `/api/v1` is still registered for older local
clients, but new deployments should use `/v1`.

## Configuration

- `APP_URL` points to the API service root, for example `https://api.sasd.de/taskhost`
- `APP_BASE_PATH` is usually left empty and only used when the base path cannot be derived automatically
- `FRONTEND_APP_URL` points to the client root, for example `https://app.sasd.de/taskhost`
- `CORS_ALLOW_ORIGIN` contains only the origin, for example `https://app.sasd.de`

## Local development

### Shared-hosting profile

```bash
php -S 127.0.0.1:8080 router.php
```

### Optional classic `public/` profile

```bash
php -S 127.0.0.1:8080 -t public public/router.php
```
