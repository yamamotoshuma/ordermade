# Local Setup

## Prerequisites

- Docker Desktop
- Node.js and npm

This machine already has:

- `node v24.10.0`
- `npm 11.6.0`
- Docker CLI and Compose

PHP and Composer are not installed on the host, so the intended workflow is Laravel Sail.

## Current Local Defaults

Local `.env` is configured for Sail:

- app URL: `http://localhost:8080`
- app port: `8080`
- database host: `mysql`
- database name: `ordermade`
- database user: `sail`
- database password: `password`
- mail: Mailpit

## Start The Environment

1. Start Docker Desktop.
2. Run:
   `./vendor/bin/sail up -d`
3. Run migrations:
   `./vendor/bin/sail artisan migrate`
4. Install frontend dependencies:
   `npm install`
5. Start Vite:
   `npm run dev`

## Useful Commands

- App shell:
  `./vendor/bin/sail shell`
- Artisan:
  `./vendor/bin/sail artisan <command>`
- Composer in container:
  `./vendor/bin/sail composer <command>`
- Stop containers:
  `./vendor/bin/sail down`

## Local URL

- App: `http://localhost:8080`
- Mailpit UI: `http://localhost:8026`
- phpMyAdmin: `http://localhost:8081`

## Path Normalization

Production serves the app from `/kanri`, but local development serves it from `/`.

To support both environments:

- public asset references in `resources/views/layouts/app.blade.php` now derive from the request base path
- `public/manifest.json` uses relative asset paths
- `public/index.php` is a standard Laravel front controller

## What Was Intentionally Not Imported

- The remote `.git` history was excluded.
- Production database contents were not imported.

If you need production-like data locally, create a separate, explicit database export/import workflow instead of reusing production credentials from the server.

## Verification Completed

The local environment has already been verified with:

- `./vendor/bin/sail artisan migrate --force`
- `./vendor/bin/sail artisan test`
- `npm install`
- `npm run build`
- `curl -I http://127.0.0.1:8080/login`
