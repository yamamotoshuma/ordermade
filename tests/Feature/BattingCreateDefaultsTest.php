<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsBattingTestData;
use Tests\TestCase;

class BattingCreateDefaultsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBattingTestData;

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

        $this->assertMatchesRegularExpression('/<select[^>]*id="batterSelect"[\s\S]*data-user-id="' . $firstBatter->id . '"[\s\S]*selected/u', $content);
        $this->assertMatchesRegularExpression('/<input[^>]*name="userId"[^>]*id="userId"[^>]*value="' . $firstBatter->id . '"[^>]*>/u', $content);
        $this->assertMatchesRegularExpression('/<input[^>]*id="inning"[^>]*value="2"[^>]*>/u', $content);
        $this->assertSame(3, $createConfig['inningOutCounts'][1]);
        $response->assertSee('id="batting-meta-panel"', false);
        $response->assertSee('id="batterSelect"', false);
        $response->assertSee('data-role="inning-decrement"', false);
        $response->assertSee('data-role="inning-increment"', false);
        $response->assertDontSee('<details id="batting-meta-panel"', false);
        $response->assertDontSee('登録外打者', false);
    }

    public function test_create_screen_uses_batting_order_user_name_in_same_batter_select(): void
    {
        $viewer = User::factory()->create();
        $gameId = $this->createGame();

        DB::table('batting_orders')->insert([
            [
                'gameId' => $gameId,
                'battingOrder' => 8,
                'positionId' => 7,
                'userId' => null,
                'userName' => '助っ人太郎',
                'ranking' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($viewer)->get(route('batting.create', [
            'game' => $gameId,
            'userName' => '助っ人太郎',
        ]));

        $response->assertOk();
        $content = $response->getContent();

        $response->assertSee('8番 助っ人太郎', false);
        $response->assertSee('data-user-name="助っ人太郎"', false);
        $this->assertMatchesRegularExpression('/<select[^>]*id="batterSelect"[\s\S]*data-user-name="助っ人太郎"[\s\S]*selected/u', $content);
        $this->assertMatchesRegularExpression('/<input[^>]*name="userName"[^>]*id="userName"[^>]*value="助っ人太郎"[^>]*>/u', $content);
        $response->assertDontSee('登録外打者', false);
    }

    public function test_duplicate_create_shows_warning_without_creating_second_plate_appearance_when_inning_is_not_complete(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '重複打者']);
        $otherBatter = User::factory()->create(['name' => '別打者']);
        $gameId = $this->createGame();
        $this->insertOrder($gameId, 1, $batter->id);
        $this->insertOrder($gameId, 2, $otherBatter->id, 4);
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
        $this->assertBattingConfirmation($response, 'duplicate', null, '誤入力の可能性があります');
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

    public function test_duplicate_create_can_add_second_plate_appearance_after_confirmation(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '更新打者']);
        $otherBatter = User::factory()->create(['name' => '他打者']);
        $gameId = $this->createGame();
        $this->insertOrder($gameId, 1, $batter->id);
        $this->insertOrder($gameId, 2, $otherBatter->id, 4);
        $this->insertBattingStat($gameId, $batter->id, 1, 11, 18, 31);

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $batter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 13,
            'resultId2' => 29,
            'resultId3' => 32,
            'confirmationResolution' => 'duplicate',
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionMissing('message');
        $response->assertSessionHas('last_batting_stat_id');

        $this->assertSame(2, DB::table('batting_stats')->where('gameId', $gameId)->count());
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $batter->id,
            'inning' => 1,
            'resultId1' => 13,
            'resultId2' => 29,
            'resultId3' => 32,
            'inningTurn' => 2,
        ]);
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $batter->id,
            'inning' => 1,
            'resultId1' => 11,
            'resultId2' => 18,
            'resultId3' => 31,
            'inningTurn' => 1,
        ]);
    }

    public function test_duplicate_create_confirmation_can_restore_pending_payload_without_visible_form_fields(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '再送打者']);
        $otherBatter = User::factory()->create(['name' => '他打者']);
        $gameId = $this->createGame();
        $this->insertOrder($gameId, 1, $batter->id);
        $this->insertOrder($gameId, 2, $otherBatter->id, 4);
        $this->insertBattingStat($gameId, $batter->id, 1, 11, 18, 31);

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $batter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 13,
            'resultId2' => 29,
            'resultId3' => 32,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionHas('batting_confirmation');

        $confirmResponse = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'confirmationResolution' => 'duplicate',
        ]);

        $confirmResponse->assertRedirect(route('batting.create', ['game' => $gameId]));
        $confirmResponse->assertSessionHas('last_batting_stat_id');
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $batter->id,
            'inning' => 1,
            'inningTurn' => 2,
            'resultId1' => 13,
            'resultId2' => 29,
            'resultId3' => 32,
        ]);
    }

    public function test_duplicate_create_auto_adds_second_plate_appearance_after_full_cycle(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一巡一番']);
        $secondBatter = User::factory()->create(['name' => '一巡二番']);
        $gameId = $this->createGame();
        $this->insertOrder($gameId, 1, $firstBatter->id);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertBattingStat($gameId, $firstBatter->id, 1, 11, 18, 31);
        $this->insertBattingStat($gameId, $secondBatter->id, 1, 13, 29, 31);

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $firstBatter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 32,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionMissing('batting_confirmation');
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $firstBatter->id,
            'inning' => 1,
            'inningTurn' => 2,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 32,
        ]);
    }

    public function test_create_warns_when_current_bases_loaded_hit_has_zero_rbi(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一番']);
        $secondBatter = User::factory()->create(['name' => '二番']);
        $thirdBatter = User::factory()->create(['name' => '三番']);
        $fourthBatter = User::factory()->create(['name' => '四番']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, $firstBatter->id);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 7);
        $this->insertOrder($gameId, 4, $fourthBatter->id, 3);

        $this->insertBattingStat($gameId, $firstBatter->id, 1, 5, 36, 31);
        $this->insertBattingStat($gameId, $secondBatter->id, 1, 5, 36, 31);
        $this->insertBattingStat($gameId, $thirdBatter->id, 1, 5, 36, 31);

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $fourthBatter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 31,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $this->assertBattingConfirmation($response, 'rbi', '打点', '満塁');
        $this->assertSessionContains($response, 'batting_confirmation.message', '0打点');
        $this->assertSame(3, DB::table('batting_stats')->where('gameId', $gameId)->count());
    }

    public function test_create_can_continue_after_rbi_warning_confirmation(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一番']);
        $secondBatter = User::factory()->create(['name' => '二番']);
        $thirdBatter = User::factory()->create(['name' => '三番']);
        $fourthBatter = User::factory()->create(['name' => '四番']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, $firstBatter->id);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 7);
        $this->insertOrder($gameId, 4, $fourthBatter->id, 3);

        $this->insertBattingStat($gameId, $firstBatter->id, 1, 5, 36, 31);
        $this->insertBattingStat($gameId, $secondBatter->id, 1, 5, 36, 31);
        $this->insertBattingStat($gameId, $thirdBatter->id, 1, 5, 36, 31);

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $fourthBatter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 31,
            'confirmationResolution' => 'rbi',
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionHas('last_batting_stat_id');
        $response->assertSessionMissing('batting_confirmation');
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $fourthBatter->id,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 31,
        ]);
    }

    public function test_rbi_warning_confirmation_can_restore_pending_payload_without_visible_form_fields(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一番']);
        $secondBatter = User::factory()->create(['name' => '二番']);
        $thirdBatter = User::factory()->create(['name' => '三番']);
        $fourthBatter = User::factory()->create(['name' => '四番']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, $firstBatter->id);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 7);
        $this->insertOrder($gameId, 4, $fourthBatter->id, 3);

        $this->insertBattingStat($gameId, $firstBatter->id, 1, 5, 36, 31);
        $this->insertBattingStat($gameId, $secondBatter->id, 1, 5, 36, 31);
        $this->insertBattingStat($gameId, $thirdBatter->id, 1, 5, 36, 31);

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $fourthBatter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 31,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionHas('batting_confirmation');

        $confirmResponse = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'confirmationResolution' => 'rbi',
        ]);

        $confirmResponse->assertRedirect(route('batting.create', ['game' => $gameId]));
        $confirmResponse->assertSessionHas('last_batting_stat_id');
        $confirmResponse->assertSessionMissing('error');
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $fourthBatter->id,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 31,
        ]);
    }

    public function test_store_can_resolve_batter_from_selected_order_id_even_if_hidden_inputs_are_empty(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '打順解決打者']);
        $gameId = $this->createGame();
        $orderId = DB::table('batting_orders')->insertGetId([
            'gameId' => $gameId,
            'battingOrder' => 1,
            'positionId' => 8,
            'userId' => $batter->id,
            'userName' => null,
            'ranking' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'orderId');

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'selectedOrderId' => $orderId,
            'userId' => '',
            'userName' => '',
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 31,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $this->assertDatabaseHas('batting_stats', [
            'gameId' => $gameId,
            'userId' => $batter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 31,
        ]);
    }

    public function test_create_screen_renders_duplicate_create_action(): void
    {
        $viewer = User::factory()->create();
        $gameId = $this->createGame();

        $response = $this->actingAs($viewer)
            ->withSession([
                'batting_confirmation' => [
                    'title' => '同じ打者・同じイニングの打撃がすでに登録されています。',
                    'message' => 'まだ全打者分が入力されていません。このまま追加登録しますか？',
                    'resolution' => 'duplicate',
                ],
            ])
            ->get(route('batting.create', ['game' => $gameId]));

        $response->assertOk();
        $response->assertSee('同じ打者・同じイニングの打撃がすでに登録されています。', false);
        $response->assertSee('このまま追加する', false);
    }

    public function test_successful_create_renders_latest_input_card_and_fixed_submit_bar(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '直前入力打者']);
        $gameId = $this->createGame();

        $response = $this->actingAs($viewer)->post(route('batting.store', ['game' => $gameId]), [
            'userId' => $batter->id,
            'userName' => null,
            'inning' => 1,
            'resultId1' => 1,
            'resultId2' => 24,
            'resultId3' => 32,
        ]);

        $latestStatId = DB::table('batting_stats')->where('gameId', $gameId)->value('id');

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionHas('last_batting_stat_id', $latestStatId);
        $response->assertSessionMissing('message');

        $screen = $this->actingAs($viewer)
            ->withSession(['last_batting_stat_id' => $latestStatId])
            ->get(route('batting.create', ['game' => $gameId]));

        $screen->assertOk();
        $screen->assertSee('直前の入力', false);
        $screen->assertSee('1回 / 直前入力打者 / 左安打', false);
        $screen->assertSee('修正する', false);
        $screen->assertSee('returnTo=create', false);
        $screen->assertSee('取り消す', false);
        $screen->assertSee('次を入力', false);
        $screen->assertSee('id="batting-submit-bar"', false);
        $screen->assertSee('data-role="sticky-submit-summary"', false);
        $screen->assertSee('data-role="sticky-out-count-chip"', false);
        $screen->assertSee('結果未選択 / 打点 0', false);
        $screen->assertSee('data-role="result-group-tab"', false);
        $screen->assertSee('data-role="result-group-panel"', false);
        $screen->assertSee('data-role="result-picked"', false);
        $screen->assertSee('data-role="direction-picked"', false);
        $screen->assertSee('data-role="change-result"', false);
        $screen->assertSee('data-role="change-direction"', false);
        $screen->assertSee('data-result-group="onbase"', false);
        $screen->assertSee('data-result-group="extra"', false);
        $screen->assertSee('data-result-group="out"', false);
        $screen->assertSee('出塁', false);
        $screen->assertSee('長打', false);
        $screen->assertSee('アウト', false);
        $screen->assertDontSee('試合中はかんたん入力', false);
        $this->assertMatchesRegularExpression('/<select[^>]*id="resultId3"[\s\S]*<option value="31" selected>\s*0\s*<\/option>/u', $screen->getContent());
    }

    public function test_latest_input_can_be_removed_from_create_screen(): void
    {
        $viewer = User::factory()->create();
        $batter = User::factory()->create(['name' => '取消打者']);
        $gameId = $this->createGame();
        $battingStatId = $this->insertBattingStat($gameId, $batter->id, 1, 1, 24, 31);

        $response = $this->actingAs($viewer)->delete(route('batting.destroy', ['batting' => $battingStatId]), [
            'returnTo' => 'create',
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionHas('message', '直前の打撃成績を取り消しました');
        $this->assertDatabaseMissing('batting_stats', ['id' => $battingStatId]);
    }

    private function assertSessionContains($response, string $key, string $expectedSubstring): void
    {
        $response->assertSessionHas($key, function ($value) use ($expectedSubstring): bool {
            return str_contains((string) $value, $expectedSubstring);
        });
    }
}
