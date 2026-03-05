<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->decimal('kg_produced', 12, 3);
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('user_stop_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('status', ['En Curso', 'Completa'])
                ->default('En Curso');
            $table->string('shift', 50);
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->text('observation')->nullable();
            $table->boolean('edited_by_operator')->default(false);
            $table->timestamps();
        });
    }
        
    public function down(): void
    {
        Schema::dropIfExists('production_log');
    }
};
