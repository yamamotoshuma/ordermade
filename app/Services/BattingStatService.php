<?php

namespace App\Services;

use App\Exceptions\BattingStatConflictException;
use App\Models\BattingOrder;
use App\Models\BattingResultMaster;
use App\Models\BattingStats;
use App\Models\Game;
use App\Models\GameOffenseState;
use App\Models\Point;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BattingStatService
{
    public function __construct(
        private readonly OffenseStateService $offenseStateService
    ) {
    }

    /**
     * 打撃成績一覧画面で必要な関連データをまとめて返す。
     */
    public function getIndexData(Game $game, string $statsId = ''): array
    {
        $battingStats = BattingStats::where('gameId', $game->gameId)
            ->with('user', 'result1', 'result2', 'result3', 'result4', 'result5')
            ->orderBy('inning')
            ->orderBy('inningTurn')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $tableLayout = $this->buildTableLayout($battingStats, 9);

        return [
            'game' => $game,
            'points' => Point::where('gameId', $game->gameId)->get(),
            'orders' => BattingOrder::where('gameId', $game->gameId)
                ->with('position', 'user')
                ->orderBy('battingOrder', 'asc')
                ->orderBy('ranking', 'asc')
                ->get(),
            'battingStats' => $battingStats,
            'battingColumns' => $tableLayout['columns'],
            'battingCellMap' => $tableLayout['cellMap'],
            'statsId' => $statsId,
        ];
    }

    /**
     * 打撃成績登録画面で使う候補値と初期値を返す。
     */
    public function getCreateData(Game $game, mixed $lastBattingStatId = null): array
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
            ->orderBy('inningTurn')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $fallbackDefaults = $this->buildCreateDefaults($orders, $users, $battingStats);
        $stateData = $this->offenseStateService->buildCreateStateData(
            $game,
            $orders,
            $battingStats,
            $fallbackDefaults
        );
        $offenseState = $stateData['offenseState'];
        unset($stateData['offenseState']);

        return [
            'game' => $game,
            'users' => $users,
            'results' => BattingResultMaster::all(),
            'orders' => $orders,
            'createDefaults' => $stateData,
            'offenseState' => $offenseState,
            'lastBattingStat' => $this->findLastBattingStat($game, $lastBattingStatId),
        ];
    }

    /**
     * 打撃成績編集画面の表示用データを返す。
     */
    public function getEditData(BattingStats $batting): array
    {
        $batting->loadMissing('game', 'user', 'result1', 'result2', 'result3');
        $orders = BattingOrder::where('gameId', $batting->gameId)
            ->with('user')
            ->orderBy('battingOrder')
            ->orderBy('ranking')
            ->get();

        $userIds = $orders->pluck('userId')
            ->filter()
            ->when($batting->userId, fn ($ids) => $ids->push($batting->userId))
            ->unique()
            ->values();

        return [
            'batting' => $batting,
            'results' => BattingResultMaster::all(),
            'orders' => $orders,
            'users' => User::where('active_flg', 1)
                ->whereIn('id', $userIds)
                ->get(),
        ];
    }

    /**
     * 打撃成績表で使うイニング列定義とセル参照マップを返す。
     */
    public function buildTableLayout(Collection $battingStats, int $minimumPrimaryInnings = 0): array
    {
        $columns = [];
        $cellMap = [];
        $inningTurnCounts = [];
        $maxPrimaryInning = max($minimumPrimaryInnings, (int) ($battingStats->max('inning') ?? 0));

        foreach ($battingStats as $stat) {
            $inning = (int) $stat->inning;
            $inningTurn = max(1, (int) ($stat->inningTurn ?? 1));
            $playerKey = $this->makeOrderKey($stat->userId, $stat->userName);

            $inningTurnCounts[$inning] = max($inningTurnCounts[$inning] ?? 1, $inningTurn);
            $cellMap[$playerKey][$inning][$inningTurn] = $stat;
        }

        for ($inning = 1; $inning <= $maxPrimaryInning; $inning++) {
            $maxTurn = max(1, (int) ($inningTurnCounts[$inning] ?? 1));

            for ($turn = 1; $turn <= $maxTurn; $turn++) {
                $columns[] = [
                    'inning' => $inning,
                    'turn' => $turn,
                    'label' => $turn === 1 ? (string) $inning : '',
                ];
            }
        }

        return [
            'columns' => $columns,
            'cellMap' => $cellMap,
        ];
    }

    /**
     * 打撃成績を1件登録する。打者一巡後の同一イニング再打席は inningTurn で管理する。
     */
    public function create(Game $game, array $payload): BattingStats
    {
        $this->ensureExclusiveBatter($payload);

        return $this->offenseStateService->withGameStateLock($game, function () use ($game, $payload): BattingStats {
            $this->offenseStateService->assertExpectedVersion($game, (int) ($payload['offenseStateVersion'] ?? 0));
            $liveState = $this->offenseStateService->syncCachedState($game);

            return $this->withEntryLock($game, $payload, function () use ($game, $payload, $liveState): BattingStats {
                return DB::transaction(function () use ($game, $payload, $liveState): BattingStats {
                    $inningTurn = $this->resolveCreateInningTurn($game, $payload);
                    $this->assertCurrentAtBatRbiConfirmation($payload, $liveState);
                    $saved = $this->saveFromPayload(new BattingStats(), $game->gameId, $payload, $inningTurn);
                    $this->renumberGroupByPayload($game->gameId, $payload);
                    $this->offenseStateService->syncCachedState($game);

                    return $saved;
                });
            });
        });
    }

    /**
     * 既存の打撃成績を更新する。
     */
    public function update(BattingStats $batting, array $payload): BattingStats
    {
        $this->ensureExclusiveBatter($payload);

        $game = Game::findOrFail((int) $batting->gameId);

        return $this->offenseStateService->withGameStateLock($game, function () use ($batting, $payload, $game) {
            return $this->withEntryLock($game, $payload, function () use ($batting, $payload, $game) {
                return DB::transaction(function () use ($batting, $payload, $game) {
                    $originalGroup = $this->buildGroupSignatureFromStat($batting);
                    $inningTurn = $this->resolveUpdateInningTurn($game, $batting, $payload);
                    $saved = $this->saveFromPayload($batting, (int) $batting->gameId, $payload, $inningTurn);
                    $targetGroup = $this->buildGroupSignatureFromPayload($payload);

                    $this->renumberGroupBySignature($game->gameId, $originalGroup);

                    if (! $this->sameGroupSignature($originalGroup, $targetGroup)) {
                        $this->renumberGroupBySignature($game->gameId, $targetGroup);
                    }

                    $this->offenseStateService->syncCachedState($game);

                    return $saved;
                });
            });
        });
    }

    /**
     * 打撃成績を削除し、戻り先の試合を返す。
     */
    public function delete(BattingStats $batting): Game
    {
        $game = Game::findOrFail((int) $batting->gameId);

        return $this->offenseStateService->withGameStateLock($game, function () use ($batting, $game) {
            return DB::transaction(function () use ($batting, $game) {
                $group = $this->buildGroupSignatureFromStat($batting);
                $batting->delete();
                $this->renumberGroupBySignature($game->gameId, $group);
                $this->offenseStateService->syncCachedState($game);

                return $game;
            });
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
     * 新規登録時の inningTurn を決める。
     */
    private function resolveCreateInningTurn(Game $game, array $payload): int
    {
        $targetStats = $this->loadTargetGroupStats($game->gameId, $payload, null, true);

        if ($targetStats->isEmpty()) {
            return 1;
        }

        $nextTurn = $targetStats->count() + 1;

        if (($payload['confirmationResolution'] ?? null) === 'duplicate') {
            return $nextTurn;
        }

        if ($this->isInningCycleCompleted($game, (int) $payload['inning'])) {
            return $nextTurn;
        }

        throw new BattingStatConflictException(
            null,
            $this->buildDuplicateWarningMessage((int) $payload['inning'], $nextTurn),
            '同じ打者・同じイニングの打撃がすでに登録されています。',
            'duplicate'
        );
    }

    /**
     * 更新時の inningTurn を決める。
     */
    private function resolveUpdateInningTurn(Game $game, BattingStats $batting, array $payload): int
    {
        $originalGroup = $this->buildGroupSignatureFromStat($batting);
        $targetGroup = $this->buildGroupSignatureFromPayload($payload);

        if ($this->sameGroupSignature($originalGroup, $targetGroup)) {
            return max(1, (int) ($batting->inningTurn ?? 1));
        }

        $targetStats = $this->loadTargetGroupStats($game->gameId, $payload, (int) $batting->id, true);

        if ($targetStats->isEmpty()) {
            return 1;
        }

        $nextTurn = $targetStats->count() + 1;

        if (($payload['confirmationResolution'] ?? null) === 'duplicate') {
            return $nextTurn;
        }

        if ($this->isInningCycleCompleted($game, (int) $payload['inning'])) {
            return $nextTurn;
        }

        throw new BattingStatConflictException(
            null,
            $this->buildDuplicateWarningMessage((int) $payload['inning'], $nextTurn),
            '同じ打者・同じイニングの打撃がすでに登録されています。',
            'duplicate'
        );
    }

    /**
     * 現在の打者・走者状況から打点の入力漏れを警告する。
     */
    private function assertCurrentAtBatRbiConfirmation(array $payload, ?GameOffenseState $liveState): void
    {
        if (($payload['confirmationResolution'] ?? null) === 'rbi') {
            return;
        }

        $warningMessage = $this->buildRbiWarningMessage($payload, $liveState);

        if (! $warningMessage) {
            return;
        }

        throw new BattingStatConflictException(
            null,
            $warningMessage,
            '打点を確認してください。',
            'rbi'
        );
    }

    /**
     * 打点警告メッセージを必要な場合だけ返す。
     */
    private function buildRbiWarningMessage(array $payload, ?GameOffenseState $liveState): ?string
    {
        if (! $liveState || $liveState->needsRunnerConfirmation) {
            return null;
        }

        if (! $this->payloadTargetsCurrentAtBat($payload, $liveState)) {
            return null;
        }

        $masters = BattingResultMaster::whereIn('id', [
            (int) ($payload['resultId1'] ?? 0),
            (int) ($payload['resultId3'] ?? 0),
        ])->get()->keyBy('id');

        $resultName = trim((string) optional($masters->get((int) ($payload['resultId1'] ?? 0)))->name);
        $enteredRbi = max(0, (int) trim((string) optional($masters->get((int) ($payload['resultId3'] ?? 0)))->name));
        $expectation = $this->estimateRbiExpectation($liveState, $resultName);

        if (! $expectation || $enteredRbi >= $expectation['minimum']) {
            return null;
        }

        $baseSummary = $this->summarizeOccupiedBases($liveState);

        if ($expectation['certainty'] === 'definite') {
            return $baseSummary . 'で' . $resultName . 'なら少なくとも' . $expectation['minimum'] . '打点になるはずです。現在は' . $enteredRbi . '打点です。このまま登録しますか？';
        }

        return $baseSummary . 'で' . $resultName . 'なら' . $expectation['minimum'] . '打点以上になる可能性が高いです。本塁アウトなどで打点が付かないケースでなければ、打点を見直してください。現在は' . $enteredRbi . '打点です。このまま登録しますか？';
    }

    /**
     * 現在の打者・イニングと送信内容が一致する時だけ自動警告対象にする。
     */
    private function payloadTargetsCurrentAtBat(array $payload, GameOffenseState $liveState): bool
    {
        if ((int) ($payload['inning'] ?? 0) !== (int) $liveState->inning) {
            return false;
        }

        $payloadKey = $this->makeOrderKey(
            filled($payload['userId'] ?? null) ? (int) $payload['userId'] : null,
            filled($payload['userId'] ?? null) ? null : trim((string) ($payload['userName'] ?? ''))
        );
        $stateKey = $this->makeOrderKey(
            $liveState->batterUserId ? (int) $liveState->batterUserId : null,
            $liveState->batterUserId ? null : trim((string) $liveState->batterUserName)
        );

        return $payloadKey !== 'name:' && $payloadKey === $stateKey;
    }

    /**
     * 現在塁上にいる走者と結果から、警告すべき最低打点を推定する。
     */
    private function estimateRbiExpectation(GameOffenseState $liveState, string $resultName): ?array
    {
        $firstOccupied = $this->isBaseOccupied($liveState->firstOrderId, $liveState->firstUserId, $liveState->firstUserName);
        $secondOccupied = $this->isBaseOccupied($liveState->secondOrderId, $liveState->secondUserId, $liveState->secondUserName);
        $thirdOccupied = $this->isBaseOccupied($liveState->thirdOrderId, $liveState->thirdUserId, $liveState->thirdUserName);
        $occupiedCount = collect([$firstOccupied, $secondOccupied, $thirdOccupied])->filter()->count();

        return match ($resultName) {
            '本塁打' => ['minimum' => $occupiedCount + 1, 'certainty' => 'definite'],
            '四球', '死球' => $firstOccupied && $secondOccupied && $thirdOccupied
                ? ['minimum' => 1, 'certainty' => 'definite']
                : null,
            '安打' => $thirdOccupied
                ? ['minimum' => 1, 'certainty' => 'likely']
                : null,
            '二塁打' => ($secondOccupied || $thirdOccupied)
                ? ['minimum' => 1, 'certainty' => 'likely']
                : null,
            '三塁打' => $occupiedCount > 0
                ? ['minimum' => 1, 'certainty' => 'likely']
                : null,
            '犠飛' => $thirdOccupied
                ? ['minimum' => 1, 'certainty' => 'likely']
                : null,
            default => null,
        };
    }

    /**
     * 塁が埋まっているかを prefix 列から判定する。
     */
    private function isBaseOccupied(mixed $orderId, mixed $userId, mixed $userName): bool
    {
        return filled($orderId) || filled($userId) || trim((string) $userName) !== '';
    }

    /**
     * 打点警告文言向けに現在の塁状況を短く表す。
     */
    private function summarizeOccupiedBases(GameOffenseState $liveState): string
    {
        $bases = [];

        if ($this->isBaseOccupied($liveState->firstOrderId, $liveState->firstUserId, $liveState->firstUserName)) {
            $bases[] = '一';
        }

        if ($this->isBaseOccupied($liveState->secondOrderId, $liveState->secondUserId, $liveState->secondUserName)) {
            $bases[] = '二';
        }

        if ($this->isBaseOccupied($liveState->thirdOrderId, $liveState->thirdUserId, $liveState->thirdUserName)) {
            $bases[] = '三';
        }

        if ($bases === ['一', '二', '三']) {
            return '満塁';
        }

        if ($bases === []) {
            return '走者なし';
        }

        return implode('', $bases) . '塁';
    }

    /**
     * モデルへ入力値を反映して保存する共通処理。
     */
    private function saveFromPayload(BattingStats $battingStat, int $gameId, array $payload, int $inningTurn): BattingStats
    {
        $battingStat->gameId = $gameId;
        $battingStat->userId = $payload['userId'] ?? null;
        $battingStat->userName = $payload['userName'] ?? null;
        $battingStat->inning = (int) $payload['inning'];
        $battingStat->inningTurn = $inningTurn;
        $battingStat->resultId1 = (int) $payload['resultId1'];
        $battingStat->resultId2 = (int) $payload['resultId2'];
        $battingStat->resultId3 = (int) $payload['resultId3'];
        $battingStat->save();

        return $battingStat;
    }

    /**
     * 登録完了直後だけ表示する直前入力カード用の成績を取得する。
     */
    private function findLastBattingStat(Game $game, mixed $lastBattingStatId): ?BattingStats
    {
        if (! is_numeric($lastBattingStatId)) {
            return null;
        }

        return BattingStats::whereKey((int) $lastBattingStatId)
            ->where('gameId', $game->gameId)
            ->with('user', 'result1', 'result2', 'result3')
            ->first();
    }

    /**
     * 対象の打者・イニングに属する既存打席を取得する。
     */
    private function loadTargetGroupStats(int $gameId, array $payload, ?int $excludeBattingId = null, bool $forUpdate = false): Collection
    {
        $query = BattingStats::where('gameId', $gameId)
            ->where('inning', (int) $payload['inning']);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        if ($excludeBattingId !== null) {
            $query->where('id', '!=', $excludeBattingId);
        }

        if (filled($payload['userId'] ?? null)) {
            $query->where('userId', (int) $payload['userId']);
        } else {
            $query->whereNull('userId')
                ->where('userName', trim((string) ($payload['userName'] ?? '')));
        }

        return $query
            ->orderBy('inningTurn')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * 打順人数分の打席がそのイニングで1周しているかを判定する。
     */
    private function isInningCycleCompleted(Game $game, int $inning): bool
    {
        $requiredKeys = $this->loadSelectableOrderKeys($game);

        if ($requiredKeys === []) {
            return false;
        }

        $requiredCounts = array_count_values($requiredKeys);
        $presentCounts = [];

        foreach (BattingStats::where('gameId', $game->gameId)->where('inning', $inning)->get() as $stat) {
            $playerKey = $this->makeOrderKey($stat->userId, $stat->userName);
            $presentCounts[$playerKey] = ($presentCounts[$playerKey] ?? 0) + 1;
        }

        foreach ($requiredCounts as $playerKey => $requiredCount) {
            if (($presentCounts[$playerKey] ?? 0) < $requiredCount) {
                return false;
            }
        }

        return true;
    }

    /**
     * 打順表から打席1周分の選手キーを読み取る。
     */
    private function loadSelectableOrderKeys(Game $game): array
    {
        return BattingOrder::where('gameId', $game->gameId)
            ->with('user')
            ->orderBy('battingOrder')
            ->orderBy('ranking')
            ->get()
            ->filter(function (BattingOrder $order): bool {
                if ($order->userId) {
                    return (bool) optional($order->user)->active_flg;
                }

                return filled($order->userName);
            })
            ->map(fn (BattingOrder $order): string => $this->makeOrderKey($order->userId, $order->userName))
            ->values()
            ->all();
    }

    /**
     * 同一イニング再打席の誤操作を防ぐ確認文言を作る。
     */
    private function buildDuplicateWarningMessage(int $inning, int $nextTurn): string
    {
        return $inning . '回の同じ打者はすでに登録されています。まだ' . $inning . '回の全打者分が入力されていないため、誤入力の可能性があります。このまま' . $inning . '回の' . $nextTurn . '打席目として登録しますか？';
    }

    /**
     * 打者・イニング単位の並びを 1,2,3... に振り直す。
     */
    private function renumberGroupByPayload(int $gameId, array $payload): void
    {
        $this->renumberGroupBySignature($gameId, $this->buildGroupSignatureFromPayload($payload));
    }

    /**
     * 打者・イニング単位の並びを 1,2,3... に振り直す。
     */
    private function renumberGroupBySignature(int $gameId, array $group): void
    {
        if (($group['inning'] ?? 0) < 1) {
            return;
        }

        if (($group['userId'] ?? null) === null && trim((string) ($group['userName'] ?? '')) === '') {
            return;
        }

        $query = BattingStats::where('gameId', $gameId)
            ->where('inning', (int) $group['inning'])
            ->orderBy('inningTurn')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate();

        if (($group['userId'] ?? null) !== null) {
            $query->where('userId', (int) $group['userId']);
        } else {
            $query->whereNull('userId')
                ->where('userName', trim((string) $group['userName']));
        }

        foreach ($query->get() as $index => $stat) {
            $newTurn = $index + 1;

            if ((int) $stat->inningTurn === $newTurn) {
                continue;
            }

            $stat->inningTurn = $newTurn;
            $stat->save();
        }
    }

    /**
     * 更新前の打者・イニング識別子を作る。
     */
    private function buildGroupSignatureFromStat(BattingStats $batting): array
    {
        return [
            'inning' => (int) $batting->inning,
            'userId' => $batting->userId ? (int) $batting->userId : null,
            'userName' => $batting->userId ? null : trim((string) $batting->userName),
        ];
    }

    /**
     * 入力値から打者・イニング識別子を作る。
     */
    private function buildGroupSignatureFromPayload(array $payload): array
    {
        return [
            'inning' => (int) ($payload['inning'] ?? 0),
            'userId' => filled($payload['userId'] ?? null) ? (int) $payload['userId'] : null,
            'userName' => filled($payload['userId'] ?? null) ? null : trim((string) ($payload['userName'] ?? '')),
        ];
    }

    /**
     * 打者・イニングの対象グループが同じか比較する。
     */
    private function sameGroupSignature(array $left, array $right): bool
    {
        return (int) ($left['inning'] ?? 0) === (int) ($right['inning'] ?? 0)
            && (int) ($left['userId'] ?? 0) === (int) ($right['userId'] ?? 0)
            && trim((string) ($left['userName'] ?? '')) === trim((string) ($right['userName'] ?? ''));
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
