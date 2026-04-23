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

For smartphone checks over the local network, Vite must advertise a LAN-reachable host.
Use:

- `VITE_DEV_SERVER_HOST=0.0.0.0`
- `VITE_HMR_HOST=<your Mac's LAN IP>`

If CSS/JS disappears on another device, check `public/hot`.
If it points to `localhost`, the phone will try to load assets from its own `localhost` and fail.

## Start The Environment

1. Start Docker Desktop.
2. Run:
   `./vendor/bin/sail up -d`
3. Run migrations and seed the default local data:
   `./vendor/bin/sail artisan migrate --seed`
4. Install frontend dependencies:
   `npm install`
5. Start Vite:
   `npm run dev`

If you changed the Vite host settings, stop and restart `npm run dev` so `public/hot` is regenerated.

## Default Local Seed Data

`DatabaseSeeder` now creates:

- a local admin user
- `disbur_categories`
- `positions`
- `batting_result_masters`

Default local admin credentials:

- email: `admin@example.com`
- password: `adminpassword`
- role: `10` (management)

You can override these with:

- `INITIAL_ADMIN_NAME`
- `INITIAL_ADMIN_EMAIL`
- `INITIAL_ADMIN_PASSWORD`

## Spreadsheet Import Setup

Batting order registration can import rows from Google Sheets.

- Service account JSON path:
  `storage/app/private/google/ordermade-google-service-account.json`
- Related env vars:
  `GOOGLE_SERVICE_ACCOUNT_PATH`
  `GOOGLE_ORDER_SPREADSHEET_ID`
  `GOOGLE_ORDER_SPREADSHEET_GID`
  `GOOGLE_ORDER_SPREADSHEET_RANGE`
  `GOOGLE_ORDER_USER_ALIASES_JSON`

Default import range is `B6:D20`, so rows for a second game lower in the sheet are ignored by default.

`GOOGLE_ORDER_USER_ALIASES_JSON` is optional and accepts a JSON object like:

`{"シューマ":"山本修馬","やましゅー":"山本修馬"}`

Import behavior is intentionally tolerant:

- blank or incomplete rows are ignored
- unknown positions are skipped
- unmatched users are stored as free-text `userName`
- duplicate batting orders are ranked from top to bottom as `1, 2, 3...`

Only configuration or Google API failures are treated as errors.

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

Production may serve the app from a subdirectory, but local development serves it from `/`.

To support both environments:

- public asset references in `resources/views/layouts/app.blade.php` derive from the request base path
- `public/manifest.json` uses relative asset paths
- `public/index.php` is a standard Laravel front controller

## What Was Intentionally Not Imported

- The remote `.git` history was excluded.
- Production database contents were not imported.

If you need production-like data locally, create a separate, explicit database export/import workflow instead of reusing production credentials from the server.

## Verification Completed

The local environment has already been verified with:

- `./vendor/bin/sail artisan migrate --seed`
- `./vendor/bin/sail artisan test`
- `npm install`
- `npm run build`
- `curl -I http://127.0.0.1:8080/login`
