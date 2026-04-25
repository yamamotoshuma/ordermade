<?php

namespace Tests\Feature;

use App\Models\GameOffenseState;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsBattingTestData;
use Tests\TestCase;

class OffenseStateFlowTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBattingTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
    }

    public function test_create_screen_renders_runner_state_panel(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '先頭走者']);
        $secondBatter = User::factory()->create(['name' => '次打者']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, $firstBatter->id, 8);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);

        $response = $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));

        $response->assertOk();
        $response->assertSee('id="offense-state-panel"', false);
        $response->assertSee('現在の攻撃状況', false);
        $response->assertSee('走者操作', false);
        $response->assertSee('直前の走者操作を取り消す', false);
        $response->assertSee('name="offenseStateVersion"', false);
    }

    public function test_runner_out_as_third_out_advances_inning_but_keeps_same_batter(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一番']);
        $secondBatter = User::factory()->create(['name' => '二番']);
        $thirdBatter = User::factory()->create(['name' => '三番']);
        $gameId = $this->createGame();

        $firstOrderId = $this->insertOrder($gameId, 1, $firstBatter->id, 8);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $thirdOrderId = $this->insertOrder($gameId, 3, $thirdBatter->id, 6);

        $this->insertBattingStat($gameId, $firstBatter->id, 1, 13, 29, 31, now()->subMinutes(2));
        $this->insertBattingStat($gameId, $secondBatter->id, 1, 13, 29, 31, now()->subMinute());

        $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $state = GameOffenseState::where('gameId', $gameId)->firstOrFail();

        $this->assertSame(1, (int) $state->inning);
        $this->assertSame(2, (int) $state->outCount);
        $this->assertSame($thirdOrderId, (int) $state->batterOrderId);

        $this->actingAs($viewer)->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
            'action' => 'manual_place',
            'orderId' => $firstOrderId,
            'userId' => $firstBatter->id,
            'displayName' => '一番',
            'targetBase' => 1,
        ])->assertRedirect(route('batting.create', ['game' => $gameId]));

        $state = GameOffenseState::where('gameId', $gameId)->firstOrFail();
        $this->assertSame($firstBatter->id, (int) $state->firstUserId);

        $response = $this->actingAs($viewer)->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
            'action' => 'runner_out',
            'base' => 1,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));

        $state->refresh();
        $this->assertSame(2, (int) $state->inning);
        $this->assertSame(0, (int) $state->outCount);
        $this->assertSame($thirdOrderId, (int) $state->batterOrderId);
        $this->assertNull($state->firstUserId);
        $this->assertNull($state->secondUserId);
        $this->assertNull($state->thirdUserId);

        $screen = $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $screen->assertSee('value="2"', false);
        $this->assertMatchesRegularExpression('/<select[^>]*id="batterSelect"[\s\S]*data-user-id="' . $thirdBatter->id . '"[\s\S]*selected/u', $screen->getContent());
    }

    public function test_successful_stolen_base_moves_runner_and_is_counted(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '走者一番']);
        $secondBatter = User::factory()->create(['name' => '打者二番']);
        $gameId = $this->createGame();

        $firstOrderId = $this->insertOrder($gameId, 1, $firstBatter->id, 8);
        $secondOrderId = $this->insertOrder($gameId, 2, $secondBatter->id, 4);

        $this->insertBattingStat($gameId, $firstBatter->id, 1, 1, 24, 31, now()->subMinute());

        $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $state = GameOffenseState::where('gameId', $gameId)->firstOrFail();

        $this->assertSame($secondOrderId, (int) $state->batterOrderId);
        $this->assertSame($firstBatter->id, (int) $state->firstUserId);

        $response = $this->actingAs($viewer)->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
            'action' => 'stolen_base',
            'base' => 1,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));

        $state->refresh();
        $this->assertNull($state->firstUserId);
        $this->assertSame($firstBatter->id, (int) $state->secondUserId);
        $this->assertSame($secondOrderId, (int) $state->batterOrderId);
        $this->assertDatabaseHas('base_running_events', [
            'gameId' => $gameId,
            'actorOrderId' => $firstOrderId,
            'actorUserId' => $firstBatter->id,
            'startBase' => 1,
            'endBase' => 2,
            'eventType' => 'stolen_base',
            'affectsState' => 1,
        ]);

        $showResponse = $this->actingAs($viewer)->get(route('game.show', ['game' => $gameId]));
        $showResponse->assertOk();
        $this->assertBattingStealCount($showResponse->getContent(), '走者一番', '1');
    }

    public function test_stolen_base_pushes_lead_runner_instead_of_erasing_them(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一塁走者']);
        $secondBatter = User::factory()->create(['name' => '二塁走者']);
        $thirdBatter = User::factory()->create(['name' => '打者三番']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, $firstBatter->id, 8);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 6);

        $this->insertBattingStat($gameId, $firstBatter->id, 1, 1, 24, 31, now()->subMinutes(2));
        $this->insertBattingStat($gameId, $secondBatter->id, 1, 1, 24, 31, now()->subMinute());

        $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $state = GameOffenseState::where('gameId', $gameId)->firstOrFail();

        $this->assertSame($secondBatter->id, (int) $state->firstUserId);
        $this->assertSame($firstBatter->id, (int) $state->secondUserId);

        $response = $this->actingAs($viewer)->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
            'action' => 'stolen_base',
            'base' => 1,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));

        $state->refresh();
        $this->assertNull($state->firstUserId);
        $this->assertSame($secondBatter->id, (int) $state->secondUserId);
        $this->assertSame($firstBatter->id, (int) $state->thirdUserId);
    }

    public function test_double_steal_credits_both_runners_when_next_base_is_occupied(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一塁走者']);
        $secondBatter = User::factory()->create(['name' => '二塁走者']);
        $thirdBatter = User::factory()->create(['name' => '打者三番']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, $firstBatter->id, 8);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 6);

        $this->insertBattingStat($gameId, $firstBatter->id, 1, 1, 24, 31, now()->subMinutes(2));
        $this->insertBattingStat($gameId, $secondBatter->id, 1, 1, 24, 31, now()->subMinute());

        $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $state = GameOffenseState::where('gameId', $gameId)->firstOrFail();

        $createResponse = $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $createResponse->assertSee('data-action="double_steal"', false);
        $createResponse->assertSee('重盗', false);

        $response = $this->actingAs($viewer)->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
            'action' => 'double_steal',
            'base' => 1,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));

        $state->refresh();
        $this->assertNull($state->firstUserId);
        $this->assertSame($secondBatter->id, (int) $state->secondUserId);
        $this->assertSame($firstBatter->id, (int) $state->thirdUserId);

        $stealEvents = DB::table('base_running_events')
            ->where('gameId', $gameId)
            ->where('eventType', 'stolen_base')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $stealEvents);
        $this->assertSame([$firstBatter->id, $secondBatter->id], $stealEvents->pluck('actorUserId')->map(fn ($userId) => (int) $userId)->all());
        $this->assertSame(['2', '1'], $stealEvents->pluck('startBase')->map(fn ($base) => (string) $base)->all());
        $this->assertSame(1, $stealEvents->pluck('meta')->map(fn ($meta) => data_get(json_decode($meta, true), 'operationId'))->unique()->count());
        $this->assertSame(1, $stealEvents->pluck('meta')->map(fn ($meta) => data_get(json_decode($meta, true), 'operationType'))->unique()->count());
        $this->assertSame('double_steal', data_get(json_decode($stealEvents->first()->meta, true), 'operationType'));

        $showResponse = $this->actingAs($viewer)->get(route('game.show', ['game' => $gameId]));
        $showResponse->assertOk();
        $this->assertBattingStealCount($showResponse->getContent(), '一塁走者', '1');
        $this->assertBattingStealCount($showResponse->getContent(), '二塁走者', '1');
    }

    public function test_undo_latest_runner_event_removes_double_steal_as_one_operation(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一塁走者']);
        $secondBatter = User::factory()->create(['name' => '二塁走者']);
        $thirdBatter = User::factory()->create(['name' => '打者三番']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, $firstBatter->id, 8);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertOrder($gameId, 3, $thirdBatter->id, 6);

        $this->insertBattingStat($gameId, $firstBatter->id, 1, 1, 24, 31, now()->subMinutes(2));
        $this->insertBattingStat($gameId, $secondBatter->id, 1, 1, 24, 31, now()->subMinute());

        $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $state = GameOffenseState::where('gameId', $gameId)->firstOrFail();

        $this->actingAs($viewer)->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
            'action' => 'double_steal',
            'base' => 1,
        ])->assertRedirect(route('batting.create', ['game' => $gameId]));

        $state->refresh();
        $this->assertDatabaseCount('base_running_events', 2);

        $response = $this->actingAs($viewer)->delete(route('batting.runnerEvents.destroyLatest', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));

        $state->refresh();
        $this->assertSame($secondBatter->id, (int) $state->firstUserId);
        $this->assertSame($firstBatter->id, (int) $state->secondUserId);
        $this->assertNull($state->thirdUserId);
        $this->assertDatabaseCount('base_running_events', 0);
    }

    public function test_runner_event_rejects_stale_offense_state_version(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '走者一番']);
        $secondBatter = User::factory()->create(['name' => '打者二番']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, $firstBatter->id, 8);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertBattingStat($gameId, $firstBatter->id, 1, 1, 24, 31, now()->subMinute());

        $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $state = GameOffenseState::where('gameId', $gameId)->firstOrFail();

        $this->actingAs($viewer)->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
            'action' => 'stolen_base',
            'base' => 1,
        ])->assertRedirect(route('batting.create', ['game' => $gameId]));

        $response = $this->from(route('batting.create', ['game' => $gameId]))
            ->actingAs($viewer)
            ->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
                'offenseStateVersion' => $state->version,
                'action' => 'advance',
                'base' => 2,
            ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));
        $response->assertSessionHas('error', '打撃・走塁状況が他の端末で更新されました。画面を開き直してから再度入力してください。');
    }

    public function test_game_show_displays_stolen_base_for_order_only_name_player(): void
    {
        $viewer = User::factory()->create();
        $nextBatter = User::factory()->create(['name' => '次打者']);
        $gameId = $this->createGame();

        $this->insertOrder($gameId, 1, null, 8, '助っ人太郎');
        $this->insertOrder($gameId, 2, $nextBatter->id, 4);
        $this->insertNamedBattingStat($gameId, '助っ人太郎', 1, 1, 24, 31, now()->subMinute());

        $this->actingAs($viewer)->get(route('batting.create', ['game' => $gameId]));
        $state = GameOffenseState::where('gameId', $gameId)->firstOrFail();

        $response = $this->actingAs($viewer)->post(route('batting.runnerEvents.store', ['game' => $gameId]), [
            'offenseStateVersion' => $state->version,
            'action' => 'stolen_base',
            'base' => 1,
        ]);

        $response->assertRedirect(route('batting.create', ['game' => $gameId]));

        $showResponse = $this->actingAs($viewer)->get(route('game.show', ['game' => $gameId]));
        $showResponse->assertOk();
        $this->assertBattingStealCount($showResponse->getContent(), '助っ人太郎', '1');
    }

}
