<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avance_curricular', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->foreignId('aula_materia_id')->constrained('aula_materia')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->boolean('planificacion_entregada')->default(false);
            $table->date('fecha_entrega')->nullable();
            $table->unsignedInteger('clases_programadas')->default(0);
            $table->unsignedInteger('clases_dictadas')->default(0);
            $table->timestamps();

            $table->unique(['aula_materia_id', 'periodo_id']);
            $table->index('unidad_educativa_id');
            $table->index('periodo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avance_curricular');
    }
};
