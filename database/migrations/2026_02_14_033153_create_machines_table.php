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
            $table->string('name'); // Ejemplo: Trocil N
            $table->string('machine_gauge')->nullable(); // Calibre de Máquina
            $table->boolean('is_active')->default(true); //Estado de la maquina
            $table->timestamps(); //Fecha y Hora
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
