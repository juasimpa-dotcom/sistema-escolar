<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->foreignId('gestion_id')->constrained('gestiones')->cascadeOnDelete();
            $table->string('nombre', 60);
            $table->string('tipo', 20); // bimestre | trimestre | semestre
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gestion_id', 'nombre']);
            $table->index('unidad_educativa_id');
            $table->index('gestion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
