<?php

namespace App\Console\Commands;

use Database\Seeders\MasterDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedBattingFlowFixture extends Command
{
    public const LOGIN_USER_ID = 991001;

    public const LOGIN_EMAIL = 'e2e-batting@example.com';

    public const LOGIN_PASSWORD = 'password';

    public const RBI_WARNING_GAME_ID = 991101;

    public const DUPLICATE_WARNING_GAME_ID = 991102;

    public const RUNNER_FLOW_GAME_ID = 991103;

    protected $signature = 'testing:seed-batting-flow-fixture';

    protected $description = '打撃登録E2E用の固定フィクスチャを投入する';

    public function handle(): int
    {
        DB::transaction(function (): void {
            app(MasterDataSeeder::class)->run();
            $this->upsertUsers();
            $this->recreateGames();
        });

        $this->info('打撃登録E2E用フィクスチャを投入しました。');
        $this->line('login: ' . self::LOGIN_EMAIL . ' / ' . self::LOGIN_PASSWORD);

        return self::SUCCESS;
    }

    /**
     * ログイン用管理者と打順用選手を固定IDで揃える。
     */
    private function upsertUsers(): void
    {
        $timestamp = now();

        DB::table('users')->upsert([
            [
                'id' => self::LOGIN_USER_ID,
                'name' => '打撃E2E管理者',
                'email' => self::LOGIN_EMAIL,
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 10,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 991011,
                'name' => '一番太郎',
                'email' => 'batting-e2e-player-01@example.com',
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 1,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 991012,
                'name' => '二番次郎',
                'email' => 'batting-e2e-player-02@example.com',
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 1,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 991013,
                'name' => '三番三郎',
                'email' => 'batting-e2e-player-03@example.com',
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 1,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 991014,
                'name' => '四番四郎',
                'email' => 'batting-e2e-player-04@example.com',
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 1,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['id'], ['name', 'email', 'email_verified_at', 'password', 'role', 'active_flg', 'remember_token', 'updated_at']);
    }

    /**
     * E2Eで使う試合を毎回固定状態へ戻す。
     */
    private function recreateGames(): void
    {
        $gameIds = [
            self::RBI_WARNING_GAME_ID,
            self::DUPLICATE_WARNING_GAME_ID,
            self::RUNNER_FLOW_GAME_ID,
        ];

        DB::table('game_offense_states')->whereIn('gameId', $gameIds)->delete();
        DB::table('base_running_events')->whereIn('gameId', $gameIds)->delete();
        DB::table('steals')->whereIn('gameId', $gameIds)->delete();
        DB::table('batting_stats')->whereIn('gameId', $gameIds)->delete();
        DB::table('batting_orders')->whereIn('gameId', $gameIds)->delete();
        DB::table('points')->whereIn('gameId', $gameIds)->delete();
        DB::table('games')->whereIn('gameId', $gameIds)->delete();

        $this->createRbiWarningGame();
        $this->createDuplicateWarningGame();
        $this->createRunnerFlowGame();
    }

    /**
     * 満塁で打点警告が出る試合。
     */
    private function createRbiWarningGame(): void
    {
        $this->insertGame(self::RBI_WARNING_GAME_ID, 'E2E打点警告試合');
        $this->insertOrders(self::RBI_WARNING_GAME_ID);

        DB::table('batting_stats')->insert([
            $this->makeBattingStat(991101, self::RBI_WARNING_GAME_ID, 991011, 1, 5, 36, 31, '2026-04-25 09:01:00'),
            $this->makeBattingStat(991102, self::RBI_WARNING_GAME_ID, 991012, 1, 5, 36, 31, '2026-04-25 09:02:00'),
            $this->makeBattingStat(991103, self::RBI_WARNING_GAME_ID, 991013, 1, 5, 36, 31, '2026-04-25 09:03:00'),
        ]);
    }

    /**
     * 同一イニング重複打席の確認が出る試合。
     */
    private function createDuplicateWarningGame(): void
    {
        $this->insertGame(self::DUPLICATE_WARNING_GAME_ID, 'E2E重複確認試合');
        $this->insertOrders(self::DUPLICATE_WARNING_GAME_ID);

        DB::table('batting_stats')->insert([
            $this->makeBattingStat(991201, self::DUPLICATE_WARNING_GAME_ID, 991011, 1, 1, 24, 31, '2026-04-25 10:01:00'),
        ]);
    }

    /**
     * 重盗と取り消しを確認する試合。
     */
    private function createRunnerFlowGame(): void
    {
        $this->insertGame(self::RUNNER_FLOW_GAME_ID, 'E2E走者操作試合');
        $this->insertOrders(self::RUNNER_FLOW_GAME_ID);

        DB::table('batting_stats')->insert([
            $this->makeBattingStat(991301, self::RUNNER_FLOW_GAME_ID, 991011, 1, 5, 36, 31, '2026-04-25 11:01:00'),
            $this->makeBattingStat(991302, self::RUNNER_FLOW_GAME_ID, 991012, 1, 5, 36, 31, '2026-04-25 11:02:00'),
        ]);
    }

    /**
     * 固定の打順を投入する。
     */
    private function insertOrders(int $gameId): void
    {
        $baseOrderId = match ($gameId) {
            self::RBI_WARNING_GAME_ID => 991111,
            self::DUPLICATE_WARNING_GAME_ID => 991211,
            self::RUNNER_FLOW_GAME_ID => 991311,
        };

        DB::table('batting_orders')->insert([
            ['orderId' => $baseOrderId, 'gameId' => $gameId, 'battingOrder' => 1, 'positionId' => 8, 'userId' => 991011, 'userName' => null, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['orderId' => $baseOrderId + 1, 'gameId' => $gameId, 'battingOrder' => 2, 'positionId' => 4, 'userId' => 991012, 'userName' => null, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['orderId' => $baseOrderId + 2, 'gameId' => $gameId, 'battingOrder' => 3, 'positionId' => 6, 'userId' => 991013, 'userName' => null, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['orderId' => $baseOrderId + 3, 'gameId' => $gameId, 'battingOrder' => 4, 'positionId' => 3, 'userId' => 991014, 'userName' => null, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * 固定の試合行を作る。
     */
    private function insertGame(int $gameId, string $gameName): void
    {
        DB::table('games')->insert([
            'gameId' => $gameId,
            'gameName' => $gameName,
            'year' => 2026,
            'gameDates' => '2026-04-25 09:00:00',
            'enemyName' => 'E2E相手',
            'gameFirstFlg' => 0,
            'winFlg' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 打撃成績投入用の配列を作る。
     */
    private function makeBattingStat(int $id, int $gameId, int $userId, int $inning, int $resultId1, int $resultId2, int $resultId3, string $createdAt): array
    {
        return [
            'id' => $id,
            'gameId' => $gameId,
            'userId' => $userId,
            'userName' => null,
            'inning' => $inning,
            'inningTurn' => 1,
            'resultId1' => $resultId1,
            'resultId2' => $resultId2,
            'resultId3' => $resultId3,
            'resultId4' => null,
            'resultId5' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
