<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BattingEditScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
    }

    public function test_edit_screen_uses_fixed_action_bar_for_update_and_delete(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '編集対象打者']);
        $gameId = $this->createGame();
        $battingStatId = $this->insertBattingStat($gameId, $batter->id);

        $response = $this->actingAs($viewer)->get(route('batting.edit', ['batting' => $battingStatId]));

        $response->assertOk();
        $response->assertSee('id="batting-edit-form"', false);
        $response->assertSee('id="batting-delete-form"', false);
        $response->assertSee('id="batting-edit-action-bar"', false);
        $response->assertSee('id="batting-edit-meta-panel"', false);
        $response->assertSee('id="batterSelect"', false);
        $response->assertSee('data-role="inning-decrement"', false);
        $response->assertSee('data-role="inning-increment"', false);
        $response->assertSee('form="batting-edit-form"', false);
        $response->assertSee('form="batting-delete-form"', false);
        $response->assertSee('return confirm(\'削除してもよろしいですか？\');', false);
        $response->assertSee('更新する', false);
        $response->assertSee('削除', false);
        $response->assertSee('1回 / 編集対象打者 / 左安打 / 打点 1', false);
        $response->assertDontSee('<details', false);
        $response->assertDontSee('id="userDisplay"', false);
        $this->assertMatchesRegularExpression('/<select[^>]*id="batterSelect"[\s\S]*data-user-id="' . $batter->id . '"[\s\S]*selected/u', $response->getContent());
    }

    public function test_edit_screen_from_latest_card_returns_to_create_after_update(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '直前修正打者']);
        $gameId = $this->createGame();
        $battingStatId = $this->insertBattingStat($gameId, $batter->id);

        $screen = $this->actingAs($viewer)->get(route('batting.edit', [
            'batting' => $battingStatId,
            'returnTo' => 'create',
        ]));

        $screen->assertOk();
        $screen->assertSee('登録画面に戻る', false);
        $screen->assertSee('name="returnTo" value="create"', false);
        $screen->assertSee('更新して戻る', false);

        $response = $this->actingAs($viewer)->post(route('batting.update', ['batting' => $battingStatId]), [
            'userId' => $batter->id,
            'inning' => 1,
            'resultId1' => 2,
            'resultId2' => 27,
            'resultId3' => 33,
            'returnTo' => 'create',
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionMissing('message');
        $response->assertSessionHas('last_batting_stat_id', $battingStatId);
        $this->assertDatabaseHas('batting_stats', [
            'id' => $battingStatId,
            'resultId1' => 2,
            'resultId2' => 27,
            'resultId3' => 33,
        ]);
    }

    public function test_edit_screen_can_update_batter_from_order_user_name_option(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '元打者']);
        $gameId = $this->createGame();
        $battingStatId = $this->insertBattingStat($gameId, $batter->id);

        DB::table('batting_orders')->insert([
            [
                'gameId' => $gameId,
                'battingOrder' => 9,
                'positionId' => 7,
                'userId' => null,
                'userName' => '代打助っ人',
                'ranking' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $screen = $this->actingAs($viewer)->get(route('batting.edit', ['batting' => $battingStatId]));

        $screen->assertOk();
        $screen->assertSee('9番 代打助っ人', false);
        $screen->assertSee('data-user-name="代打助っ人"', false);

        $response = $this->actingAs($viewer)->post(route('batting.update', ['batting' => $battingStatId]), [
            'userId' => null,
            'userName' => '代打助っ人',
            'inning' => 2,
            'resultId1' => 5,
            'resultId2' => 36,
            'resultId3' => 31,
        ]);

        $response->assertRedirect(route('batting.edit', ['batting' => $battingStatId]));
        $this->assertDatabaseHas('batting_stats', [
            'id' => $battingStatId,
            'userId' => null,
            'userName' => '代打助っ人',
            'inning' => 2,
            'resultId1' => 5,
            'resultId2' => 36,
            'resultId3' => 31,
        ]);
    }

    private function createGame(): int
    {
        return DB::table('games')->insertGetId([
            'gameName' => '編集テスト試合',
            'year' => 2026,
            'gameDates' => now(),
            'enemyName' => '編集テスト相手',
            'gameFirstFlg' => 0,
            'winFlg' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'gameId');
    }

    private function insertBattingStat(int $gameId, int $userId): int
    {
        return DB::table('batting_stats')->insertGetId([
            'gameId' => $gameId,
            'userId' => $userId,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 32,
            'resultId4' => null,
            'resultId5' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
