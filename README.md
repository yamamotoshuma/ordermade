# Ordermade 管理アプリ

草野球チーム「オーダーメイド」の管理画面です。Laravel 10 / Breeze / Sail / Vite / Tailwind をベースに、試合・打順・打撃成績・投手成績・盗塁・入出金・ユーザ管理を扱います。

本番環境は別途運用しています。公開リポジトリにサーバー固有の接続情報や配置パスは載せない方針です。

## 業務要件

このアプリは「試合中にスマホで素早く入力し、後から成績を参照・分析できること」を重視しています。

- 打撃成績入力はスマホ利用が主用途です。入力回数が多いので、画面遷移やスクロール量を増やしすぎないでください。
- 打撃成績の保存形式は `resultId1` / `resultId2` / `resultId3` が中心です。既存画面や集計への影響が大きいため、DB構造は安易に変えないでください。
- 成績表示画面の HTML は外部アプリからスクレイピングされる前提があります。表示内容だけでなく、HTML構造変更も慎重に扱ってください。
- 打順は途中出場により同じ打順が複数行になります。同一打順は上から `ranking = 1, 2, 3...` で扱います。
- 打撃成績の新規登録は衝突しやすいです。同じ試合・同じイニング・同じ打者でも、打者一巡後なら複数打席がありえます。安易に上書きせず、`inningTurn` と確認ダイアログで扱ってください。
- スプレッドシート連携は「可能な行だけ取り込む」方針です。未知の選手は `userName` として取り込み、未知の守備位置などは基本的にスキップします。
- `スコア表作成` は不要機能として削除済みです。復活させる場合は用途を再確認してください。

## 主な機能

- ユーザ登録・編集・削除
- ログイン、プロフィール管理
- 入金管理、部費残高管理
- 出金管理、出金カテゴリ管理
- 試合登録、試合詳細、スコア入力
- 打順登録、Google スプレッドシートからの打順反映
- 打撃成績入力、編集、一覧、個人成績集計
- 打撃入力画面からの走者状況管理、盗塁・進塁・走塁死入力
- 投手成績入力、編集
- 盗塁数管理
- 目安箱のメール・LINE通知

## 構成

主要な業務ロジックは `app/Services` に寄せています。Controller は HTTP 入出力、View 選択、リダイレクト制御を中心にしてください。

- `app/Http/Controllers`: コントローラー
- `app/Http/Requests`: FormRequest による入力検証
- `app/Services`: ビジネスロジック、集計、外部連携、排他制御
- `resources/views`: Blade
- `resources/css` / `resources/js`: フロントエンド
- `public/build`: 本番デプロイ用のビルド済みアセット
- `deploy`: 本番反映用スクリプト群
- `docs`: 調査・移行・UI 方針などの詳細ドキュメント

## ローカル環境構築

前提として Docker Desktop と Node.js / npm が必要です。PHP と Composer はホストではなく Sail コンテナ内で使う想定です。

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
npm install
npm run dev
```

ローカルURL:

- アプリ: `http://localhost:8080`
- Mailpit: `http://localhost:8026`
- phpMyAdmin: `http://localhost:8081`

初期ユーザーは Seeder で作成されます。

- email: `admin@example.com`
- password: `adminpassword`
- role: `10`

`.env` で `INITIAL_ADMIN_EMAIL` などを設定すれば変更できます。

## スマホ実機確認

スマホから Mac のローカル環境を見る場合は、Vite の HMR が `localhost` を向かないようにします。

`.env` 例:

```dotenv
VITE_DEV_SERVER_HOST=0.0.0.0
VITE_HMR_HOST=192.168.2.127
```

CSS/JS が効かない場合は `public/hot` を確認してください。ここに `localhost` が入っていると、スマホ側が自分自身の `localhost` にアクセスして失敗します。

## テスト

基本は Sail 上で実行します。

```bash
./vendor/bin/sail artisan test
```

特定テストだけ回す場合:

```bash
./vendor/bin/sail artisan test --filter=BattingCreateDefaultsTest
./vendor/bin/sail artisan test tests/Feature/BattingOrderControllerTest.php
```

Blade の構文確認:

```bash
./vendor/bin/sail artisan view:cache
```

フロントエンドビルド:

```bash
npm run build
```

試合詳細画面のビジュアルリグレッション:

```bash
npm run test:e2e
```

基準画像を更新する場合:

```bash
npm run test:e2e:update
```

補足:

- `tests/e2e/game-show-visual.spec.ts` が `試合詳細` と `打撃成績` セクションを desktop / mobile Chromium で比較します。
- 実行時にローカル限定ルート `/_testing/visual-regression/seed` 経由で固定フィクスチャを投入します。
- 基準画像は `tests/e2e/game-show-visual.spec.ts-snapshots/` に保存されます。
- `./vendor/bin/sail up -d` 済みであることが前提です。
- 打撃画面の走者状態は `tests/Feature/OffenseStateFlowTest.php` で保護しています。

