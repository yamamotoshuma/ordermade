<?php

namespace App\Console\Commands;

use Database\Seeders\MasterDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedVisualRegressionFixture extends Command
{
    private const LOGIN_USER_ID = 990001;

    private const GAME_ID = 990001;

    /**
     * 試合詳細の見た目確認に使う固定ログイン情報。
     */
    private const LOGIN_EMAIL = 'visual-regression@example.com';

    private const LOGIN_PASSWORD = 'password';

    protected $signature = 'visual-regression:seed';

    protected $description = '試合詳細画面のE2E・ビジュアルリグレッション用フィクスチャを投入する';

    public function handle(): int
    {
        DB::transaction(function (): void {
            // テスト用DBでも本番寄せのマスタ値を前提に描画する。
            app(MasterDataSeeder::class)->run();

            $this->upsertUsers();
            $this->recreateFixtureGame();
        });

        $this->info('ビジュアルリグレッション用フィクスチャを投入しました。');
        $this->line('login: ' . self::LOGIN_EMAIL . ' / ' . self::LOGIN_PASSWORD);
        $this->line('game: ' . self::GAME_ID);

        return self::SUCCESS;
    }

    /**
     * ログイン用管理者と試合表示用の選手を固定IDで揃える。
     */
    private function upsertUsers(): void
    {
        $timestamp = now();

        DB::table('users')->upsert([
            [
                'id' => self::LOGIN_USER_ID,
                'name' => 'E2E管理者',
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
                'id' => 990011,
                'name' => '佐藤一郎',
                'email' => 'visual-player-01@example.com',
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 1,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 990012,
                'name' => '鈴木次郎',
                'email' => 'visual-player-02@example.com',
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 1,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 990013,
                'name' => '高橋三郎',
                'email' => 'visual-player-03@example.com',
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 1,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 990014,
                'name' => '田中四郎',
                'email' => 'visual-player-04@example.com',
                'email_verified_at' => $timestamp,
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 1,
                'active_flg' => 1,
                'remember_token' => Str::random(10),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 990015,
                'name' => '渡辺五郎',
                'email' => 'visual-player-05@example.com',
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
     * 試合単位で関連データを入れ直し、毎回同じ見た目を再現できるようにする。
     */
    private function recreateFixtureGame(): void
    {
        DB::table('base_running_events')->whereBetween('id', [990001, 990999])->delete();
        DB::table('pitching_stats')->whereBetween('id', [990001, 990999])->delete();
        DB::table('steals')->whereBetween('id', [990001, 990999])->delete();
        DB::table('batting_stats')->whereBetween('id', [990001, 990999])->delete();
        DB::table('batting_orders')->whereBetween('orderId', [990001, 990999])->delete();
        DB::table('points')->whereBetween('pointId', [990001, 990999])->delete();
        DB::table('game_offense_states')->where('gameId', self::GAME_ID)->delete();
        DB::table('base_running_events')->where('gameId', self::GAME_ID)->delete();
        DB::table('pitching_stats')->where('gameId', self::GAME_ID)->delete();
        DB::table('steals')->where('gameId', self::GAME_ID)->delete();
        DB::table('batting_stats')->where('gameId', self::GAME_ID)->delete();
        DB::table('batting_orders')->where('gameId', self::GAME_ID)->delete();
        DB::table('points')->where('gameId', self::GAME_ID)->delete();
        DB::table('games')->where('gameId', self::GAME_ID)->delete();

        DB::table('games')->insert([
            'gameId' => self::GAME_ID,
            'gameName' => 'E2E確認用試合',
            'year' => 2026,
            'gameDates' => '2026-04-01 10:00:00',
            'enemyName' => 'テストベアーズ',
            'gameFirstFlg' => 0,
            'winFlg' => 0,
            'created_at' => '2026-04-01 09:00:00',
            'updated_at' => '2026-04-01 09:00:00',
        ]);

        DB::table('points')->insert([
            ['pointId' => 990001, 'gameId' => self::GAME_ID, 'inning' => 1, 'inning_side' => 0, 'score' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['pointId' => 990002, 'gameId' => self::GAME_ID, 'inning' => 1, 'inning_side' => 1, 'score' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['pointId' => 990003, 'gameId' => self::GAME_ID, 'inning' => 2, 'inning_side' => 0, 'score' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['pointId' => 990004, 'gameId' => self::GAME_ID, 'inning' => 2, 'inning_side' => 1, 'score' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['pointId' => 990005, 'gameId' => self::GAME_ID, 'inning' => 3, 'inning_side' => 0, 'score' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['pointId' => 990006, 'gameId' => self::GAME_ID, 'inning' => 3, 'inning_side' => 1, 'score' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('batting_orders')->insert([
            ['orderId' => 990001, 'gameId' => self::GAME_ID, 'battingOrder' => 1, 'positionId' => 8, 'userId' => 990011, 'userName' => null, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['orderId' => 990002, 'gameId' => self::GAME_ID, 'battingOrder' => 2, 'positionId' => 4, 'userId' => 990012, 'userName' => null, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['orderId' => 990003, 'gameId' => self::GAME_ID, 'battingOrder' => 3, 'positionId' => 6, 'userId' => 990013, 'userName' => null, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['orderId' => 990004, 'gameId' => self::GAME_ID, 'battingOrder' => 4, 'positionId' => 3, 'userId' => 990014, 'userName' => null, 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['orderId' => 990005, 'gameId' => self::GAME_ID, 'battingOrder' => 5, 'positionId' => 7, 'userId' => null, 'userName' => '助っ人五郎', 'ranking' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['orderId' => 990006, 'gameId' => self::GAME_ID, 'battingOrder' => 5, 'positionId' => 9, 'userId' => 990015, 'userName' => null, 'ranking' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('batting_stats')->insert([
            ['id' => 990001, 'gameId' => self::GAME_ID, 'userId' => 990011, 'userName' => null, 'inning' => 1, 'inningTurn' => 1, 'resultId1' => 1, 'resultId2' => 24, 'resultId3' => 31, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:01:00', 'updated_at' => '2026-04-01 10:01:00'],
            ['id' => 990002, 'gameId' => self::GAME_ID, 'userId' => 990012, 'userName' => null, 'inning' => 1, 'inningTurn' => 1, 'resultId1' => 5, 'resultId2' => 36, 'resultId3' => 31, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:03:00', 'updated_at' => '2026-04-01 10:03:00'],
            ['id' => 990003, 'gameId' => self::GAME_ID, 'userId' => 990013, 'userName' => null, 'inning' => 1, 'inningTurn' => 1, 'resultId1' => 12, 'resultId2' => 25, 'resultId3' => 31, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:05:00', 'updated_at' => '2026-04-01 10:05:00'],
            ['id' => 990004, 'gameId' => self::GAME_ID, 'userId' => null, 'userName' => '助っ人五郎', 'inning' => 1, 'inningTurn' => 1, 'resultId1' => 2, 'resultId2' => 26, 'resultId3' => 32, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:08:00', 'updated_at' => '2026-04-01 10:08:00'],
            ['id' => 990005, 'gameId' => self::GAME_ID, 'userId' => 990014, 'userName' => null, 'inning' => 2, 'inningTurn' => 1, 'resultId1' => 13, 'resultId2' => 29, 'resultId3' => 31, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:14:00', 'updated_at' => '2026-04-01 10:14:00'],
            ['id' => 990006, 'gameId' => self::GAME_ID, 'userId' => 990015, 'userName' => null, 'inning' => 2, 'inningTurn' => 1, 'resultId1' => 4, 'resultId2' => 25, 'resultId3' => 33, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:18:00', 'updated_at' => '2026-04-01 10:18:00'],
            ['id' => 990007, 'gameId' => self::GAME_ID, 'userId' => 990011, 'userName' => null, 'inning' => 3, 'inningTurn' => 1, 'resultId1' => 9, 'resultId2' => 22, 'resultId3' => 31, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:24:00', 'updated_at' => '2026-04-01 10:24:00'],
            ['id' => 990008, 'gameId' => self::GAME_ID, 'userId' => 990012, 'userName' => null, 'inning' => 3, 'inningTurn' => 1, 'resultId1' => 8, 'resultId2' => 25, 'resultId3' => 32, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:26:00', 'updated_at' => '2026-04-01 10:26:00'],
            ['id' => 990009, 'gameId' => self::GAME_ID, 'userId' => 990011, 'userName' => null, 'inning' => 3, 'inningTurn' => 2, 'resultId1' => 13, 'resultId2' => 29, 'resultId3' => 31, 'resultId4' => null, 'resultId5' => null, 'created_at' => '2026-04-01 10:29:00', 'updated_at' => '2026-04-01 10:29:00'],
        ]);

        DB::table('steals')->insert([
            ['id' => 990001, 'gameId' => self::GAME_ID, 'userId' => 990012, 'created_at' => '2026-04-01 10:04:00', 'updated_at' => '2026-04-01 10:04:00'],
            ['id' => 990002, 'gameId' => self::GAME_ID, 'userId' => 990015, 'created_at' => '2026-04-01 10:19:00', 'updated_at' => '2026-04-01 10:19:00'],
        ]);

        DB::table('base_running_events')->insert([
            [
                'id' => 990001,
                'gameId' => self::GAME_ID,
                'inning' => 1,
                'actorOrderId' => 990002,
                'actorUserId' => 990012,
                'actorUserName' => null,
                'startBase' => 1,
                'endBase' => 2,
                'eventType' => 'stolen_base',
                'outsRecorded' => 0,
                'affectsState' => true,
                'stateVersion' => 2,
                'createdBy' => self::LOGIN_USER_ID,
                'meta' => json_encode(['source' => 'visual_fixture'], JSON_UNESCAPED_UNICODE),
                'created_at' => '2026-04-01 10:04:00',
                'updated_at' => '2026-04-01 10:04:00',
            ],
            [
                'id' => 990002,
                'gameId' => self::GAME_ID,
                'inning' => 2,
                'actorOrderId' => 990006,
                'actorUserId' => 990015,
                'actorUserName' => null,
                'startBase' => 3,
                'endBase' => 4,
                'eventType' => 'stolen_base',
                'outsRecorded' => 0,
                'affectsState' => true,
                'stateVersion' => 6,
                'createdBy' => self::LOGIN_USER_ID,
                'meta' => json_encode(['source' => 'visual_fixture'], JSON_UNESCAPED_UNICODE),
                'created_at' => '2026-04-01 10:19:00',
                'updated_at' => '2026-04-01 10:19:00',
            ],
        ]);

        DB::table('pitching_stats')->insert([
            [
                'id' => 990001,
                'gameId' => self::GAME_ID,
                'userId' => 990011,
                'pitchingOrder' => 1,
                'result' => '勝',
                'save' => 0,
                'inning' => 3.0,
                'hitsAllowed' => 3,
                'homeRunsAllowed' => 0,
                'strikeouts' => 4,
                'walks' => 1,
                'wildPitches' => 0,
                'balks' => 0,
                'runsAllowed' => 1,
                'earnedRuns' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
