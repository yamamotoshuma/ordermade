# Server Analysis

## Snapshot Date

- `2026-04-20`

## Remote Paths

- Host: `ordermade.sakura.ne.jp`
- SSH user: `ordermade`
- App root: `~/ordermade`
- Public web root: `~/www/kanri`

## Runtime Observations

- PHP: `8.3.30`
- Composer: `2.5.8`
- Node.js: not installed on the server
- Package manager on the app: Composer + npm lockfile present

## Framework and Dependencies

- Laravel: `^10.10`
- PHP requirement: `^8.1`
- Auth stack: Breeze
- Frontend toolchain: Vite, Tailwind, Alpine
- Local container config in repo: Laravel Sail with MySQL, Redis, Meilisearch, Mailpit, Selenium, phpMyAdmin

## Deployment Shape

- `~/ordermade` holds the application code, `.env`, `vendor/`, `storage/`, migrations, and the internal `public/` folder.
- `~/www/kanri` holds the publicly exposed files:
  `.htaccess`
  `index.php`
  `manifest.json`
  `sw.js`
  `build/`
  icon files
  splash screens
- `~/www/kanri/index.php` points back into `~/ordermade/bootstrap`, `~/ordermade/vendor`, and `~/ordermade/storage`.

## Git State On The Server

- `origin` was configured as `https://github.com/yamamotoshuma/ordermade.git`
- `master`, `origin/master`, and `origin/HEAD` all pointed to:
  `ac1cd59 firstcommit`
- The working tree in `~/ordermade` had many modified tracked files and many untracked files.
- Conclusion:
  the production server is being used as an editing surface, and GitHub is not a faithful representation of the deployed app.

## Functional Surface Area

Controllers and routes indicate these modules:

- `PayController`: payments and bulk payment insert
- `dCategoryController`: expense category master
- `disburController`: disbursement entry, edit, score view
- `GameController`: game CRUD and bulk update/insert
- `BattingEditController`: per-game batting result editing
- `BattingOrderController`: batting order CRUD
- `BattingStatsController`: batting aggregate stats
- `PitchingStatsController`: pitching stats entry/edit/delete
- `StealController`: steal tracking
- `ContactController`: contact form and mail view
- Breeze auth/profile flows

The app currently exposes `83` routes.

## Configuration Notes From `.env`

The server `.env` used:

- `DB_CONNECTION=mysql`
- remote Sakura MySQL host
- SMTP mailer against the production host
- `APP_ENV=local`
- `APP_URL=http://localhost`

Those values were not appropriate to copy directly into a local development environment, so the local `.env` was sanitized after import.

## Security Findings

- `~/ordermade/public/index.php` contained obfuscated PHP prepended before the normal Laravel bootstrap code.
- That injected code referenced external domains and attempted to write extra PHP files.
- `~/www/kanri/index.php` was clean and did not include the injected block.
- Action taken locally:
  the copied `public/index.php` was replaced with a clean Laravel front controller.
- Action still recommended on the server:
  inspect and clean `~/ordermade/public/index.php` and review the rest of the deployment for compromise.

## Public Asset Differences

The deployed `~/www/kanri` directory differed from `~/ordermade/public` in several important ways:

- clean `index.php` in `~/www/kanri`
- additional PWA icons and splash screens
- different `manifest.json`
- different `sw.js`

For local development, the missing public assets were copied into `public/`, while path handling was normalized so the app works locally at `/`.

Because the Sakura server does not have Node.js, the repository now tracks `public/build` so the deployed app can serve Vite-built assets without rebuilding on the server.