デプロイ前の最低限チェック:

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail artisan view:cache
npm run build
npm run test:e2e
```

テスト追加時の目安:

- Controller の分岐より Service のビジネスルールを固定するテストを優先してください。
- DB を使う Feature テストは `RefreshDatabase` を使います。
- マスタデータが必要なテストは `MasterDataSeeder` を seed してください。
- Google Sheets は実APIを叩かず、`GoogleSheetsOrderImporter` を mock してください。
- 打撃成績は「新規」「重複警告」「確認後の2打席目追加」「HTML構造維持」を重点的に守ってください。

## 打撃成績入力の注意点

打撃成績登録は衝突しやすいため、`BattingStatService` で同じ試合・同じイニング・同じ打者の処理を守っています。

- MySQL では短時間の名前付きロックを使います。
- `batting_stats.inningTurn` で同一イニング内の1打席目 / 2打席目 / 3打席目を表現します。
- 既存打席がある場合でも、打者一巡後なら `inningTurn = 2, 3...` として新規追加します。
- まだそのイニングの全打者分が揃っていない段階で同じ打者を登録する場合は、誤入力防止の確認を出します。
- `confirmationResolution=duplicate` が送られた場合のみ、重複警告を越えて同一イニングの追加打席または編集移動を確定します。
- 打撃登録画面では、現在の打者・現在の塁状況と一致する入力に限って、`打点0` や打点不足が不自然なケースを警告します。
  例: 満塁で四球/死球、満塁や三塁走者ありでの安打、二三塁での二塁打、走者ありの三塁打、本塁打。
  本塁アウトなどで打点が付かない特殊ケースは `confirmationResolution=rbi` で確認後にそのまま登録できます。
- テスト環境など MySQL 以外では名前付きロックを使わず、通常の衝突検知のみ実行します。

## 走者状況管理の注意点

走塁イベントは `base_running_events` と `game_offense_states` で管理します。打撃入力画面の `走者操作` はここを使って現在のイニング、アウト数、塁上走者を再構築します。

- `game_offense_states` は現在状態のキャッシュです
- `base_running_events` は盗塁、進塁、牽制死、走塁死、手動配置などの履歴です
- 旧 `steals` テーブルの既存データは migration で `base_running_events` にバックフィルされます
- 現在のイニングと次打者は、打撃結果だけでなく走塁イベントも加味して初期表示します
- `manual_place` は牽制死や記録遅延で塁状況がズレた時の補正用です
- 走者操作も打撃登録と同じ試合同士で排他制御しています

## Google スプレッドシート打順反映

打順登録画面から Google スプレッドシートのオーダーを反映できます。

サービスアカウント JSON は Git に含めません。配置先は以下です。

```text
storage/app/private/google/ordermade-google-service-account.json
```

関連 `.env`:

```dotenv
GOOGLE_SERVICE_ACCOUNT_PATH=
GOOGLE_ORDER_SPREADSHEET_ID=
GOOGLE_ORDER_SPREADSHEET_GID=
GOOGLE_ORDER_SPREADSHEET_RANGE=B6:D20
GOOGLE_ORDER_USER_ALIASES_JSON=
```

取り込み仕様:

- `B列`: 打順
- `C列`: 守備位置
- `D列`: 選手名
- デフォルト範囲は `B6:D20`
- 下の方にある2試合目以降の行は無視します
- 一致しないユーザーはエラーにせず、登録外選手として `userName` に入れます
- 守備位置が解釈できない行はスキップします
- 選手名の表記ゆれは `GOOGLE_ORDER_USER_ALIASES_JSON` で吸収できます

例:

```json
{"シューマ":"山本崇真","やましゅー":"山本崇真"}
```

## デプロイ

通常フローは以下です。

1. ローカルで修正
2. テスト・ビルド
3. `main` に commit
4. GitHub に push
5. 本番環境で pull
6. サーバー側のデプロイスクリプトを実行

本番環境のホスト名、接続ユーザー、配置パス、具体的な接続コマンドは README には記載しません。
必要な場合は、公開されない運用メモまたはローカルの Codex skill を参照してください。

デプロイ前にローカルで確認するコマンド:

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail artisan view:cache
npm run build
```

本番反映後は、ログイン画面が正常に表示されることを最低条件として確認してください。

## Tips

- `public/build` は Git 管理対象です。本番環境では基本的にフロントエンドビルドしないため、`npm run build` 後の差分もコミットしてください。
- `.env` は絶対にコミットしないでください。共有する設定は `.env.example` に入れます。
- サーバーへ直接手動アップロードした secret は GitHub に入れないでください。
- Blade を触ったら `./vendor/bin/sail artisan view:cache` で構文確認してください。
- 画面の CSS が効かない場合は Vite dev server、`public/hot`、`public/build/manifest.json` の順で確認してください。
- `Sail is not running.` が出たら `./vendor/bin/sail up -d` してください。
- 既存画面の HTML 構造を変える場合は、スクレイピングしている外部アプリへの影響を確認してください。
- ルートやリンクは本番のサブディレクトリ配信とローカルのルート配信の両方で壊れないよう、固定パスを増やさないでください。

## 関連ドキュメント

- [AGENTS.md](./AGENTS.md)
- [docs/server-analysis.md](./docs/server-analysis.md)
- [docs/local-setup.md](./docs/local-setup.md)
- [docs/github-migration.md](./docs/github-migration.md)
- [docs/batting-input-ui.md](./docs/batting-input-ui.md)
