<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ficha_estudiante_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->string('tipo', 20); // academico | disciplinario | salud
            $table->date('fecha');
            $table->text('descripcion');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unidad_educativa_id', 'fecha']);
            $table->index(['estudiante_id', 'fecha']);
            $table->index('registrado_por');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_estudiante_eventos');
    }
};
