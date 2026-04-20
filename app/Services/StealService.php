<?php

namespace App\Services;

use App\Models\BattingOrder;
use App\Models\Game;
use App\Models\Steal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StealService
{
    /**
     * 盗塁登録画面で使う打順一覧と盗塁数を返す。
     */
    public function getIndexData(Game $game): array
    {
        return [
            'battingOrders' => BattingOrder::where('gameId', $game->gameId)
                ->with('position', 'user')
                ->orderBy('battingOrder', 'asc')
                ->get(),
            'stealCounts' => Steal::select('userId', DB::raw('count(*) as count'))
                ->where('gameId', $game->gameId)
                ->whereNotNull('userId')
                ->groupBy('userId')
                ->get(),
            'game' => $game,
        ];
    }

    /**
     * 盗塁を1件追加する。
     */
    public function create(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $steal = new Steal();
            $steal->gameId = (int) $payload['gameId'];
            $steal->userId = (int) $payload['userId'];
            $steal->save();
        });
    }

    /**
     * 直近の盗塁1件を取り消す。
     */
    public function deleteLatest(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $steal = Steal::where('userId', (int) $payload['userId'])
                ->where('gameId', (int) $payload['gameId'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $steal) {
                throw new RuntimeException('取り消せる盗塁がありません。');
            }

            $steal->delete();
        });
    }
}
