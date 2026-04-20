<?php

namespace App\Services;

use App\Models\BattingOrder;
use App\Models\BattingStats;
use App\Models\Game;
use App\Models\pitchingStats;
use App\Models\Point;
use App\Models\Steal;
use Illuminate\Support\Facades\DB;

class GameService
{
    /**
     * 試合一覧の年フィルタと表示データをまとめて返す。
     */
    public function getIndexData(?int $year): array
    {
        $targetYear = $year ?: (int) date('Y');

        return [
            'games' => Game::where('year', $targetYear)->orderBy('gameDates', 'desc')->get(),
            'points' => Point::all(),
        ];
    }

    /**
     * 試合詳細画面で必要な関連データをまとめて取得する。
     */
    public function getShowData(Game $game): array
    {
        return [
            'game' => $game,
            'points' => Point::where('gameId', $game->gameId)->get(),
            'orders' => BattingOrder::where('gameId', $game->gameId)
                ->with('position', 'user')
                ->orderBy('battingOrder', 'asc')
                ->get(),
            'battingStats' => BattingStats::where('gameId', $game->gameId)
                ->with('user', 'result1', 'result2', 'result3', 'result4', 'result5')
                ->get(),
            'stealCounts' => Steal::select('userId', DB::raw('count(*) as count'))
                ->where('gameId', $game->gameId)
                ->whereNotNull('userId')
                ->groupBy('userId')
                ->get(),
            'pitchingStats' => pitchingStats::where('gameId', $game->gameId)
                ->with('user', 'game')
                ->orderBy('pitchingOrder', 'asc')
                ->get(),
        ];
    }

    /**
     * 試合編集画面では点数情報もまとめて返す。
     */
    public function getEditData(Game $game): array
    {
        return [
            'game' => $game,
            'points' => Point::where('gameId', $game->gameId)->get(),
        ];
    }

    /**
     * 新規試合を登録する。
     */
    public function create(array $payload): Game
    {
        return DB::transaction(function () use ($payload): Game {
            $game = new Game();
            $game->gameName = $payload['gameName'];
            $game->year = (int) $payload['year'];
            $game->gameDates = $payload['gameDates'];
            $game->enemyName = $payload['enemyName'];
            $game->gameFirstFlg = (int) $payload['gameFirstFlg'];
            $game->save();

            return $game;
        });
    }

    /**
     * 試合基本情報を更新する。
     */
    public function update(Game $game, array $payload): Game
    {
        return DB::transaction(function () use ($game, $payload): Game {
            $game->gameName = $payload['gameName'];
            $game->year = (int) $payload['year'];
            $game->gameDates = $payload['gameDates'];
            $game->enemyName = $payload['enemyName'];
            $game->gameFirstFlg = (int) $payload['gameFirstFlg'];
            $game->winFlg = $payload['winFlg'] === null || $payload['winFlg'] === ''
                ? null
                : (int) $payload['winFlg'];
            $game->save();

            return $game;
        });
    }

    /**
     * イニング別スコアを一括で更新または新規登録する。
     */
    public function upsertScores(int $gameId, array $inningData): void
    {
        DB::transaction(function () use ($gameId, $inningData): void {
            foreach ($inningData as $inningNumber => $inningScores) {
                foreach ($inningScores as $inningSide => $score) {
                    if ($score === null || $score === '') {
                        continue;
                    }

                    Point::updateOrCreate(
                        [
                            'gameId' => $gameId,
                            'inning' => (int) $inningNumber,
                            'inning_side' => (int) $inningSide,
                        ],
                        [
                            'score' => (int) $score,
                        ]
                    );
                }
            }
        });
    }

    /**
     * 試合を削除する。
     */
    public function delete(Game $game): void
    {
        $game->delete();
    }
}
