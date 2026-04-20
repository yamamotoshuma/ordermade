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
}
