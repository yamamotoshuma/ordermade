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
