<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BattingCreateDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
    }

    public function test_create_screen_prefills_next_batter_and_next_inning_after_three_outs(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '先頭打者']);
        $secondBatter = User::factory()->create(['name' => '二番打者']);
        $thirdBatter = User::factory()->create(['name' => '三番打者']);
        $gameId = $this->createGame();

        DB::table('batting_orders')->insert([
            [
                'gameId' => $gameId,
                'battingOrder' => 1,
                'positionId' => 8,
                'userId' => $firstBatter->id,
                'userName' => null,
                'ranking' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'gameId' => $gameId,
                'battingOrder' => 2,
                'positionId' => 4,
                'userId' => $secondBatter->id,
                'userName' => null,
                'ranking' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'gameId' => $gameId,
                'battingOrder' => 3,
                'positionId' => 7,
                'userId' => $thirdBatter->id,
                'userName' => null,
                'ranking' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('batting_stats')->insert([
            [
                'gameId' => $gameId,
                'userId' => $firstBatter->id,
                'userName' => null,
                'inning' => 1,
                'resultId1' => 11,
                'resultId2' => 18,
                'resultId3' => 31,
                'resultId4' => null,
                'resultId5' => null,
                'created_at' => now()->subMinutes(3),
                'updated_at' => now()->subMinutes(3),
            ],
            [
                'gameId' => $gameId,
                'userId' => $secondBatter->id,
                'userName' => null,
                'inning' => 1,
                'resultId1' => 13,
                'resultId2' => 29,
                'resultId3' => 31,
                'resultId4' => null,
                'resultId5' => null,
                'created_at' => now()->subMinutes(2),
                'updated_at' => now()->subMinutes(2),
            ],
            [
                'gameId' => $gameId,
                'userId' => $thirdBatter->id,
                'userName' => null,
                'inning' => 1,
                'resultId1' => 12,
                'resultId2' => 24,
                'resultId3' => 31,
                'resultId4' => null,
                'resultId5' => null,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
        ]);

        $response = $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match('/<form[^>]*id="batting-create-form"[^>]*data-create-config=\'([^\']+)\'/u', $content, $configMatch);
        $createConfig = json_decode(html_entity_decode($configMatch[1] ?? '{}'), true);

        $this->assertMatchesRegularExpression('/<option value="' . $firstBatter->id . '" selected>/u', $content);
        $this->assertMatchesRegularExpression('/<input[^>]*id="inning"[^>]*value="2"[^>]*>/u', $content);
        $this->assertSame(3, $createConfig['inningOutCounts'][1]);
    }

    public function test_duplicate_create_shows_conflict_choice_without_overwriting_existing_stat(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '重複打者']);
        $gameId = $this->createGame();
        $this->insertBattingStat($gameId, $batter->id, 1, 11, 18, 31);

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $batter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 13,
            'resultId2' => 29,
            'resultId3' => 31,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionHas('batting_conflict', function (array $conflict): bool {
            return str_contains($conflict['message'], '既存データを更新しますか');
        });
        $response->assertSessionHasInput('resultId1', 13);

        $this->assertSame(1, DB::table('batting_stats')->where('gameId', $gameId)->count());
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $batter->id,
            'inning' => 1,
            'resultId1' => 11,
            'resultId2' => 18,
            'resultId3' => 31,
        ]);
    }

    public function test_duplicate_create_can_update_existing_stat_after_confirmation(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '更新打者']);
        $gameId = $this->createGame();
        $this->insertBattingStat($gameId, $batter->id, 1, 11, 18, 31);

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $batter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 13,
            'resultId2' => 29,
            'resultId3' => 32,
            'conflictResolution' => 'update',
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionHas('message', '打撃成績を更新しました');

        $this->assertSame(1, DB::table('batting_stats')->where('gameId', $gameId)->count());
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $batter->id,
            'inning' => 1,
            'resultId1' => 13,
            'resultId2' => 29,
            'resultId3' => 32,
        ]);
    }

    public function test_create_screen_renders_conflict_update_action(): void
    {
        $viewer = User::factory()->create();
        $gameId = $this->createGame();

        $response = $this->actingAs($viewer)
            ->withSession([
                'batting_conflict' => [
                    'statsId' => 123,
                    'message' => 'すでに打撃データが存在します。既存データを更新しますか？',
                ],
            ])
            ->get(route('batting.create', ['game' => $gameId]));

        $response->assertOk();
        $response->assertSee('同じ打者・同じイニングの成績がすでに登録されています。', false);
        $response->assertSee('現在の入力内容で更新する', false);
    }

    private function createGame(): int
    {
        return DB::table('games')->insertGetId([
            'gameName' => 'テスト試合',
            'year' => 2026,
            'gameDates' => now(),
            'enemyName' => 'テスト相手',
            'gameFirstFlg' => 0,
            'winFlg' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'gameId');
    }

    private function insertBattingStat(int $gameId, int $userId, int $inning, int $resultId1, int $resultId2, int $resultId3): void
    {
        DB::table('batting_stats')->insert([
            'gameId' => $gameId,
            'userId' => $userId,
            'userName' => null,
            'inning' => $inning,
            'resultId1' => $resultId1,
            'resultId2' => $resultId2,
            'resultId3' => $resultId3,
            'resultId4' => null,
            'resultId5' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
