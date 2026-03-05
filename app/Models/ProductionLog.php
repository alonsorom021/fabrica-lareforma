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
            'user_id',
            'user_stop_id',
            'status',
            'shift',
            'start_time',
            'end_time',
            'observation',
            'edited_by_operator',
        ];
        protected $casts = [
            'edited_by_operator' => 'boolean',
        ];
        public function machine(): BelongsTo
        {
            return $this->belongsTo(Machine::class);
        } 
        public function user():BelongsTo
        {
            return $this->belongsTo(User::class);
        }
        public function operatorStop(): BelongsTo
        {
            return $this->belongsTo(User::class, 'user_stop_id');
        }
}
