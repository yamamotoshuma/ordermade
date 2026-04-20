<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BattingStats extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'gameId');
    }

    public function result1()
    {
        return $this->belongsTo(BattingResultMaster::class, 'resultId1');
    }

    public function result2()
    {
        return $this->belongsTo(BattingResultMaster::class, 'resultId2');
    }

    public function result3()
    {
        return $this->belongsTo(BattingResultMaster::class, 'resultId3');
    }

    public function result4()
    {
        return $this->belongsTo(BattingResultMaster::class, 'resultId4');
    }

    public function result5()
    {
        return $this->belongsTo(BattingResultMaster::class, 'resultId5');
    }
}
