<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class disbur extends Model
{
    use HasFactory;
    public function disburcategories() {
        return $this->belongsTo(disburCategories::class);
    }
}
