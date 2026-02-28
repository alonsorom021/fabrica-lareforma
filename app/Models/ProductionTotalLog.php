<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionTotalLog extends Model
{
    protected $table = 'production_total_log';
    protected $guarded = [];

    public function machine(): BelongsTo
    {
       return $this->belongsTo(\App\Models\Machine::class, 'machine_id');
    } 
}