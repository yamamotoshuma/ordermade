<?php

namespace App\Services;

use App\Models\BattingOrder;
use App\Models\Game;
use App\Models\Positions;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BattingOrderService
{
    /**
     * 打順編集画面に必要なマスタと既存打順をまとめて返す。
     */
    public function getEditData(string $gameId, array $oldInput = []): array
    {
        $orders = BattingOrder::where('gameId', $gameId)
            ->with('position', 'user')
            ->orderBy('battingOrder')
            ->orderBy('ranking')
            ->orderBy('orderId')
            ->get();

        return [
            'positions' => Positions::all(),
            'orders' => $orders,
            'orderRows' => $this->buildEditRows($orders, $oldInput),
            'users' => User::where('active_flg', 1)->get(),
            'id' => $gameId,
        ];
    }

    /**
     * 手入力された打順を検証しながら保存用の行データに変換する。
     */
    public function store(string $gameId, array $payload): void
    {
        $rows = $this->buildOrderRowsFromRequest($payload, $gameId);

        DB::transaction(function () use ($gameId, $rows): void {
            BattingOrder::where('gameId', $gameId)->delete();

            if ($rows !== []) {
                BattingOrder::insert($rows);
            }
        });
    }

    /**
     * スプレッドシート由来の打順を既存データに反映し、結果メッセージも返す。
     */
    public function importFromSpreadsheet(string $gameId, array $rows): string
    {
        $result = $this->buildOrderRowsFromSpreadsheet($rows, $gameId);

        if ($result['rows'] !== []) {
            DB::transaction(function () use ($gameId, $result): void {
                BattingOrder::where('gameId', $gameId)->delete();
                BattingOrder::insert($result['rows']);
            });
        }

        return $this->buildSpreadsheetImportMessage($result);
    }

    /**
     * 打順登録の対象となる試合が存在することを明示的に確認する。
     */
    public function ensureGameExists(string $gameId): void
    {
        Game::findOrFail($gameId);
    }

    /**
     * フォーム配列を1行ずつ検証し、保存可能な形に整える。
     */
    private function buildOrderRowsFromRequest(array $payload, string $gameId): array
    {
        $data = [];
        $battingOrders = $payload['battingOrder'] ?? [];
        $positionIds = $payload['positionId'] ?? [];
        $userIds = $payload['userId'] ?? [];
        $userNames = $payload['userName'] ?? [];

        foreach ($battingOrders as $key => $battingOrder) {
            $battingOrder = trim((string) $battingOrder);
            $positionId = trim((string) ($positionIds[$key] ?? ''));
            $userId = trim((string) ($userIds[$key] ?? ''));
            $userName = trim((string) ($userNames[$key] ?? ''));
            $hasRowData = $positionId !== '' || $userId !== '' || $userName !== '';

            if (! $hasRowData) {
                continue;
            }

            if ($battingOrder === '' || ! is_numeric($battingOrder)) {
                throw new RuntimeException('打順を正しく入力してください。');
            }

            if ($positionId === '' || ! is_numeric($positionId)) {
                throw new RuntimeException('守備位置を入力してください。');
            }

            if ($userId !== '' && ! is_numeric($userId)) {
                throw new RuntimeException('選手を正しく選択してください。');
            }

            if ($userId !== '' && $userName !== '') {
                throw new RuntimeException('選手と選手名は同時に入力しないでください。');
            }

            if ($userId === '' && $userName === '') {
                throw new RuntimeException('選手または選手名を入力してください。');
            }

            $data[] = [
                'gameId' => $gameId,
                'battingOrder' => (int) $battingOrder,
                'positionId' => (int) $positionId,
                'userId' => $userId === '' ? null : (int) $userId,
                'userName' => $userName === '' ? null : $userName,
                'ranking' => 1,
            ];
        }

        return $this->normalizeRankings($data);
    }

    /**
     * 編集画面でそのまま描画できる行配列を作る。
     */
    private function buildEditRows(Collection $orders, array $oldInput): array
    {
        if (isset($oldInput['battingOrder']) && is_array($oldInput['battingOrder'])) {
            return $this->buildEditRowsFromOldInput($oldInput);
        }

        $rows = [];
        $maxBattingOrder = max(9, (int) ($orders->max('battingOrder') ?? 0));
        $groupedOrders = $orders->groupBy(fn (BattingOrder $order): int => (int) $order->battingOrder);

        for ($battingOrder = 1; $battingOrder <= $maxBattingOrder; $battingOrder++) {
            $orderGroup = $groupedOrders->get($battingOrder, collect());

            if ($orderGroup->isEmpty()) {
                $rows[] = $this->makeEditRow((string) $battingOrder, '', '', '', 1);
                continue;
            }

            foreach ($orderGroup as $order) {
                $rows[] = $this->makeEditRow(
                    (string) $battingOrder,
                    (string) $order->positionId,
                    $order->userId === null ? '' : (string) $order->userId,
                    (string) ($order->userName ?? ''),
                    (int) ($order->ranking ?? 1)
                );
            }
        }

        return $this->normalizeEditRowRankings($rows);
    }

    /**
     * 保存エラー時は入力済みの行を優先して再表示する。
     */
    private function buildEditRowsFromOldInput(array $oldInput): array
    {
        $battingOrders = is_array($oldInput['battingOrder'] ?? null) ? $oldInput['battingOrder'] : [];
        $positionIds = is_array($oldInput['positionId'] ?? null) ? $oldInput['positionId'] : [];
        $userIds = is_array($oldInput['userId'] ?? null) ? $oldInput['userId'] : [];
        $userNames = is_array($oldInput['userName'] ?? null) ? $oldInput['userName'] : [];
        $rankings = is_array($oldInput['ranking'] ?? null) ? $oldInput['ranking'] : [];
        $rowCount = max(
            9,
            count($battingOrders),
            count($positionIds),
            count($userIds),
            count($userNames)
        );
        $rows = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $rows[] = $this->makeEditRow(
                (string) ($battingOrders[$index] ?? ($index + 1)),
                (string) ($positionIds[$index] ?? ''),
                (string) ($userIds[$index] ?? ''),
                (string) ($userNames[$index] ?? ''),
                (int) ($rankings[$index] ?? 1)
            );
        }

        return $this->normalizeEditRowRankings($rows);
    }

    private function makeEditRow(
        string $battingOrder,
        string $positionId,
        string $userId,
        string $userName,
        int $ranking
    ): array {
        return [
            'battingOrder' => $battingOrder,
            'positionId' => $positionId,
            'userId' => $userId,
            'userName' => $userName,
            'ranking' => max(1, $ranking),
        ];
    }

    /**
     * 画面表示用の ranking も保存時と同じルールで揃える。
     */
    private function normalizeEditRowRankings(array $rows): array
    {
        $rankings = [];

        foreach ($rows as $index => $row) {
            $battingOrder = trim((string) ($row['battingOrder'] ?? ''));

            if ($battingOrder === '') {
                $rows[$index]['ranking'] = 1;
                continue;
            }

            $rankings[$battingOrder] = ($rankings[$battingOrder] ?? 0) + 1;
            $rows[$index]['ranking'] = $rankings[$battingOrder];
        }

        return $rows;
    }

    /**
     * スプレッドシートの行を既存のマスタに合わせて解釈する。
     */
    private function buildOrderRowsFromSpreadsheet(array $rows, string $gameId): array
    {
        $positions = $this->buildPositionLookup();
        $users = $this->buildUserLookup();
        $userAliases = $this->buildUserAliasLookup();
        $data = [];
        $skippedPositionCount = 0;
        $manualNameCount = 0;

        foreach ($rows as $row) {
            $positionName = $this->normalizePositionName((string) ($row['positionName'] ?? ''));
            $position = $positions[$this->normalizeLookupKey($positionName)] ?? null;

            if (! $position) {
                $skippedPositionCount++;
                continue;
            }

            $playerName = trim((string) ($row['playerName'] ?? ''));
            $user = $this->resolveSpreadsheetUser($playerName, $users, $userAliases);

            if (! $user) {
                $manualNameCount++;
            }

            $data[] = [
                'gameId' => $gameId,
                'battingOrder' => (int) ($row['battingOrder'] ?? 0),
                'positionId' => (int) $position->positionId,
                'userId' => $user?->id,
                'userName' => $user ? null : $playerName,
                'ranking' => 1,
            ];
        }

        return [
            'rows' => $this->normalizeRankings($data),
            'sourceCount' => count($rows),
            'skippedPositionCount' => $skippedPositionCount,
            'manualNameCount' => $manualNameCount,
        ];
    }

    /**
     * 表記ゆれの多い守備位置名をシステム内の正式表記へ寄せる。
     */
    private function normalizePositionName(string $positionName): string
    {
        $normalized = mb_strtoupper(trim($positionName));

        return match ($normalized) {
            'DH', 'ＤＨ', '指', '指名打者' => '指',
            '投', '投手', 'P', 'Ｐ', 'ピッチャー' => '投',
            '捕', '捕手', 'C', 'Ｃ', 'キャッチャー' => '捕',
            '一', '一塁', '一塁手', '1B', '１Ｂ', 'ファースト' => '一',
            '二', '二塁', '二塁手', '2B', '２Ｂ', 'セカンド' => '二',
            '三', '三塁', '三塁手', '3B', '３Ｂ', 'サード' => '三',
            '遊', '遊撃', '遊撃手', 'SS', 'ＳＳ', 'ショート' => '遊',
            '左', '左翼', '左翼手', 'LF', 'ＬＦ', 'レフト' => '左',
            '中', '中堅', '中堅手', 'CF', 'ＣＦ', 'センター' => '中',
            '右', '右翼', '右翼手', 'RF', 'ＲＦ', 'ライト' => '右',
            default => trim($positionName),
        };
    }

    /**
     * 同じ打順が複数ある場合は上から順に ranking を採番し直す。
     */
    private function normalizeRankings(array $rows): array
    {
        $rankings = [];

        foreach ($rows as $index => $row) {
            $battingOrder = (int) $row['battingOrder'];
            $rankings[$battingOrder] = ($rankings[$battingOrder] ?? 0) + 1;
            $rows[$index]['ranking'] = $rankings[$battingOrder];
        }

        return $rows;
    }

    /**
     * 守備位置を高速に引けるようにキー付き配列を作る。
     */
    private function buildPositionLookup(): array
    {
        $lookup = [];

        foreach (Positions::all() as $position) {
            $lookup[$this->normalizeLookupKey((string) $position->positionName)] = $position;
        }

        return $lookup;
    }

    /**
     * 現役ユーザー名を表記ゆれ吸収後のキーで引けるようにする。
     */
    private function buildUserLookup(): array
    {
        $lookup = [];

        foreach (User::where('active_flg', 1)->get() as $user) {
            $lookup[$this->normalizeLookupKey((string) $user->name)] = $user;
        }

        return $lookup;
    }

    /**
     * .env / config で管理する選手名エイリアスを検索しやすい形に変換する。
     */
    private function buildUserAliasLookup(): array
    {
        $configuredAliases = config('services.google_sheets.user_aliases', []);

        if (! is_array($configuredAliases)) {
            return [];
        }

        $lookup = [];

        foreach ($configuredAliases as $alias => $canonicalName) {
            if (! is_string($alias) || ! is_string($canonicalName)) {
                continue;
            }

            $aliasKey = $this->normalizeLookupKey($alias);
            $canonicalKey = $this->normalizeLookupKey($canonicalName);

            if ($aliasKey === '' || $canonicalKey === '') {
                continue;
            }

            $lookup[$aliasKey] = $canonicalKey;
        }

        return $lookup;
    }

    /**
     * 名前一致またはエイリアス一致したユーザーを返す。
     */
    private function resolveSpreadsheetUser(string $playerName, array $users, array $userAliases): ?User
    {
        $playerKey = $this->normalizeLookupKey($playerName);

        if ($playerKey === '') {
            return null;
        }

        if (isset($users[$playerKey])) {
            return $users[$playerKey];
        }

        $aliasTarget = $userAliases[$playerKey] ?? null;

        if ($aliasTarget === null) {
            return null;
        }

        return $users[$aliasTarget] ?? null;
    }

    /**
     * 取り込み結果をそのまま画面に出せる日本語メッセージへ整形する。
     */
    private function buildSpreadsheetImportMessage(array $result): string
    {
        $messages = [];

        if ($result['rows'] === []) {
            $messages[] = 'スプレッドシート内に反映できる打順がなかったため、既存の打順は変更していません。';
        } else {
            $messages[] = 'スプレッドシートのオーダーを反映しました。';
        }

        if (($result['skippedPositionCount'] ?? 0) > 0) {
            $messages[] = sprintf('守備位置を判定できない %d 行はスキップしました。', $result['skippedPositionCount']);
        }

        if (($result['manualNameCount'] ?? 0) > 0) {
            $messages[] = sprintf('一致しない選手名 %d 件は登録外選手として取り込みました。', $result['manualNameCount']);
        }

        if (($result['sourceCount'] ?? 0) === 0) {
            $messages[] = 'シート内に対象データがありませんでした。';
        }

        return implode(' ', $messages);
    }

    /**
     * 名前比較用に全角半角や空白を吸収したキーを生成する。
     */
    private function normalizeLookupKey(string $value): string
    {
        $normalized = mb_convert_kana(trim($value), 'asKV', 'UTF-8');
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;

        return mb_strtoupper($normalized);
    }
}
