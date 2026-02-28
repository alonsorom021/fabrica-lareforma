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
        Schema::create('machine_changes', function (Blueprint $table) {
            $table->id();
            
            // Relación con la tabla machines
            $table->foreignId('machine_id')
                    ->constrained('machines')
                    ->onUpdate('cascade')
                    ->onDelete('cascade'); 
            $table->string('previous_gauge'); //Calibre Anterior
            $table->string('current_gauge'); //Calibre Actual
            $table->timestamps(); //Fecha y Hora
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_changes');
    }
};
