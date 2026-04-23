<?php

namespace App\Exceptions;

use App\Models\BattingStats;
use RuntimeException;

class BattingStatConflictException extends RuntimeException
{
    /**
     * 衝突した既存打撃成績を画面側へ返すために保持する。
     */
    public function __construct(
        public readonly BattingStats $battingStat,
        string $message = 'すでに打撃データが存在します。既存データを更新しますか？'
    ) {
        parent::__construct($message);
    }
}
