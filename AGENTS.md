# AGENTS

このファイルは開発者・エージェント向けの作業メモです。
公開リポジトリに入る可能性があるため、本番ホスト名、接続ユーザー、サーバー上の実パス、接続コマンド、秘密情報は記載しません。

## Production Information Policy

- 本番環境の接続先、接続エイリアス、ユーザー名、配置パスは非公開メモで管理します。
- `.env`、サービスアカウント JSON、SMTP/DB 接続情報は Git に含めません。
- 本番固有の配置は `LIVE_APP_DIR`、`LIVE_PUBLIC_DIR` などの環境変数で指定できるようにします。
- 公開ドキュメントには「アプリケーションディレクトリ」「公開ディレクトリ」のような一般名だけを書きます。

## Project Notes

- Laravel 10 app with Breeze auth, Vite, Tailwind, and Sail.
- 本番はサブディレクトリ配信、ローカルは `/` 配信になる可能性があるため、Blade に固定の本番パスを増やさないでください。
- `public/build` は意図的に Git 管理対象です。本番環境でフロントエンドビルドしない運用のため、`npm run build` 後の差分もコミット対象です。
- `.env` はローカル専用です。Git に載せる設定は `.env.example` に限定してください。
- PWA assets are stored under `public/`.
- The batting create/edit screens support switchable `かんたん入力 / 通常入力` without changing the saved `resultId1/2/3` schema or the batting index HTML used by external scraping.
- The batting entry flow is split so controller work stays thin:
  `App\Services\BattingStatService` owns batting page query/mutation logic and `StoreBattingStatRequest` / `UpdateBattingStatRequest` own validation.
- The other major business controllers follow the same style.
  Payments, disbursements, games, batting order import/save, pitching stats, steals, contact notifications, and batting summary aggregation live in dedicated `App\Services\...Service` classes, with request validation in `App\Http\Requests`.
- `DatabaseSeeder` seeds server-aligned master data for `disbur_categories`, `positions`, and `batting_result_masters`, plus a local admin user from `INITIAL_ADMIN_*` env vars with defaults `admin@example.com` / `adminpassword`.
- The batting create/edit screens use the label `かんたん入力`, collapse the `試合・打者・イニング` block by default, and use a responsive SVG field map.
- The batting create screen auto-suggests the next batter from the current batting order and advances the default inning when the current inning already has 3 or more outs recorded.
- The batting create screen warns before submitting into an inning that already has 3 or more outs.
- Batting stat creation uses a same-game / same-inning / same-batter conflict flow.
  On MySQL it acquires a short named lock before checking existing rows; if an existing row is found, the create screen shows a confirmation alert and only updates the row when the user explicitly chooses update.
- The batting order edit screen supports spreadsheet import from Google Sheets.
  Service account JSON must live under `storage/app/private/google/` or the path set in `GOOGLE_SERVICE_ACCOUNT_PATH`, and must not be committed.
  Import policy is intentionally tolerant: incomplete rows, unknown positions, and unmatched users do not fail the whole import.
  Unknown users are imported as `userName`, duplicate batting orders are re-ranked top-to-bottom, and optional player-name aliases can be set with `GOOGLE_ORDER_USER_ALIASES_JSON`.
- Manager-facing user maintenance exists at `register/allshow`, with create, edit, and delete actions.
  Delete is safety-biased: users with related records are deactivated instead of being physically removed.
- The standalone `スコア表作成` feature and its route/view were intentionally removed as unused functionality.

## Local Commands

- Start containers: `./vendor/bin/sail up -d`
- Stop containers: `./vendor/bin/sail down`
- Run migrations: `./vendor/bin/sail artisan migrate`
- Open a shell: `./vendor/bin/sail shell`
- Install frontend deps: `npm install`
- Start Vite dev server: `npm run dev`
- Run tests: `./vendor/bin/sail artisan test`
- Check Blade: `./vendor/bin/sail artisan view:cache`
- Build assets: `npm run build`

## Deployment Notes

- Deploy from a clean Git checkout on the production server, not from an edited live application directory.
- The deploy script is intentionally generic and reads destination paths from environment variables.
- Required deploy env vars:
  `LIVE_APP_DIR`
  `LIVE_PUBLIC_DIR`
- Deployment is split:
  Laravel application files are synced into the live application directory.
  Public assets/front controller are synced into the live public directory.
- Never put concrete production hostnames, connection aliases, or absolute production paths in tracked docs.

## GitHub Notes

- Treat this local workspace as the source of truth, not a drifted server checkout.
- Keep `.env` out of Git. `.env.example` is the safe template.
- If a fresh GitHub repository is created, initialize it from this local workspace rather than from production server state.

## Docs

- Server analysis: [docs/server-analysis.md](./docs/server-analysis.md)
- Local setup: [docs/local-setup.md](./docs/local-setup.md)
- GitHub migration: [docs/github-migration.md](./docs/github-migration.md)
- Batting input UI: [docs/batting-input-ui.md](./docs/batting-input-ui.md)
