<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasFactory;
    protected $primaryKey = 'pointId';

    protected $fillable = [
        'gameId',
        'inning',
        'inning_side',
        'score',
    ];
}
