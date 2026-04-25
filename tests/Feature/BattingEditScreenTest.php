<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsBattingTestData;
use Tests\TestCase;

class BattingEditScreenTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBattingTestData;

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

    public function test_edit_duplicate_update_requires_confirmation_when_inning_is_not_complete(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一番']);
        $secondBatter = User::factory()->create(['name' => '二番']);
        $thirdBatter = User::factory()->create(['name' => '三番']);
        $gameId = $this->createGame();
        $this->insertOrder($gameId, 1, $firstBatter->id);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 7);
        $firstStatId = $this->insertBattingStat($gameId, $firstBatter->id);
        $secondStatId = $this->insertBattingStat($gameId, $secondBatter->id);

        $response = $this->actingAs($viewer)->post(route('batting.update', ['batting' => $secondStatId]), [
            'userId' => $firstBatter->id,
            'inning' => 1,
            'resultId1' => 5,
            'resultId2' => 36,
            'resultId3' => 31,
        ]);

        $response->assertRedirect(route('batting.edit', ['batting' => $secondStatId]));
        $this->assertBattingConfirmation($response, 'duplicate', null, '誤入力の可能性があります');
        $this->assertDatabaseHas('batting_stats', [
            'id' => $firstStatId,
            'userId' => $firstBatter->id,
            'inning' => 1,
            'inningTurn' => 1,
        ]);
        $this->assertDatabaseHas('batting_stats', [
            'id' => $secondStatId,
            'userId' => $secondBatter->id,
            'inning' => 1,
            'inningTurn' => 1,
        ]);
    }

    public function test_edit_duplicate_update_can_move_stat_to_second_plate_appearance_after_confirmation(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '確認一番']);
        $secondBatter = User::factory()->create(['name' => '確認二番']);
        $thirdBatter = User::factory()->create(['name' => '確認三番']);
        $gameId = $this->createGame();
        $this->insertOrder($gameId, 1, $firstBatter->id);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 7);
        $firstStatId = $this->insertBattingStat($gameId, $firstBatter->id);
        $secondStatId = $this->insertBattingStat($gameId, $secondBatter->id);

        $response = $this->actingAs($viewer)->post(route('batting.update', ['batting' => $secondStatId]), [
            'userId' => $firstBatter->id,
            'inning' => 1,
            'resultId1' => 5,
            'resultId2' => 36,
            'resultId3' => 31,
            'confirmationResolution' => 'duplicate',
        ]);

        $response->assertRedirect(route('batting.edit', ['batting' => $secondStatId]));
        $this->assertDatabaseHas('batting_stats', [
            'id' => $firstStatId,
            'userId' => $firstBatter->id,
            'inning' => 1,
            'inningTurn' => 1,
        ]);
        $this->assertDatabaseHas('batting_stats', [
            'id' => $secondStatId,
            'userId' => $firstBatter->id,
            'inning' => 1,
            'inningTurn' => 2,
            'resultId1' => 5,
            'resultId2' => 36,
            'resultId3' => 31,
        ]);
    }

    public function test_edit_duplicate_update_confirmation_can_restore_pending_payload_without_visible_form_fields(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '確認一番']);
        $secondBatter = User::factory()->create(['name' => '確認二番']);
        $thirdBatter = User::factory()->create(['name' => '確認三番']);
        $gameId = $this->createGame();
        $this->insertOrder($gameId, 1, $firstBatter->id);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 7);
        $firstStatId = $this->insertBattingStat($gameId, $firstBatter->id);
        $secondStatId = $this->insertBattingStat($gameId, $secondBatter->id);

        $response = $this->actingAs($viewer)->post(route('batting.update', ['batting' => $secondStatId]), [
            'userId' => $firstBatter->id,
            'inning' => 1,
            'resultId1' => 5,
            'resultId2' => 36,
            'resultId3' => 31,
        ]);

        $response->assertRedirect(route('batting.edit', ['batting' => $secondStatId]));
        $response->assertSessionHas('batting_confirmation');

        $confirmResponse = $this->actingAs($viewer)->post(route('batting.update', ['batting' => $secondStatId]), [
            'confirmationResolution' => 'duplicate',
        ]);

        $confirmResponse->assertRedirect(route('batting.edit', ['batting' => $secondStatId]));
        $this->assertDatabaseHas('batting_stats', [
            'id' => $firstStatId,
            'userId' => $firstBatter->id,
            'inning' => 1,
            'inningTurn' => 1,
        ]);
        $this->assertDatabaseHas('batting_stats', [
            'id' => $secondStatId,
            'userId' => $firstBatter->id,
            'inning' => 1,
            'inningTurn' => 2,
            'resultId1' => 5,
            'resultId2' => 36,
            'resultId3' => 31,
        ]);
    }

    public function test_destroy_renumbers_remaining_plate_appearances(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '連続打席打者']);
        $gameId = $this->createGame();
        $firstStatId = $this->insertBattingStat($gameId, $batter->id, 1, 1, 24, 32, 1);
        $secondStatId = $this->insertBattingStat($gameId, $batter->id, 1, 5, 36, 31, 2);

        $response = $this->actingAs($viewer)->delete(route('batting.destroy', ['batting' => $firstStatId]));

        $response->assertRedirect(route('batting.index', ['game' => $gameId]));
        $this->assertDatabaseMissing('batting_stats', ['id' => $firstStatId]);
        $this->assertDatabaseHas('batting_stats', [
            'id' => $secondStatId,
            'inningTurn' => 1,
        ]);
    }

}
