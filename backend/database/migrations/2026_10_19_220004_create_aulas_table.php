<?php

use App\Enums\EstadoGenerico;
use App\Enums\Turno;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestion_id')->constrained('gestiones')->cascadeOnDelete();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->string('grado', 30);
            $table->string('paralelo', 5);
            $table->string('turno', 20)->default(Turno::MANANA->value);
            $table->string('estado', 20)->default(EstadoGenerico::ACTIVO->value)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gestion_id', 'grado', 'paralelo', 'turno']);
            $table->index(['unidad_educativa_id', 'gestion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aulas');
    }
};
