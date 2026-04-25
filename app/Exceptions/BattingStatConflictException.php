<?php

namespace App\Exceptions;

use App\Models\BattingStats;
use RuntimeException;

class BattingStatConflictException extends RuntimeException
{
    /**
     * 打撃入力でユーザー確認が必要な場合に投げる。
     */
    public function __construct(
        public readonly ?BattingStats $battingStat = null,
        string $message = '打撃入力内容の確認が必要です。',
        public readonly string $title = '確認が必要です。',
        public readonly string $resolution = 'duplicate'
    ) {
        parent::__construct($message);
    }
}
