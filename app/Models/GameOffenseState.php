<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameOffenseState extends Model
{
    use HasFactory;

    protected $fillable = [
        'gameId',
        'inning',
        'outCount',
        'batterOrderId',
        'batterUserId',
        'batterUserName',
        'firstOrderId',
        'firstUserId',
        'firstUserName',
        'secondOrderId',
        'secondUserId',
        'secondUserName',
        'thirdOrderId',
        'thirdUserId',
        'thirdUserName',
        'version',
        'needsRunnerConfirmation',
        'runnerConfirmationMessage',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class, 'gameId');
    }
}
