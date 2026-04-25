<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseRunningEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'gameId',
        'inning',
        'actorOrderId',
        'actorUserId',
        'actorUserName',
        'startBase',
        'endBase',
        'eventType',
        'outsRecorded',
        'affectsState',
        'stateVersion',
        'createdBy',
        'meta',
    ];

    protected $casts = [
        'affectsState' => 'boolean',
        'meta' => 'array',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class, 'gameId');
    }
}
