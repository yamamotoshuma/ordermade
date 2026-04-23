<?php

namespace App\Services;

use App\Exceptions\BattingStatConflictException;
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
     * 打撃成績を1件登録する。既存行がある場合は確認なしでは更新しない。
     */
    public function create(Game $game, array $payload): BattingStats
    {
        $this->ensureExclusiveBatter($payload);

        return $this->withEntryLock($game, $payload, function () use ($game, $payload): BattingStats {
            return DB::transaction(function () use ($game, $payload): BattingStats {
                $existingBattingStat = $this->findExistingBattingStat($game, $payload, true);

                if ($existingBattingStat) {
                    if (($payload['conflictResolution'] ?? null) === 'update') {
                        return $this->saveFromPayload($existingBattingStat, $game->gameId, $payload);
                    }

                    throw new BattingStatConflictException($existingBattingStat);
                }

                return $this->saveFromPayload(new BattingStats(), $game->gameId, $payload);
            });
        });
    }

    /**
     * 既存の打撃成績を更新する。
     */
    public function update(BattingStats $batting, array $payload): BattingStats
    {
        return DB::transaction(function () use ($batting, $payload) {
            return $this->saveFromPayload($batting, (int) $batting->gameId, $payload);
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
     * 更新前提の検索では既存行に行ロックをかける。
     */
    private function findExistingBattingStat(Game $game, array $payload, bool $forUpdate = false): ?BattingStats
    {
        $query = BattingStats::where('gameId', $game->gameId)
            ->where('inning', (int) $payload['inning']);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        if (filled($payload['userId'] ?? null)) {
            return $query->where('userId', (int) $payload['userId'])->first();
        }

        return $query->where('userName', (string) ($payload['userName'] ?? ''))->first();
    }

    /**
     * モデルへ入力値を反映して保存する共通処理。
     */
    private function saveFromPayload(BattingStats $battingStat, int $gameId, array $payload): BattingStats
    {
        $battingStat->gameId = $gameId;
        $battingStat->userId = $payload['userId'] ?? null;
        $battingStat->userName = $payload['userName'] ?? null;
        $battingStat->inning = (int) $payload['inning'];
        $battingStat->resultId1 = (int) $payload['resultId1'];
        $battingStat->resultId2 = (int) $payload['resultId2'];
        $battingStat->resultId3 = (int) $payload['resultId3'];
        $battingStat->save();

        return $battingStat;
    }

    /**
     * 同じ打者・同じイニングの新規登録が同時実行されないようにする。
     */
    private function withEntryLock(Game $game, array $payload, callable $callback): BattingStats
    {
        if (! $this->supportsMysqlNamedLocks()) {
            return $callback();
        }

        $lockName = $this->makeEntryLockName($game, $payload);
        $lock = DB::selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockName]);

        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw new RuntimeException('他の端末が同じ打者の登録処理中です。少し待ってから再度登録してください。');
        }

        try {
            return $callback();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }
    }

    /**
     * MySQL 以外のテスト環境では名前付きロックを使わず通常実行する。
     */
    private function supportsMysqlNamedLocks(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    /**
     * MySQL の名前付きロック上限に収まる衝突キーを作る。
     */
    private function makeEntryLockName(Game $game, array $payload): string
    {
        $batterKey = filled($payload['userId'] ?? null)
            ? 'id:' . (int) $payload['userId']
            : 'name:' . trim((string) ($payload['userName'] ?? ''));

        return 'batting:' . sha1($game->gameId . ':' . (int) $payload['inning'] . ':' . $batterKey);
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

        $activeUserIds = $users->pluck('id')->flip();

        $selectableOrders = $orders->filter(function ($order) use ($activeUserIds) {
            if ($order->userId) {
                return $activeUserIds->has($order->userId);
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
