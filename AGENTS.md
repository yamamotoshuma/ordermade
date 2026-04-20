# AGENTS

## Remote Access

- SSH alias: `ssh sakura-ordermade`
- Server host: `ordermade.sakura.ne.jp`
- SSH user: `ordermade`
- Application root on server: `~/ordermade`
- Deployed public directory on server: `~/www/kanri`

## Server Findings

- The application is a Laravel 10 app with Breeze auth, Vite, Tailwind, and a Sail config.
- The live deployment is split:
  `~/ordermade` contains the Laravel app code, vendor tree, `.env`, and `storage/`.
  `~/www/kanri` contains the public web root that points back into `~/ordermade`.
- The server-side Git checkout is not a reliable source of truth.
  `~/ordermade/.git` exists, but `master` and `origin/master` both point to the single commit `ac1cd59 firstcommit`, while the working tree contains many modified and untracked files.
- `~/ordermade/public/index.php` contained injected obfuscated PHP before the normal Laravel bootstrap code.
  Do not redeploy that file as-is.
- `~/www/kanri/index.php` is the clean front controller currently wired to `../../ordermade/...`.

## Local Workspace

- This workspace was copied from `~/ordermade` on `2026-04-20`.
- The remote `.git` directory was intentionally excluded during the copy.
- Local `public/index.php` was normalized back to a clean Laravel front controller.
- Local `.env` was sanitized for Docker/Sail and no longer points at the Sakura MySQL or SMTP endpoints.
- PWA assets from `~/www/kanri` were copied into local `public/`.
- Hardcoded `/kanri/...` links in Blade templates were rewritten to follow the current request base path so the app can run locally at `/` and on the server under `/kanri`.
- `public/build` is intentionally tracked because the Sakura server does not build frontend assets locally.
- The batting create/edit screens now support a switchable `かんたん入力 / 通常入力` UI without changing the saved `resultId1/2/3` schema or the batting index HTML used by external scraping.
- The batting entry flow is now split so controller work stays thin:
  `App\Services\BattingStatService` owns batting page query/mutation logic and `StoreBattingStatRequest` / `UpdateBattingStatRequest` own validation.
- The other major business controllers were also thinned out in the same style.
  Payments, disbursements, games, batting order import/save, pitching stats, steals, contact notifications, and batting summary aggregation now live in dedicated `App\Services\...Service` classes, with request validation in `App\Http\Requests`.
- `DatabaseSeeder` now seeds server-aligned master data for `disbur_categories`, `positions`, and `batting_result_masters`, plus a local admin user from `INITIAL_ADMIN_*` env vars with defaults `admin@example.com` / `adminpassword`.
- The batting create/edit screens now use the label `かんたん入力`, collapse the `試合・打者・イニング` block by default, and use a responsive SVG field map instead of the previous CSS-built infield shape.
- The batting create screen now auto-suggests the next batter from the current batting order and advances the default inning when the current inning already has 3 or more outs recorded.
- The batting create screen warns before submitting into an inning that already has 3 or more outs.
- The batting order edit screen now supports spreadsheet import from Google Sheets.
  Service account JSON must live at `storage/app/private/google/ordermade-google-service-account.json` or the path set in `GOOGLE_SERVICE_ACCOUNT_PATH`, and must not be committed.
  Import policy is intentionally tolerant: incomplete rows, unknown positions, and unmatched users do not fail the whole import.
  Unknown users are imported as `userName`, duplicate batting orders are re-ranked top-to-bottom, and optional player-name aliases can be set with `GOOGLE_ORDER_USER_ALIASES_JSON`.
- Manager-facing user maintenance now exists at `register/allshow`, with create, edit, and delete actions.
  Delete is safety-biased: users with related records are deactivated instead of being physically removed.
- The standalone `スコア表作成` feature and its route/view were intentionally removed as unused functionality.

## Local Commands

- Start containers: `./vendor/bin/sail up -d`
- Stop containers: `./vendor/bin/sail down`
- Run migrations: `./vendor/bin/sail artisan migrate`
- Open a shell: `./vendor/bin/sail shell`
- Install frontend deps: `npm install`
- Start Vite dev server: `npm run dev`

## Server Deploy Flow

- Recommended server-side source checkout: `~/ordermade-repo`
- Live Laravel app: `~/ordermade`
- Live public root: `~/www/kanri`
- Deploy command from the source checkout:
  `./deploy/sakura/deploy.sh`
- Codex skill installed for this flow: `ordermade-sakura-deploy`

## GitHub Migration Notes

- Treat this local workspace as the new source of truth, not the server checkout.
- Keep `.env` out of Git. `.env.example` is the safe template for GitHub.
- When creating a fresh GitHub repository, initialize it from this local workspace rather than pushing the server's existing `.git` state.
- Deployment is split:
  the Laravel app root is synced into `~/ordermade`
  the public assets/front controller are synced into `~/www/kanri`

## Docs

- Server analysis: [docs/server-analysis.md](/Users/yamamotoshuma/work/kusayakyu/ordermade/docs/server-analysis.md)
- Local setup: [docs/local-setup.md](/Users/yamamotoshuma/work/kusayakyu/ordermade/docs/local-setup.md)
- GitHub migration: [docs/github-migration.md](/Users/yamamotoshuma/work/kusayakyu/ordermade/docs/github-migration.md)
- Batting input UI: [docs/batting-input-ui.md](/Users/yamamotoshuma/work/kusayakyu/ordermade/docs/batting-input-ui.md)
