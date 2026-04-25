<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

trait BuildsBattingTestData
{
    /**
     * 打撃系テストで使う試合を作る。
     */
    protected function createGame(string $gameName = 'テスト試合', string $enemyName = 'テスト相手'): int
    {
        return DB::table('games')->insertGetId([
            'gameName' => $gameName,
            'year' => 2026,
            'gameDates' => now(),
            'enemyName' => $enemyName,
            'gameFirstFlg' => 0,
            'winFlg' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'gameId');
    }

    /**
     * 打順を1件投入する。
     */
    protected function insertOrder(
        int $gameId,
        int $battingOrder,
        ?int $userId,
        int $positionId = 8,
        ?string $userName = null,
        int $ranking = 1
    ): int {
        return DB::table('batting_orders')->insertGetId([
            'gameId' => $gameId,
            'battingOrder' => $battingOrder,
            'positionId' => $positionId,
            'userId' => $userId,
            'userName' => $userName,
            'ranking' => $ranking,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'orderId');
    }

    /**
     * 打撃成績を1件投入する。
     */
    protected function insertBattingStat(
        int $gameId,
        ?int $userId,
        int $inning = 1,
        int $resultId1 = 1,
        int $resultId2 = 24,
        int $resultId3 = 32,
        mixed $createdAtOrInningTurn = null,
        ?int $inningTurn = null,
        ?string $userName = null
    ): int {
        $resolvedCreatedAt = now();
        $resolvedInningTurn = 1;

        if (is_int($createdAtOrInningTurn) && $inningTurn === null) {
            $resolvedInningTurn = $createdAtOrInningTurn;
        } else {
            $resolvedCreatedAt = $createdAtOrInningTurn ?? now();
            $resolvedInningTurn = $inningTurn ?? 1;
        }

        return DB::table('batting_stats')->insertGetId([
            'gameId' => $gameId,
            'userId' => $userId,
            'userName' => $userName,
            'inning' => $inning,
            'inningTurn' => $resolvedInningTurn,
            'resultId1' => $resultId1,
            'resultId2' => $resultId2,
            'resultId3' => $resultId3,
            'resultId4' => null,
            'resultId5' => null,
            'created_at' => $resolvedCreatedAt,
            'updated_at' => $resolvedCreatedAt,
        ]);
    }

    /**
     * 打順上の名前だけの選手を打撃成績へ投入する。
     */
    protected function insertNamedBattingStat(
        int $gameId,
        string $userName,
        int $inning,
        int $resultId1,
        int $resultId2,
        int $resultId3,
        mixed $createdAt = null,
        int $inningTurn = 1
    ): int {
        return $this->insertBattingStat(
            gameId: $gameId,
            userId: null,
            inning: $inning,
            resultId1: $resultId1,
            resultId2: $resultId2,
            resultId3: $resultId3,
            createdAtOrInningTurn: $createdAt,
            inningTurn: $inningTurn,
            userName: $userName,
        );
    }

    /**
     * 打撃確認メッセージの種類と文面を検証する。
     */
    protected function assertBattingConfirmation(
        TestResponse $response,
        string $resolution,
        ?string $titleContains = null,
        ?string $messageContains = null
    ): void {
        $response->assertSessionHas('batting_confirmation', function (array $confirmation) use ($resolution, $titleContains, $messageContains): bool {
            if (($confirmation['resolution'] ?? null) !== $resolution) {
                return false;
            }

            if ($titleContains !== null && ! str_contains((string) ($confirmation['title'] ?? ''), $titleContains)) {
                return false;
            }

            if ($messageContains !== null && ! str_contains((string) ($confirmation['message'] ?? ''), $messageContains)) {
                return false;
            }

            return true;
        });
    }

    /**
     * 試合詳細HTMLから対象選手の盗塁数を検証する。
     */
    protected function assertBattingStealCount(string $html, string $playerName, string $expectedCount): void
    {
        preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/su', $html, $rowMatches);

        foreach ($rowMatches[1] as $rowHtml) {
            preg_match_all('/<td\b[^>]*>(.*?)<\/td>/su', $rowHtml, $cellMatches);
            $cellTexts = array_map(function (string $cellHtml): string {
                $text = html_entity_decode(strip_tags($cellHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
            }, $cellMatches[1]);

            if (($cellTexts[2] ?? null) !== $playerName) {
                continue;
            }

            $this->assertSame($expectedCount, $cellTexts[7] ?? '');

            return;
        }

        $this->fail('対象選手の打撃成績行を特定できませんでした。');
    }
}
