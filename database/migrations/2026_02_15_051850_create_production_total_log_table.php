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
        Schema::create('production_total_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('shift', 50);
            $table->decimal('real_kg', 12, 3);
            $table->decimal('objective_kg', 12, 3);
            $table->decimal('efficiency', 5, 2)->default(0);
            $table->date('date_select')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }
        
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_total_log');
    }
};
