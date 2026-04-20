<?php

namespace App\Http\Controllers;

use App\Models\BattingOrder;
use App\Models\Game;
use App\Models\Positions;
use App\Models\User;
use App\Services\GoogleSheetsOrderImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BattingOrderController extends Controller
{
    public function index()
    {
        //
    }

    public function create(Game $game)
    {
        //
    }

    public function store(Request $request)
    {
        $gameId = (string) $request->input('gameId');

        try {
            DB::beginTransaction();

            $data = $this->buildOrderRowsFromRequest($request, $gameId);

            BattingOrder::where('gameId', $gameId)->delete();
            if ($data !== []) {
                BattingOrder::insert($data);
            }

            DB::commit();

            return redirect()->route('order.edit', ['order' => $gameId])->with('message', 'データが保存されました');
        } catch (RuntimeException $e) {
            DB::rollBack();

            return redirect()->route('order.edit', ['order' => $gameId])->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('order.edit', ['order' => $gameId])->withInput()->with('error', 'データの保存中にエラーが発生しました');
        }
    }

    public function importFromSpreadsheet(string $id, GoogleSheetsOrderImporter $importer)
    {
        try {
            $rows = $importer->fetchOrderRows();
            $result = $this->buildOrderRowsFromSpreadsheet($rows, $id);

            if ($result['rows'] !== []) {
                DB::transaction(function () use ($id, $result) {
                    BattingOrder::where('gameId', $id)->delete();
                    BattingOrder::insert($result['rows']);
                });
            }

            return redirect()
                ->route('order.edit', ['order' => $id])
                ->with('message', $this->buildSpreadsheetImportMessage($result));
        } catch (RuntimeException $e) {
            return redirect()->route('order.edit', ['order' => $id])->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->route('order.edit', ['order' => $id])->with('error', 'スプレッドシート反映中にエラーが発生しました');
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $positions = Positions::all();
        $orders = BattingOrder::where('gameId', $id)->with('position', 'user')->get();
        $users = User::where('active_flg', 1)->get();

        return view('order.edit', compact('orders', 'positions', 'users', 'id'));
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    private function buildOrderRowsFromRequest(Request $request, string $gameId): array
    {
        $data = [];
        $battingOrders = $request->input('battingOrder', []);
        $positionIds = $request->input('positionId', []);
        $userIds = $request->input('userId', []);
        $userNames = $request->input('userName', []);

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

            if ($positionId === '') {
                throw new RuntimeException('守備位置を入力してください。');
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

    private function buildOrderRowsFromSpreadsheet(array $rows, string $gameId): array
    {
        $positions = $this->buildPositionLookup();
        $users = $this->buildUserLookup();
        $userAliases = $this->buildUserAliasLookup();
        $data = [];
        $skippedPositionCount = 0;
        $manualNameCount = 0;

        foreach ($rows as $row) {
            $positionName = $this->normalizePositionName((string) $row['positionName']);
            $position = $positions[$this->normalizeLookupKey($positionName)] ?? null;

            if (! $position) {
                $skippedPositionCount++;
                continue;
            }

            $playerName = trim((string) $row['playerName']);
            $user = $this->resolveSpreadsheetUser($playerName, $users, $userAliases);

            if (! $user) {
                $manualNameCount++;
            }

            $data[] = [
                'gameId' => $gameId,
                'battingOrder' => (int) $row['battingOrder'],
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

    private function buildPositionLookup(): array
    {
        $lookup = [];

        foreach (Positions::all() as $position) {
            $lookup[$this->normalizeLookupKey((string) $position->positionName)] = $position;
        }

        return $lookup;
    }

    private function buildUserLookup(): array
    {
        $lookup = [];

        foreach (User::where('active_flg', 1)->get() as $user) {
            $lookup[$this->normalizeLookupKey((string) $user->name)] = $user;
        }

        return $lookup;
    }

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

    private function normalizeLookupKey(string $value): string
    {
        $normalized = mb_convert_kana(trim($value), 'asKV', 'UTF-8');
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;

        return mb_strtoupper($normalized);
    }
}
