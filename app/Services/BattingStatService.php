<?php

namespace App\Services;

use App\Models\BattingOrder;
use App\Models\BattingResultMaster;
use App\Models\BattingStats;
use App\Models\Game;
use App\Models\Point;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BattingStatService
{
    /**
     * 打撃成績一覧画面で必要な関連データをまとめて返す。
     */
    public function getIndexData(Game $game, string $statsId = ''): array
    {
        return [
            'game' => $game,
            'points' => Point::where('gameId', $game->gameId)->get(),
            'orders' => BattingOrder::where('gameId', $game->gameId)
                ->with('position', 'user')
                ->orderBy('battingOrder', 'asc')
                ->orderBy('ranking', 'asc')
                ->get(),
            'battingStats' => BattingStats::where('gameId', $game->gameId)
                ->with('user', 'result1', 'result2', 'result3', 'result4', 'result5')
                ->get(),
            'statsId' => $statsId,
        ];
    }

    /**
     * 打撃成績登録画面で使う候補値と初期値を返す。
     */
    public function getCreateData(Game $game): array
    {
        $orders = BattingOrder::where('gameId', $game->gameId)
            ->with('user')
            ->orderBy('battingOrder')
            ->orderBy('ranking')
            ->get();

        $userIdsInOrder = $orders->pluck('userId')->filter()->unique()->values();
        $users = User::where('active_flg', 1)
            ->whereIn('id', $userIdsInOrder)
            ->get();
        $battingStats = BattingStats::where('gameId', $game->gameId)
            ->with('result1')
            ->orderBy('inning')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return [
            'game' => $game,
            'users' => $users,
            'results' => BattingResultMaster::all(),
            'orders' => $orders,
            'createDefaults' => $this->buildCreateDefaults($orders, $users, $battingStats),
        ];
    }

    /**
     * 打撃成績編集画面の表示用データを返す。
     */
    public function getEditData(BattingStats $batting): array
    {
        $batting->loadMissing('game', 'user');

        return [
            'batting' => $batting,
            'results' => BattingResultMaster::all(),
        ];
    }

    /**
     * 打撃成績を1件登録する。
     */
    public function create(Game $game, array $payload): BattingStats
    {
        return DB::transaction(function () use ($game, $payload) {
            $this->ensureExclusiveBatter($payload);

            $existingBattingStat = $this->findExistingBattingStat($game, $payload);

            if ($existingBattingStat) {
                throw new RuntimeException('すでに打撃データが存在します');
            }

            $battingStat = new BattingStats();
            $battingStat->gameId = $game->gameId;
            $battingStat->userId = $payload['userId'] ?? null;
            $battingStat->userName = $payload['userName'] ?? null;
            $battingStat->inning = (int) $payload['inning'];
            $battingStat->resultId1 = (int) $payload['resultId1'];
            $battingStat->resultId2 = (int) $payload['resultId2'];
            $battingStat->resultId3 = (int) $payload['resultId3'];
            $battingStat->save();

            return $battingStat;
        });
    }

    /**
     * 既存の打撃成績を更新する。
     */
    public function update(BattingStats $batting, array $payload): BattingStats
    {
        return DB::transaction(function () use ($batting, $payload) {
            $batting->userId = $payload['userId'] ?? null;
            $batting->userName = $payload['userName'] ?? null;
            $batting->inning = (int) $payload['inning'];
            $batting->resultId1 = (int) $payload['resultId1'];
            $batting->resultId2 = (int) $payload['resultId2'];
            $batting->resultId3 = (int) $payload['resultId3'];
            $batting->save();

            return $batting;
        });
    }

    /**
     * 打撃成績を削除し、戻り先の試合を返す。
     */
    public function delete(BattingStats $batting): Game
    {
        return DB::transaction(function () use ($batting) {
            $game = Game::findOrFail($batting->gameId);
            $batting->delete();

            return $game;
        });
    }

    /**
     * ユーザーIDとユーザー名の二重指定を防ぐ。
     */
    private function ensureExclusiveBatter(array $payload): void
    {
        if (filled($payload['userId'] ?? null) && filled($payload['userName'] ?? null)) {
            throw new RuntimeException('ユーザーIDとユーザー名を同時に入力しないでください');
        }
    }

    /**
     * 同一試合・同一イニング・同一打者の重複登録を検知する。
     */
    private function findExistingBattingStat(Game $game, array $payload): ?BattingStats
    {
        $query = BattingStats::where('gameId', $game->gameId)
            ->where('inning', (int) $payload['inning']);

        if (filled($payload['userId'] ?? null)) {
            return $query->where('userId', (int) $payload['userId'])->first();
        }

        return $query->where('userName', (string) ($payload['userName'] ?? ''))->first();
    }

    /**
     * 打順と既存打席から初期表示用の打者・イニングを決める。
     */
    private function buildCreateDefaults(Collection $orders, Collection $users, Collection $battingStats): array
    {
        $inningOutCounts = $this->buildInningOutCounts($battingStats);
        $defaultInning = 1;

        if ($battingStats->isNotEmpty()) {
            $latestStat = $battingStats->last();
            $latestInning = (int) $latestStat->inning;
            $defaultInning = $latestInning + (($inningOutCounts[$latestInning] ?? 0) >= 3 ? 1 : 0);
        }

        $selectableOrders = $orders->filter(function ($order) use ($users) {
            if ($order->userId) {
                return $users->contains('id', $order->userId);
            }

            return filled($order->userName);
        })->values();

        $nextOrder = $this->resolveNextOrder($selectableOrders, $battingStats);

        return [
            'defaultUserId' => $nextOrder?->userId ? (string) $nextOrder->userId : '',
            'defaultUserName' => $nextOrder && ! $nextOrder->userId ? (string) $nextOrder->userName : '',
            'defaultInning' => $defaultInning,
            'inningOutCounts' => $inningOutCounts,
        ];
    }

    /**
     * 最後に入力された打者の次打者を打順から解決する。
     */
    private function resolveNextOrder(Collection $orders, Collection $battingStats): ?BattingOrder
    {
        if ($orders->isEmpty()) {
            return null;
        }

        if ($battingStats->isEmpty()) {
            return $orders->first();
        }

        $orderKeys = $orders->map(function ($order) {
            return $this->makeOrderKey($order->userId, $order->userName);
        })->all();

        foreach ($battingStats->reverse() as $stat) {
            $matchedIndex = array_search($this->makeOrderKey($stat->userId, $stat->userName), $orderKeys, true);

            if ($matchedIndex === false) {
                continue;
            }

            $nextIndex = ($matchedIndex + 1) % $orders->count();

            return $orders->get($nextIndex);
        }

        return $orders->first();
    }

    /**
     * イニングごとのアウト数を集計する。
     */
    private function buildInningOutCounts(Collection $battingStats): array
    {
        $outCounts = [];

        foreach ($battingStats as $stat) {
            $inning = (int) $stat->inning;
            $outCounts[$inning] = ($outCounts[$inning] ?? 0) + $this->resultOutCount((string) optional($stat->result1)->name);
        }

        ksort($outCounts);

        return $outCounts;
    }

    /**
     * 打撃結果名から増加すべきアウト数を返す。
     */
    private function resultOutCount(string $resultName): int
    {
        return match (trim($resultName)) {
            'ゴロ', 'フライ', '三振', 'ライナー', '犠打', '犠飛' => 1,
            '併殺' => 2,
            '三重殺' => 3,
            default => 0,
        };
    }

    /**
     * 打順照合用に userId または userName ベースのキーを作る。
     */
    private function makeOrderKey(?int $userId, ?string $userName): string
    {
        if ($userId) {
            return 'id:' . $userId;
        }

        return 'name:' . trim((string) $userName);
    }
}
