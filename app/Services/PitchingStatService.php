<?php

namespace App\Services;

use App\Models\Game;
use App\Models\pitchingStats;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PitchingStatService
{
    /**
     * 投手成績一覧に必要なデータを返す。
     */
    public function getIndexData(Game $game): array
    {
        return [
            'pitchingStats' => pitchingStats::where('gameId', $game->gameId)
                ->with('user', 'game')
                ->orderBy('pitchingOrder', 'asc')
                ->get(),
            'game' => $game,
        ];
    }

    /**
     * 投手成績の新規登録画面に必要な候補を返す。
     */
    public function getCreateData(int $gameId): array
    {
        Game::findOrFail($gameId);

        return [
            'gameId' => $gameId,
            'users' => User::where('active_flg', 1)->get(),
        ];
    }

    /**
     * 投手成績の編集画面に必要な候補を返す。
     */
    public function getEditData(pitchingStats $pitching): array
    {
        return [
            'pitching' => $pitching,
            'users' => User::where('active_flg', 1)->get(),
        ];
    }

    /**
     * 投手成績の基本行を登録する。
     */
    public function create(int $gameId, array $payload): pitchingStats
    {
        return DB::transaction(function () use ($gameId, $payload): pitchingStats {
            $pitching = new pitchingStats();
            $pitching->gameId = $gameId;
            $pitching->pitchingOrder = (int) $payload['pitchingOrder'];
            $pitching->userId = (int) $payload['userId'];
            $pitching->result = $payload['result'] ?? null;
            $pitching->save = $payload['save'] ?? null;
            $pitching->save();

            return $pitching;
        });
    }

    /**
     * 投手成績の基本項目を更新する。
     */
    public function update(pitchingStats $pitching, array $payload): pitchingStats
    {
        return DB::transaction(function () use ($pitching, $payload): pitchingStats {
            $pitching->pitchingOrder = (int) $payload['pitchingOrder'];
            $pitching->userId = (int) $payload['userId'];
            $pitching->result = $payload['result'] ?? null;
            $pitching->save = $payload['save'] ?? null;
            $pitching->save();

            return $pitching;
        });
    }

    /**
     * 個別のカウント項目だけを更新する。
     */
    public function updateNumber(pitchingStats $pitching, array $payload): pitchingStats
    {
        $field = $this->resolveUpdatableField((string) $payload['type']);
        $value = $payload[$field] ?? null;

        if ($value === null || $value === '') {
            throw new RuntimeException('更新値を指定してください。');
        }

        return DB::transaction(function () use ($pitching, $field, $value): pitchingStats {
            $pitching->{$field} = is_numeric($value) ? (float) $value : $value;
            $pitching->save();

            return $pitching;
        });
    }

    /**
     * 投手成績を削除し、戻り先の試合も返す。
     */
    public function delete(pitchingStats $pitching): Game
    {
        return DB::transaction(function () use ($pitching): Game {
            $game = Game::findOrFail($pitching->gameId);
            $pitching->delete();

            return $game;
        });
    }

    /**
     * 更新可能な数値項目だけを受け付ける。
     */
    private function resolveUpdatableField(string $type): string
    {
        return match ($type) {
            'inning' => 'inning',
            'hitsAllowed' => 'hitsAllowed',
            'homeRunsAllowed' => 'homeRunsAllowed',
            'strikeouts' => 'strikeouts',
            'walks' => 'walks',
            'wildPitches' => 'wildPitches',
            'balks' => 'balks',
            'runsAllowed' => 'runsAllowed',
            'earnedRuns' => 'earnedRuns',
            default => throw new RuntimeException('更新対象の項目が不正です。'),
        };
    }
}
