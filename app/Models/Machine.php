<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $guarded = [];

    public function productions() {
        return $this->hasMany(ProductionLog::class);
    }
}
