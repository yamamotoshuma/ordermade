<?php

namespace App\Services;

use App\Models\BattingOrder;
use App\Models\Game;

class StealService
{
    public function __construct(
        private readonly OffenseStateService $offenseStateService
    ) {
    }

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
            'stealCounts' => $this->offenseStateService->getStealCounts($game),
            'game' => $game,
        ];
    }

    /**
     * 盗塁を1件追加する。
     */
    public function create(array $payload): void
    {
        $game = Game::findOrFail((int) $payload['gameId']);
        $this->offenseStateService->createLegacyStealEvent($game, (int) $payload['userId']);
    }

    /**
     * 直近の盗塁1件を取り消す。
     */
    public function deleteLatest(array $payload): void
    {
        $game = Game::findOrFail((int) $payload['gameId']);
        $this->offenseStateService->deleteLatestStealEvent($game, (int) $payload['userId']);
    }
}
