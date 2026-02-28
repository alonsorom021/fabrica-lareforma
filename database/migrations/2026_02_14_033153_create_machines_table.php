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
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('yarn')->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->integer('usage');
            $table->integer('yarn_weight')->nullable();
            $table->decimal('productive_cap', 8, 2)->nullable();
            $table->decimal('shift_cap', 12, 6)->nullable();
            $table->decimal('real_val', 12, 6)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }
        
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
