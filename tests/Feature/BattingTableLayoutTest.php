<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBattingTestData;
use Tests\TestCase;

class BattingTableLayoutTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBattingTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
    }

    public function test_batting_tables_render_blank_header_column_for_second_plate_appearance(): void
    {
        $viewer = User::factory()->create();
        $firstBatter = User::factory()->create(['name' => '一番打者']);
        $secondBatter = User::factory()->create(['name' => '二番打者']);
        $gameId = $this->createGame();
        $this->insertOrder($gameId, 1, $firstBatter->id);
        $this->insertOrder($gameId, 2, $secondBatter->id, 4);
        $this->insertBattingStat($gameId, $firstBatter->id, 3, 1, 24, 31, 1);
        $this->insertBattingStat($gameId, $firstBatter->id, 3, 13, 29, 31, 2);
        $this->insertBattingStat($gameId, $secondBatter->id, 4, 12, 25, 31, 1);

        $battingIndex = $this->actingAs($viewer)->get(route('batting.index', ['game' => $gameId]));
        $battingIndex->assertOk();
        $this->assertMatchesRegularExpression('/>3<\/th>\s*<th[^>]*>\s*<\/th>\s*<th[^>]*>4<\/th>/u', $battingIndex->getContent());

        $gameShow = $this->actingAs($viewer)->get(route('game.show', ['game' => $gameId]));
        $gameShow->assertOk();
        $this->assertMatchesRegularExpression('/>3<\/th>\s*<th[^>]*>\s*<\/th>\s*<th[^>]*>4<\/th>/u', $gameShow->getContent());
        $gameShow->assertSee('左安打', false);
        $gameShow->assertSee('三振', false);
    }
}
