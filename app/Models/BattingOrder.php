<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Positions;
use App\Models\User;

class BattingOrder extends Model
{
    protected $primaryKey = 'orderId';
    use HasFactory;

    public function position()
    {
        return $this->belongsTo(Positions::class, 'positionId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
