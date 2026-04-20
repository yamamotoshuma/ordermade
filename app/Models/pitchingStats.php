<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pitchingStats extends Model
{
    protected $fillable = [
        'gameId',
        'userId',
        'pitchingOrder',//投げた順番　1＝先発　2以降中継ぎ等
        'result', // 勝敗
        'save', // セーブ
        'inning', // イニング
        'hitsAllowed',//被安打
        'homeRunsAllowed',//被本塁打
        'strikeouts',//奪三振
        'walks',//四死球
        'wildPitches',//暴投
        'balks',//ボーク
        'runsAllowed',//失点
        'earnedRuns',//自責点
    ];

    // リレーション: 試合との関連
    public function game()
    {
        return $this->belongsTo(Game::class, 'gameId');
    }

    // リレーション: 選手との関連
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
