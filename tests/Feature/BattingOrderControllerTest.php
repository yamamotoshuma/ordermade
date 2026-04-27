<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GoogleSheetsOrderImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\Concerns\BuildsBattingTestData;
use Tests\TestCase;

class BattingOrderControllerTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBattingTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPositions();
    }

    public function test_store_normalizes_duplicate_rankings_from_request(): void
    {
        $authUser = User::factory()->create();
        $playerOne = User::factory()->create();
        $playerTwo = User::factory()->create();
        $gameId = $this->createGame();

        $response = $this->actingAs($authUser)->post(route('order.store'), [
            'gameId' => $gameId,
            'battingOrder' => [3, 3, 4],
            'positionId' => [5, 1, 2],
            'userId' => [$playerOne->id, $playerTwo->id, ''],
            'userName' => ['', '', '助っ人'],
            'ranking' => [1, 1, 1],
        ]);

        $response->assertRedirect(route('order.edit', ['order' => $gameId]));
        $response->assertSessionHas('message', 'データが保存されました');

        $orders = DB::table('batting_orders')
            ->where('gameId', $gameId)
            ->orderBy('orderId')
            ->get(['battingOrder', 'ranking', 'userId', 'userName']);

        $this->assertEquals([3, 3, 4], $orders->pluck('battingOrder')->all());
        $this->assertEquals([1, 2, 1], $orders->pluck('ranking')->all());
        $this->assertSame('助っ人', $orders[2]->userName);
    }

    public function test_edit_screen_renders_mobile_friendly_order_rows(): void
    {
        $authUser = User::factory()->create();
        $player = User::factory()->create(['name' => '一番太郎']);
        $gameId = $this->createGame();

        DB::table('batting_orders')->insert([
            'gameId' => $gameId,
            'battingOrder' => 1,
            'positionId' => 5,
            'userId' => $player->id,
            'userName' => null,
            'ranking' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($authUser)->get(route('order.edit', ['order' => $gameId]));

        $response->assertOk();
        $response->assertSee('data-order-edit-page', false);
        $response->assertSee('data-order-rows', false);
        $response->assertSee('スプレッドシート反映');
        $response->assertSee('一番太郎');
        $this->assertSame(10, substr_count($response->getContent(), "data-order-row\n"));
    }

    public function test_edit_screen_repopulates_old_input_after_store_error(): void
    {
        $authUser = User::factory()->create();
        $player = User::factory()->create();
        $gameId = $this->createGame();

        $response = $this->actingAs($authUser)->post(route('order.store'), [
            'gameId' => $gameId,
            'battingOrder' => [1],
            'positionId' => [1],
            'userId' => [$player->id],
            'userName' => ['助っ人入力'],
            'ranking' => [1],
        ]);

        $response->assertRedirect(route('order.edit', ['order' => $gameId]));
        $response->assertSessionHas('error', '選手と選手名は同時に入力しないでください。');

        $this->get(route('order.edit', ['order' => $gameId]))
            ->assertOk()
            ->assertSee('助っ人入力');
    }

    public function test_import_from_spreadsheet_skips_invalid_rows_and_uses_aliases_without_error(): void
    {
        config([
            'services.google_sheets.user_aliases' => [
                'シューマ' => '山本修馬',
            ],
        ]);

        $authUser = User::factory()->create();
        $player = User::factory()->create([
            'name' => '山本修馬',
        ]);
        $gameId = $this->createGame();

        $this->mock(GoogleSheetsOrderImporter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchOrderRows')->once()->andReturn([
                ['battingOrder' => 1, 'positionName' => '3B', 'playerName' => 'シューマ'],
                ['battingOrder' => 1, 'positionName' => '投手', 'playerName' => '未登録 選手'],
                ['battingOrder' => 2, 'positionName' => 'ベンチ', 'playerName' => '対象外'],
            ]);
        });

        $response = $this->actingAs($authUser)->post(route('order.importSheet', ['order' => $gameId]));

        $response->assertRedirect(route('order.edit', ['order' => $gameId]));
        $response->assertSessionHas('message', function (string $message): bool {
            return str_contains($message, 'スプレッドシートのオーダーを反映しました。')
                && str_contains($message, '守備位置を判定できない 1 行はスキップしました。')
                && str_contains($message, '一致しない選手名 1 件は登録外選手として取り込みました。');
        });

        $orders = DB::table('batting_orders')
            ->where('gameId', $gameId)
            ->orderBy('orderId')
            ->get(['battingOrder', 'positionId', 'userId', 'userName', 'ranking']);

        $this->assertCount(2, $orders);
        $this->assertEquals([1, 1], $orders->pluck('battingOrder')->all());
        $this->assertEquals([1, 2], $orders->pluck('ranking')->all());
        $this->assertEquals(5, $orders[0]->positionId);
        $this->assertEquals($player->id, $orders[0]->userId);
        $this->assertNull($orders[0]->userName);
        $this->assertEquals(1, $orders[1]->positionId);
        $this->assertNull($orders[1]->userId);
        $this->assertSame('未登録 選手', $orders[1]->userName);
    }

    public function test_import_from_spreadsheet_keeps_existing_rows_when_no_rows_are_importable(): void
    {
        $authUser = User::factory()->create();
        $gameId = $this->createGame();

        DB::table('batting_orders')->insert([
            'gameId' => $gameId,
            'battingOrder' => 1,
            'positionId' => 1,
            'userId' => null,
            'userName' => '残す選手',
            'ranking' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(GoogleSheetsOrderImporter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchOrderRows')->once()->andReturn([
                ['battingOrder' => 1, 'positionName' => 'ベンチ', 'playerName' => '取り込まない'],
            ]);
        });

        $response = $this->actingAs($authUser)->post(route('order.importSheet', ['order' => $gameId]));

        $response->assertRedirect(route('order.edit', ['order' => $gameId]));
        $response->assertSessionHas('message', function (string $message): bool {
            return str_contains($message, '既存の打順は変更していません。')
                && str_contains($message, '守備位置を判定できない 1 行はスキップしました。');
        });

        $this->assertDatabaseHas('batting_orders', [
            'gameId' => $gameId,
            'battingOrder' => 1,
            'positionId' => 1,
            'userName' => '残す選手',
            'ranking' => 1,
        ]);
        $this->assertSame(1, DB::table('batting_orders')->where('gameId', $gameId)->count());
    }

    private function seedPositions(): void
    {
        DB::table('positions')->insert([
            ['positionId' => 1, 'positionName' => '投', 'created_at' => now(), 'updated_at' => now()],
            ['positionId' => 2, 'positionName' => '捕', 'created_at' => now(), 'updated_at' => now()],
            ['positionId' => 5, 'positionName' => '三', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
