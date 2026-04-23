# Server Analysis

## Snapshot Date

- `2026-04-20`

## Publication Policy

This document is sanitized for a public repository.
It intentionally does not include production hostnames, connection users, concrete server paths, database hosts, or mail endpoints.

## Runtime Observations

- PHP 8.3系
- Composer 2系
- Node.js is not available on the production server
- Package manager on the app: Composer + npm lockfile present

## Framework and Dependencies

- Laravel: `^10.10`
- PHP requirement: `^8.1`
- Auth stack: Breeze
- Frontend toolchain: Vite, Tailwind, Alpine
- Local container config in repo: Laravel Sail with MySQL, Redis, Meilisearch, Mailpit, Selenium, phpMyAdmin

## Deployment Shape

Production uses a split deployment shape:

- A live Laravel application directory contains application code, `.env`, `vendor/`, `storage/`, migrations, and an internal `public/` folder.
- A separate public web root contains publicly exposed files such as:
  `.htaccess`
  `index.php`
  `manifest.json`
  `sw.js`
  `build/`
  icon files
  splash screens
- The public front controller points back to the Laravel application directory.

Concrete directory names are intentionally omitted. Use private operations notes for actual server paths.

## Git State Observed During Import

- The production server had Git metadata, but it was not a reliable source of truth.
- The deployed application existed mostly as uncommitted working tree changes.
- The local workspace was therefore imported without trusting production Git metadata.
- Conclusion:
  GitHub should be treated as the source of truth going forward, and production should be updated via an explicit deploy flow.

## Functional Surface Area

Controllers and routes indicate these modules:

- `PayController`: payments and bulk payment insert
- `dCategoryController`: expense category master
- `disburController`: disbursement entry and related screens
- `GameController`: game CRUD and bulk score update/insert
- `BattingEditController`: per-game batting result editing
- `BattingOrderController`: batting order CRUD and spreadsheet import
- `BattingStatsController`: batting aggregate stats
- `PitchingStatsController`: pitching stats entry/edit/delete
- `StealController`: steal tracking
- `ContactController`: contact form and notifications
- Breeze auth/profile flows

## Configuration Notes From Production `.env`

The production `.env` contained production-only database and mail settings.
Those values are intentionally not copied into this repository.

For local development, use the sanitized `.env.example` and local Sail defaults.

## Security Findings

- One copied internal public front controller contained injected obfuscated PHP before the normal Laravel bootstrap code.
- That injected code referenced external domains and attempted to write extra PHP files.
- A clean front controller was used in the local workspace.
- Recommended production-side action:
  inspect and clean the affected front controller and review the rest of the deployment for compromise.

No malicious payload content, domains, or exact production paths are included here.

## Public Asset Differences

The live public web root differed from the Laravel app's internal `public/` directory in several important ways:

- clean public front controller
- additional PWA icons and splash screens
- deployment-specific `manifest.json`
- deployment-specific `sw.js`

For local development, the missing public assets were copied into `public/`, while path handling was normalized so the app works locally at `/`.

Because the production server does not build frontend assets, the repository tracks `public/build` so the deployed app can serve Vite-built assets without rebuilding on the server.
