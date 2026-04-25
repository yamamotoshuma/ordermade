<?php

namespace App\Services;

use App\Models\BaseRunningEvent;
use App\Models\BattingOrder;
use App\Models\BattingStats;
use App\Models\Game;
use App\Models\GameOffenseState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OffenseStateService
{
    /**
     * 走塁イベント種別。
     */
    public const EVENT_STOLEN_BASE = 'stolen_base';

    public const EVENT_DOUBLE_STEAL = 'double_steal';

    public const EVENT_ADVANCE = 'advance';

    public const EVENT_CAUGHT_STEALING = 'caught_stealing';

    public const EVENT_PICKOFF_OUT = 'pickoff_out';

    public const EVENT_RUNNER_OUT = 'runner_out';

    public const EVENT_MANUAL_PLACE = 'manual_place';

    public const EVENT_CLEAR_BASE = 'clear_base';

    /**
     * 走塁イベントと打撃結果を再生し、現在の攻撃状況をキャッシュする。
     */
    public function syncCachedState(Game $game, ?Collection $orders = null): GameOffenseState
    {
        $orders = $orders ?: $this->loadSelectableOrders($game);
        $battingStats = BattingStats::where('gameId', $game->gameId)
            ->with('result1')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $runnerEvents = BaseRunningEvent::where('gameId', $game->gameId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $snapshot = $this->buildStateSnapshot($orders, $battingStats, $runnerEvents);
        $existingState = GameOffenseState::where('gameId', $game->gameId)->first();

        if ($existingState && ! $this->stateHasChanged($existingState, $snapshot)) {
            return $existingState;
        }

        $state = $existingState ?: new GameOffenseState(['gameId' => $game->gameId]);
        $state->fill($snapshot);
        $state->version = $existingState ? ((int) $existingState->version + 1) : 1;
        $state->save();

        return $state->fresh();
    }

    /**
     * 打撃登録画面で使う現在の攻撃状況と初期値を組み立てる。
     */
    public function buildCreateStateData(
        Game $game,
        Collection $orders,
        Collection $battingStats,
        array $fallbackDefaults
    ): array {
        $state = $this->syncCachedState($game, $orders);
        $currentBatter = $this->participantFromState($state, 'batter', $orders);
        $currentOutCount = (int) $state->outCount;

        $manualRunnerOptions = $this->buildManualRunnerOptions($orders, $state);
        $combinedOutCounts = $this->buildCombinedInningOutCounts(
            $battingStats,
            BaseRunningEvent::where('gameId', $game->gameId)->get()
        );

        return [
            'defaultUserId' => $currentBatter['userId'] ?? ($fallbackDefaults['defaultUserId'] ?? ''),
            'defaultUserName' => $currentBatter['userName'] ?? ($fallbackDefaults['defaultUserName'] ?? ''),
            'defaultInning' => (int) ($state->inning ?: ($fallbackDefaults['defaultInning'] ?? 1)),
            'inningOutCounts' => $combinedOutCounts,
            'currentOutCount' => $currentOutCount,
            'currentStateInning' => (int) $state->inning,
            'offenseStateVersion' => (int) $state->version,
            'offenseState' => [
                'version' => (int) $state->version,
                'inning' => (int) $state->inning,
                'outCount' => $currentOutCount,
                'batterLabel' => $this->formatParticipantLabel($currentBatter, '未選択'),
                'needsRunnerConfirmation' => (bool) $state->needsRunnerConfirmation,
                'runnerConfirmationMessage' => $state->runnerConfirmationMessage,
                'bases' => [
                    $this->buildBaseView(1, $this->participantFromState($state, 'first', $orders)),
                    $this->buildBaseView(2, $this->participantFromState($state, 'second', $orders)),
                    $this->buildBaseView(3, $this->participantFromState($state, 'third', $orders)),
                ],
                'manualRunnerOptions' => $manualRunnerOptions,
            ],
        ];
    }

    /**
     * 打撃登録と走塁操作を同じ試合単位で直列化する。
     */
    public function withGameStateLock(Game $game, callable $callback): mixed
    {
        if (! $this->supportsMysqlNamedLocks()) {
            return $callback();
        }

        $lockName = 'offense-state:' . sha1((string) $game->gameId);
        $lock = DB::selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockName]);

        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw new RuntimeException('他の端末が走者状況を更新中です。少し待ってから再度操作してください。');
        }

        try {
            return $callback();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }
    }

    /**
     * 画面が見ていた攻撃状況と現在の状態が一致しているか確認する。
     */
    public function assertExpectedVersion(Game $game, ?int $expectedVersion): void
    {
        if (! $expectedVersion) {
            return;
        }

        $state = GameOffenseState::where('gameId', $game->gameId)->first();
        $currentVersion = (int) ($state?->version ?? 1);

        if ($currentVersion !== $expectedVersion) {
            throw new RuntimeException('打撃・走塁状況が他の端末で更新されました。画面を開き直してから再度入力してください。');
        }
    }

    /**
     * 打撃登録画面の走者操作から走塁イベントを追加する。
     */
    public function recordRunnerEvent(Game $game, array $payload): BaseRunningEvent
    {
        return $this->withGameStateLock($game, function () use ($game, $payload): BaseRunningEvent {
            $state = $this->syncCachedState($game);
            $this->assertExpectedVersion($game, (int) ($payload['offenseStateVersion'] ?? 0));
            $eventPayloads = $this->buildRunnerEventPayloads($state, $payload);
            $event = null;

            foreach ($eventPayloads as $eventPayload) {
                $event = BaseRunningEvent::create([
                    'gameId' => $game->gameId,
                    'inning' => (int) $state->inning,
                    'actorOrderId' => $eventPayload['participant']['orderId'] ?? null,
                    'actorUserId' => $eventPayload['participant']['userId'] ?? null,
                    'actorUserName' => $eventPayload['participant']['userName'] ?? null,
                    'startBase' => $eventPayload['startBase'],
                    'endBase' => $eventPayload['endBase'],
                    'eventType' => $eventPayload['eventType'],
                    'outsRecorded' => $eventPayload['outsRecorded'],
                    'affectsState' => true,
                    'stateVersion' => (int) $state->version,
                    'createdBy' => Auth::id(),
                    'meta' => $eventPayload['meta'],
                ]);
            }

            if (! $event) {
                throw new RuntimeException('走者操作の保存に失敗しました。');
            }

            $this->syncCachedState($game);

            return $event;
        });
    }

    /**
     * 打撃登録画面の直前走塁操作だけを取り消す。
     */
    public function deleteLatestRunnerEvent(Game $game, int $expectedVersion): void
    {
        $this->withGameStateLock($game, function () use ($game, $expectedVersion): void {
            $this->assertExpectedVersion($game, $expectedVersion);

            $event = BaseRunningEvent::where('gameId', $game->gameId)
                ->where('affectsState', true)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if (! $event) {
                throw new RuntimeException('取り消せる走者操作がありません。');
            }

            BaseRunningEvent::whereIn('id', $this->collectOperationEventIds($game, $event))->delete();
            $this->syncCachedState($game);
        });
    }

    /**
     * 旧盗塁画面の加算は、状態に影響しない盗塁イベントとして残す。
     */
    public function createLegacyStealEvent(Game $game, int $userId): void
    {
        BaseRunningEvent::create([
            'gameId' => $game->gameId,
            'inning' => null,
            'actorOrderId' => null,
            'actorUserId' => $userId,
            'actorUserName' => null,
            'startBase' => null,
            'endBase' => null,
            'eventType' => self::EVENT_STOLEN_BASE,
            'outsRecorded' => 0,
            'affectsState' => false,
            'stateVersion' => null,
            'createdBy' => Auth::id(),
            'meta' => ['source' => 'legacy_screen'],
        ]);
    }

    /**
     * 最新の盗塁イベントを取り消す。
     */
    public function deleteLatestStealEvent(Game $game, int $userId): void
    {
        $this->withGameStateLock($game, function () use ($game, $userId): void {
            $event = BaseRunningEvent::where('gameId', $game->gameId)
                ->where('actorUserId', $userId)
                ->where('eventType', self::EVENT_STOLEN_BASE)
                ->where('affectsState', false)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if (! $event) {
                throw new RuntimeException('取り消せる盗塁がありません。');
            }

            $shouldSync = (bool) $event->affectsState;
            $event->delete();

            if ($shouldSync) {
                $this->syncCachedState($game);
            }
        });
    }

    /**
     * 試合詳細などで使う盗塁数集計。
     */
    public function getStealCounts(Game $game): Collection
    {
        return BaseRunningEvent::where('gameId', $game->gameId)
            ->where('eventType', self::EVENT_STOLEN_BASE)
            ->get()
            // userId が無い助っ人・登録外選手も userName 単位で盗塁数を返す。
            ->filter(function (BaseRunningEvent $event): bool {
                return $event->actorUserId !== null || trim((string) $event->actorUserName) !== '';
            })
            ->groupBy(function (BaseRunningEvent $event): string {
                if ($event->actorUserId !== null) {
                    return 'id:' . $event->actorUserId;
                }

                return 'name:' . trim((string) $event->actorUserName);
            })
            ->map(function (Collection $events): object {
                /** @var BaseRunningEvent $first */
                $first = $events->first();

                return (object) [
                    'userId' => $first->actorUserId !== null ? (int) $first->actorUserId : null,
                    'userName' => $first->actorUserId !== null ? null : trim((string) $first->actorUserName),
                    'count' => $events->count(),
                ];
            })
            ->values();
    }

    /**
     * 打撃アウトと走塁アウトを合わせたイニング別アウト数。
     */
    public function buildCombinedInningOutCounts(Collection $battingStats, Collection $runnerEvents): array
    {
        $outCounts = [];

        foreach ($battingStats as $stat) {
            $inning = (int) $stat->inning;
            $outCounts[$inning] = ($outCounts[$inning] ?? 0) + $this->resultOutCount((string) optional($stat->result1)->name);
        }

        foreach ($runnerEvents as $event) {
            if (! $event->affectsState || ! $event->inning) {
                continue;
            }

            $inning = (int) $event->inning;
            $outCounts[$inning] = ($outCounts[$inning] ?? 0) + (int) $event->outsRecorded;
        }

        ksort($outCounts);

        return $outCounts;
    }

    /**
     * 打撃と走塁イベントの履歴を時系列で再生して状態を作る。
     */
    private function buildStateSnapshot(Collection $orders, Collection $battingStats, Collection $runnerEvents): array
    {
        $state = [
            'inning' => 1,
            'outCount' => 0,
            'batter' => $this->participantFromOrder($orders->first()),
            'bases' => [1 => null, 2 => null, 3 => null],
            'needsRunnerConfirmation' => false,
            'runnerConfirmationMessage' => null,
        ];

        $timeline = $battingStats->map(function ($stat) {
            return [
                'type' => 'batting',
                'timestamp' => optional($stat->created_at)->format('Y-m-d H:i:s.u') ?: '',
                'id' => (int) $stat->id,
                'payload' => $stat,
            ];
        })->merge($runnerEvents->map(function ($event) {
            return [
                'type' => 'runner',
                'timestamp' => optional($event->created_at)->format('Y-m-d H:i:s.u') ?: '',
                'id' => (int) $event->id,
                'payload' => $event,
            ];
        }))->sort(function (array $left, array $right): int {
            $timestampCompare = strcmp($left['timestamp'], $right['timestamp']);

            if ($timestampCompare !== 0) {
                return $timestampCompare;
            }

            if ($left['type'] !== $right['type']) {
                return $left['type'] === 'batting' ? -1 : 1;
            }

            return $left['id'] <=> $right['id'];
        })->values();

        foreach ($timeline as $item) {
            if ($item['type'] === 'batting') {
                $this->applyBattingStat($state, $item['payload'], $orders);
                continue;
            }

            $event = $item['payload'];

            if (! $event->affectsState) {
                continue;
            }

            $this->applyRunnerEvent($state, $event, $orders);
        }

        return [
            'inning' => (int) $state['inning'],
            'outCount' => (int) $state['outCount'],
            'batterOrderId' => $state['batter']['orderId'] ?? null,
            'batterUserId' => $state['batter']['userId'] ?? null,
            'batterUserName' => $state['batter']['userName'] ?? null,
            'firstOrderId' => $state['bases'][1]['orderId'] ?? null,
            'firstUserId' => $state['bases'][1]['userId'] ?? null,
            'firstUserName' => $state['bases'][1]['userName'] ?? null,
            'secondOrderId' => $state['bases'][2]['orderId'] ?? null,
            'secondUserId' => $state['bases'][2]['userId'] ?? null,
            'secondUserName' => $state['bases'][2]['userName'] ?? null,
            'thirdOrderId' => $state['bases'][3]['orderId'] ?? null,
            'thirdUserId' => $state['bases'][3]['userId'] ?? null,
            'thirdUserName' => $state['bases'][3]['userName'] ?? null,
            'needsRunnerConfirmation' => (bool) $state['needsRunnerConfirmation'],
            'runnerConfirmationMessage' => $state['runnerConfirmationMessage'],
        ];
    }

    /**
     * 打撃結果から現在の攻撃状況を進める。
     */
    private function applyBattingStat(array &$state, BattingStats $stat, Collection $orders): void
    {
        $participant = $this->participantFromStat($stat, $orders);
        $resultName = trim((string) optional($stat->result1)->name);
        $hadRunners = $this->hasRunners($state);
        $needsRunnerConfirmation = false;

        switch ($resultName) {
            case '四球':
            case '死球':
                $this->cascadeAdvanceFromBase($state, 1);
                $this->setBaseOccupant($state, 1, $participant);
                break;
            case '安打':
                $this->cascadeAdvanceFromBase($state, 1);
                $this->setBaseOccupant($state, 1, $participant);
                $needsRunnerConfirmation = $hadRunners;
                break;
            case 'エラー':
            case 'FC':
            case '振逃':
                $this->cascadeAdvanceFromBase($state, 1);
                $this->setBaseOccupant($state, 1, $participant);
                $needsRunnerConfirmation = true;
                break;
            case '二塁打':
                $this->cascadeAdvanceFromBase($state, 2);
                $this->setBaseOccupant($state, 2, $participant);
                $needsRunnerConfirmation = $hadRunners;
                break;
            case '三塁打':
                $this->cascadeAdvanceFromBase($state, 3);
                $this->setBaseOccupant($state, 3, $participant);
                $needsRunnerConfirmation = $hadRunners;
                break;
            case '本塁打':
                $state['bases'] = [1 => null, 2 => null, 3 => null];
                break;
            case '併殺':
                $this->consumeLeadRunnerOut($state);
                $needsRunnerConfirmation = $hadRunners;
                break;
            case '三重殺':
                $this->consumeLeadRunnerOut($state);
                $this->consumeLeadRunnerOut($state);
                $needsRunnerConfirmation = $hadRunners;
                break;
            case '犠打':
            case '犠飛':
                $needsRunnerConfirmation = $hadRunners;
                break;
        }

        $state['outCount'] += $this->resultOutCount($resultName);
        $inningEnded = false;

        if ($state['outCount'] >= 3) {
            $state['inning']++;
            $state['outCount'] = 0;
            $state['bases'] = [1 => null, 2 => null, 3 => null];
            $inningEnded = true;
        }

        $state['batter'] = $this->nextParticipant($orders, $participant);

        if ($inningEnded) {
            $state['needsRunnerConfirmation'] = false;
            $state['runnerConfirmationMessage'] = null;

            return;
        }

        if ($needsRunnerConfirmation) {
            $state['needsRunnerConfirmation'] = true;
            $state['runnerConfirmationMessage'] = '直前の打撃結果の後に走者状況を確認してください。';

            return;
        }

        $state['needsRunnerConfirmation'] = false;
        $state['runnerConfirmationMessage'] = null;
    }

    /**
     * 走塁イベントを現在状態へ反映する。
     */
    private function applyRunnerEvent(array &$state, BaseRunningEvent $event, Collection $orders): void
    {
        $participant = $this->participantFromEvent($event, $orders);

        if (in_array($event->eventType, [self::EVENT_STOLEN_BASE, self::EVENT_ADVANCE, self::EVENT_CAUGHT_STEALING, self::EVENT_PICKOFF_OUT, self::EVENT_RUNNER_OUT, self::EVENT_MANUAL_PLACE], true)) {
            $this->clearParticipantFromBases($state, $participant);
        }

        if ($event->eventType === self::EVENT_MANUAL_PLACE && $event->endBase >= 1 && $event->endBase <= 3) {
            $this->setBaseOccupant($state, (int) $event->endBase, $participant);
        }

        if (in_array($event->eventType, [self::EVENT_STOLEN_BASE, self::EVENT_ADVANCE], true) && $event->endBase >= 1 && $event->endBase <= 3) {
            $this->advanceLeadRunnersAndSetBaseOccupant($state, (int) $event->endBase, $participant);
        }

        if ($event->eventType === self::EVENT_CLEAR_BASE && $event->startBase >= 1 && $event->startBase <= 3) {
            $state['bases'][(int) $event->startBase] = null;
        }

        $state['outCount'] += (int) $event->outsRecorded;

        if ($state['outCount'] >= 3) {
            $state['inning']++;
            $state['outCount'] = 0;
            $state['bases'] = [1 => null, 2 => null, 3 => null];
        }

        $state['needsRunnerConfirmation'] = false;
        $state['runnerConfirmationMessage'] = null;
    }

    /**
     * 現在の走者状況からイベントの記録内容を決める。
     */
    private function buildRunnerEventPayloads(GameOffenseState $state, array $payload): array
    {
        $action = (string) ($payload['action'] ?? '');
        $operationId = (string) Str::uuid();

        if ($action === self::EVENT_MANUAL_PLACE) {
            $participant = $this->participantFromPayload($payload);
            $targetBase = (int) ($payload['targetBase'] ?? 0);

            if ($targetBase < 1 || $targetBase > 3) {
                throw new RuntimeException('配置先の塁を選択してください。');
            }

            return [$this->makeRunnerEventPayload(
                participant: $participant,
                startBase: null,
                endBase: $targetBase,
                eventType: self::EVENT_MANUAL_PLACE,
                outsRecorded: 0,
                meta: $this->buildOperationMeta($operationId, self::EVENT_MANUAL_PLACE, '手動配置', ['label' => '手動配置'])
            )];
        }

        $base = (int) ($payload['base'] ?? 0);
        $participant = $this->participantFromStateBase($state, $base);

        if (! $participant) {
            throw new RuntimeException('選択した塁に走者がいません。');
        }

        return match ($action) {
            self::EVENT_STOLEN_BASE => [$this->makeRunnerEventPayload(
                participant: $participant,
                startBase: $base,
                endBase: min($base + 1, 4),
                eventType: self::EVENT_STOLEN_BASE,
                outsRecorded: 0,
                meta: $this->buildOperationMeta(
                    $operationId,
                    self::EVENT_STOLEN_BASE,
                    $base === 3 ? '本盗' : '盗塁',
                    ['label' => $base === 3 ? '本盗' : '盗塁']
                )
            )],
            self::EVENT_DOUBLE_STEAL => $this->buildDoubleStealPayloads($state, $base, $operationId),
            self::EVENT_ADVANCE => [$this->makeRunnerEventPayload(
                participant: $participant,
                startBase: $base,
                endBase: min($base + 1, 4),
                eventType: self::EVENT_ADVANCE,
                outsRecorded: 0,
                meta: $this->buildOperationMeta(
                    $operationId,
                    self::EVENT_ADVANCE,
                    $base === 3 ? '生還' : '進塁',
                    ['label' => $base === 3 ? '生還' : '進塁']
                )
            )],
            self::EVENT_CAUGHT_STEALING => [$this->makeRunnerEventPayload(
                participant: $participant,
                startBase: $base,
                endBase: 0,
                eventType: self::EVENT_CAUGHT_STEALING,
                outsRecorded: 1,
                meta: $this->buildOperationMeta(
                    $operationId,
                    self::EVENT_CAUGHT_STEALING,
                    $base === 3 ? '本盗死' : '盗塁死',
                    ['label' => $base === 3 ? '本盗死' : '盗塁死']
                )
            )],
            self::EVENT_PICKOFF_OUT => [$this->makeRunnerEventPayload(
                participant: $participant,
                startBase: $base,
                endBase: 0,
                eventType: self::EVENT_PICKOFF_OUT,
                outsRecorded: 1,
                meta: $this->buildOperationMeta($operationId, self::EVENT_PICKOFF_OUT, '牽制死', ['label' => '牽制死'])
            )],
            self::EVENT_RUNNER_OUT => [$this->makeRunnerEventPayload(
                participant: $participant,
                startBase: $base,
                endBase: 0,
                eventType: self::EVENT_RUNNER_OUT,
                outsRecorded: 1,
                meta: $this->buildOperationMeta($operationId, self::EVENT_RUNNER_OUT, '走塁死', ['label' => '走塁死'])
            )],
            self::EVENT_CLEAR_BASE => [$this->makeRunnerEventPayload(
                participant: $participant,
                startBase: $base,
                endBase: 0,
                eventType: self::EVENT_CLEAR_BASE,
                outsRecorded: 0,
                meta: $this->buildOperationMeta($operationId, self::EVENT_CLEAR_BASE, 'ベースを空にする', ['label' => 'ベースを空にする'])
            )],
            default => throw new RuntimeException('不正な走者操作です。'),
        };
    }

    /**
     * 重盗は前の塁もまとめて盗塁扱いにする。
     */
    private function buildDoubleStealPayloads(GameOffenseState $state, int $base, string $operationId): array
    {
        if ($base < 1 || $base > 2) {
            throw new RuntimeException('重盗にできるのは一塁走者か二塁走者だけです。');
        }

        $participants = [];

        for ($currentBase = $base; $currentBase <= 3; $currentBase++) {
            $participant = $this->participantFromStateBase($state, $currentBase);

            if (! $participant) {
                break;
            }

            $participants[] = [
                'base' => $currentBase,
                'participant' => $participant,
            ];
        }

        if (count($participants) < 2) {
            throw new RuntimeException('重盗にするには次の塁にも走者が必要です。');
        }

        return collect(array_reverse($participants))
            ->map(function (array $entry) use ($operationId, $participants): array {
                $startBase = (int) $entry['base'];
                $endBase = min($startBase + 1, 4);

                return $this->makeRunnerEventPayload(
                    participant: $entry['participant'],
                    startBase: $startBase,
                    endBase: $endBase,
                    eventType: self::EVENT_STOLEN_BASE,
                    outsRecorded: 0,
                    meta: $this->buildOperationMeta(
                        $operationId,
                        self::EVENT_DOUBLE_STEAL,
                        '重盗',
                        [
                            'label' => $endBase === 4 ? '本盗' : '盗塁',
                            'operationRunnerCount' => count($participants),
                        ]
                    )
                );
            })
            ->values()
            ->all();
    }

    /**
     * 1件分の走塁イベント配列を組み立てる。
     */
    private function makeRunnerEventPayload(?array $participant, ?int $startBase, int $endBase, string $eventType, int $outsRecorded, array $meta): array
    {
        return [
            'participant' => $participant,
            'startBase' => $startBase,
            'endBase' => $endBase,
            'eventType' => $eventType,
            'outsRecorded' => $outsRecorded,
            'meta' => $meta,
        ];
    }

    /**
     * 取り消し用に1操作単位のメタ情報を付ける。
     */
    private function buildOperationMeta(string $operationId, string $operationType, string $operationLabel, array $extra = []): array
    {
        return array_merge($extra, [
            'operationId' => $operationId,
            'operationType' => $operationType,
            'operationLabel' => $operationLabel,
        ]);
    }

    /**
     * 同じ操作で作られたイベントはまとめて取り消す。
     */
    private function collectOperationEventIds(Game $game, BaseRunningEvent $event): array
    {
        $operationId = trim((string) data_get($event->meta, 'operationId'));

        if ($operationId === '') {
            return [(int) $event->id];
        }

        return BaseRunningEvent::where('gameId', $game->gameId)
            ->where('affectsState', true)
            ->get()
            ->filter(function (BaseRunningEvent $candidate) use ($operationId): bool {
                return trim((string) data_get($candidate->meta, 'operationId')) === $operationId;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * 現在状態から指定塁の走者を取り出す。
     */
    private function participantFromStateBase(GameOffenseState $state, int $base): ?array
    {
        return match ($base) {
            1 => $this->participantFromState($state, 'first', collect()),
            2 => $this->participantFromState($state, 'second', collect()),
            3 => $this->participantFromState($state, 'third', collect()),
            default => null,
        };
    }

    /**
     * キャッシュ状態の prefix 付きカラムを参加者配列へ戻す。
     */
    private function participantFromState(GameOffenseState $state, string $prefix, Collection $orders): ?array
    {
        $orderIdColumn = $prefix . 'OrderId';
        $userIdColumn = $prefix . 'UserId';
        $userNameColumn = $prefix . 'UserName';
        $orderId = $state->{$orderIdColumn};
        $userId = $state->{$userIdColumn};
        $userName = trim((string) $state->{$userNameColumn});

        if (! $orderId && ! $userId && $userName === '') {
            return null;
        }

        $order = $orders->firstWhere('orderId', $orderId);

        return [
            'orderId' => $orderId ? (int) $orderId : null,
            'battingOrder' => $order ? (int) $order->battingOrder : null,
            'ranking' => $order ? (int) $order->ranking : null,
            'userId' => $userId ? (int) $userId : null,
            'userName' => $userId ? null : ($userName !== '' ? $userName : null),
            'displayName' => $this->resolveParticipantName($order, $userId ? null : $userName),
        ];
    }

    /**
     * 打順情報から参加者を作る。
     */
    private function participantFromOrder(?BattingOrder $order): ?array
    {
        if (! $order) {
            return null;
        }

        $displayName = $this->resolveParticipantName($order, null);

        if ($displayName === '') {
            return null;
        }

        return [
            'orderId' => (int) $order->orderId,
            'battingOrder' => (int) $order->battingOrder,
            'ranking' => (int) $order->ranking,
            'userId' => $order->userId ? (int) $order->userId : null,
            'userName' => $order->userId ? null : trim((string) $order->userName),
            'displayName' => $displayName,
        ];
    }

    /**
     * 打撃成績から参加者を決める。
     */
    private function participantFromStat(BattingStats $stat, Collection $orders): ?array
    {
        $order = $orders->first(function ($order) use ($stat) {
            if ($stat->userId) {
                return (int) $order->userId === (int) $stat->userId;
            }

            return trim((string) $order->userName) === trim((string) $stat->userName);
        });

        if ($order) {
            return $this->participantFromOrder($order);
        }

        if (! $stat->userId && ! $stat->userName) {
            return null;
        }

        return [
            'orderId' => null,
            'battingOrder' => null,
            'ranking' => null,
            'userId' => $stat->userId ? (int) $stat->userId : null,
            'userName' => $stat->userId ? null : trim((string) $stat->userName),
            'displayName' => trim((string) optional($stat->user)->name ?: $stat->userName),
        ];
    }

    /**
     * 走塁イベントの記録値から参加者を取り出す。
     */
    private function participantFromEvent(BaseRunningEvent $event, Collection $orders): ?array
    {
        $order = $orders->firstWhere('orderId', $event->actorOrderId);

        return [
            'orderId' => $event->actorOrderId ? (int) $event->actorOrderId : null,
            'battingOrder' => $order ? (int) $order->battingOrder : null,
            'ranking' => $order ? (int) $order->ranking : null,
            'userId' => $event->actorUserId ? (int) $event->actorUserId : null,
            'userName' => $event->actorUserId ? null : ($event->actorUserName ?: null),
            'displayName' => $this->resolveParticipantName($order, $event->actorUserName),
        ];
    }

    /**
     * POST 値の userId / userName から手動配置対象を作る。
     */
    private function participantFromPayload(array $payload): array
    {
        if (! empty($payload['userId'])) {
            return [
                'orderId' => ! empty($payload['orderId']) ? (int) $payload['orderId'] : null,
                'userId' => (int) $payload['userId'],
                'userName' => null,
                'displayName' => trim((string) ($payload['displayName'] ?? '')),
            ];
        }

        $userName = trim((string) ($payload['userName'] ?? ''));

        if ($userName === '') {
            throw new RuntimeException('走者として配置する選手を選択してください。');
        }

        return [
            'orderId' => ! empty($payload['orderId']) ? (int) $payload['orderId'] : null,
            'userId' => null,
            'userName' => $userName,
            'displayName' => trim((string) ($payload['displayName'] ?? $userName)),
        ];
    }

    /**
     * 次打者を打順から解決する。
     */
    private function nextParticipant(Collection $orders, ?array $participant): ?array
    {
        if ($orders->isEmpty()) {
            return null;
        }

        if (! $participant) {
            return $this->participantFromOrder($orders->first());
        }

        $matchedIndex = $orders->search(function ($order) use ($participant) {
            if (($participant['orderId'] ?? null) && (int) $order->orderId === (int) $participant['orderId']) {
                return true;
            }

            if (($participant['userId'] ?? null) && (int) $order->userId === (int) $participant['userId']) {
                return true;
            }

            return ! ($participant['userId'] ?? null)
                && trim((string) $order->userName) === trim((string) ($participant['userName'] ?? ''));
        });

        if ($matchedIndex === false) {
            return $this->participantFromOrder($orders->first());
        }

        return $this->participantFromOrder($orders->get(((int) $matchedIndex + 1) % $orders->count()));
    }

    /**
     * 参加者を指定塁へ配置する。
     */
    private function setBaseOccupant(array &$state, int $base, ?array $participant): void
    {
        if (! $participant || $base < 1 || $base > 3) {
            return;
        }

        $this->clearParticipantFromBases($state, $participant);
        $state['bases'][$base] = $participant;
    }

    /**
     * 進塁先に別走者がいる場合は先行走者を1つ先へ順送りしてから配置する。
     *
     * 例:
     * - 一二塁で一塁走者が盗塁したら、二塁走者は三塁へ進めてから一塁走者を二塁へ置く
     * - 満塁で一塁走者が盗塁したら、三塁走者は本塁生還扱いで盤面から消す
     */
    private function advanceLeadRunnersAndSetBaseOccupant(array &$state, int $base, ?array $participant): void
    {
        if (! $participant || $base < 1 || $base > 3) {
            return;
        }

        $this->clearParticipantFromBases($state, $participant);
        $this->cascadeAdvanceFromBase($state, $base);
        $state['bases'][$base] = $participant;
    }

    /**
     * 既存の塁上から同一参加者を取り除く。
     */
    private function clearParticipantFromBases(array &$state, ?array $participant): void
    {
        if (! $participant) {
            return;
        }

        $targetKey = $this->participantKey($participant);

        foreach ([1, 2, 3] as $base) {
            if ($this->participantKey($state['bases'][$base] ?? null) === $targetKey) {
                $state['bases'][$base] = null;
            }
        }
    }

    /**
     * 指定塁から1つ先へ押し出す。ホームに達した走者は消す。
     */
    private function cascadeAdvanceFromBase(array &$state, int $base): void
    {
        if ($base < 1 || $base > 3 || ! ($state['bases'][$base] ?? null)) {
            return;
        }

        $occupant = $state['bases'][$base];
        $state['bases'][$base] = null;
        $nextBase = $base + 1;

        if ($nextBase > 3) {
            return;
        }

        $this->cascadeAdvanceFromBase($state, $nextBase);
        $state['bases'][$nextBase] = $occupant;
    }

    /**
     * 併殺などで追加アウトにする先頭走者を消す。
     */
    private function consumeLeadRunnerOut(array &$state): void
    {
        foreach ([3, 2, 1] as $base) {
            if ($state['bases'][$base] ?? null) {
                $state['bases'][$base] = null;

                return;
            }
        }
    }

    /**
     * 走者の有無を返す。
     */
    private function hasRunners(array $state): bool
    {
        foreach ([1, 2, 3] as $base) {
            if ($state['bases'][$base] ?? null) {
                return true;
            }
        }

        return false;
    }

    /**
     * 参加者を比較するキー。
     */
    private function participantKey(?array $participant): string
    {
        if (! $participant) {
            return '';
        }

        if (! empty($participant['orderId'])) {
            return 'order:' . $participant['orderId'];
        }

        if (! empty($participant['userId'])) {
            return 'id:' . $participant['userId'];
        }

        return 'name:' . trim((string) ($participant['userName'] ?? ''));
    }

    /**
     * 状態スナップショットが変わったか比較する。
     */
    private function stateHasChanged(GameOffenseState $state, array $snapshot): bool
    {
        $columns = [
            'inning',
            'outCount',
            'batterOrderId',
            'batterUserId',
            'batterUserName',
            'firstOrderId',
            'firstUserId',
            'firstUserName',
            'secondOrderId',
            'secondUserId',
            'secondUserName',
            'thirdOrderId',
            'thirdUserId',
            'thirdUserName',
            'needsRunnerConfirmation',
            'runnerConfirmationMessage',
        ];

        foreach ($columns as $column) {
            if ($state->{$column} != $snapshot[$column]) {
                return true;
            }
        }

        return false;
    }

    /**
     * 打順と表示名を含む塁表示データ。
     */
    private function buildBaseView(int $base, ?array $participant): array
    {
        $baseName = match ($base) {
            1 => '一塁',
            2 => '二塁',
            3 => '三塁',
        };

        return [
            'base' => $base,
            'baseName' => $baseName,
            'occupied' => (bool) $participant,
            'label' => $this->formatParticipantLabel($participant, '走者なし'),
            'shortLabel' => $participant['displayName'] ?? '空',
        ];
    }

    /**
     * 画面表示用の参加者ラベル。
     */
    private function formatParticipantLabel(?array $participant, string $fallback): string
    {
        if (! $participant) {
            return $fallback;
        }

        $name = trim((string) ($participant['displayName'] ?? $participant['userName'] ?? ''));

        if ($name === '') {
            return $fallback;
        }

        $order = $participant['battingOrder'] ?? null;
        $ranking = $participant['ranking'] ?? null;

        if ($order) {
            $suffix = $ranking && (int) $ranking > 1 ? '-' . $ranking : '';

            return $order . '番' . $suffix . ' ' . $name;
        }

        return $name;
    }

    /**
     * 走者手動配置用の選択肢。
     */
    private function buildManualRunnerOptions(Collection $orders, GameOffenseState $state): array
    {
        $occupiedKeys = collect([
            $this->participantKey($this->participantFromState($state, 'first', $orders)),
            $this->participantKey($this->participantFromState($state, 'second', $orders)),
            $this->participantKey($this->participantFromState($state, 'third', $orders)),
        ])->filter()->flip();

        return $orders->map(function ($order) use ($occupiedKeys) {
            $participant = $this->participantFromOrder($order);

            if (! $participant) {
                return null;
            }

            if ($occupiedKeys->has($this->participantKey($participant))) {
                return null;
            }

            return [
                'orderId' => $participant['orderId'],
                'userId' => $participant['userId'],
                'userName' => $participant['userName'],
                'displayName' => $participant['displayName'],
                'label' => $this->formatParticipantLabel($participant, ''),
            ];
        })->filter()->values()->all();
    }

    /**
     * 打順から実際に選択肢として使える行だけを残す。
     */
    private function loadSelectableOrders(Game $game): Collection
    {
        return BattingOrder::where('gameId', $game->gameId)
            ->with('user')
            ->orderBy('battingOrder')
            ->orderBy('ranking')
            ->get()
            ->filter(function ($order) {
                if ($order->userId) {
                    return (bool) optional($order->user)->active_flg;
                }

                return filled($order->userName);
            })
            ->values();
    }

    /**
     * ユーザー/打順情報から表示名を決める。
     */
    private function resolveParticipantName(?BattingOrder $order, ?string $fallbackUserName): string
    {
        if ($order?->user) {
            return trim((string) $order->user->name);
        }

        if ($order && filled($order->userName)) {
            return trim((string) $order->userName);
        }

        return trim((string) $fallbackUserName);
    }

    /**
     * 打撃結果名からアウト数を返す。
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
     * MySQL 環境では名前付きロックを使う。
     */
    private function supportsMysqlNamedLocks(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }
}
