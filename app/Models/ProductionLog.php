<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionLog extends Model
{
        protected $table = 'production_log';
        protected $fillable = [
            'machine_id',
            'kg_produced',
            'start_time',
            'end_time',
            'user_id',
            'shift',
            'observation',
        ];
        public function machine(): BelongsTo
        {
            return $this->belongsTo(Machine::class);
        } 
        public function user():BelongsTo
        {
            return $this->belongsTo(User::class);
        }
}
