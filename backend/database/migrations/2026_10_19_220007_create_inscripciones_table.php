<?php

use App\Enums\EstadoInscripcion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('aula_id')->constrained('aulas')->restrictOnDelete();
            $table->foreignId('gestion_id')->constrained('gestiones')->restrictOnDelete();
            $table->date('fecha_inscripcion')->useCurrent();
            $table->string('estado', 20)->default(EstadoInscripcion::INSCRITO->value)->index();
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['estudiante_id', 'gestion_id']);
            $table->index(['aula_id', 'gestion_id']);
            $table->index(['gestion_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
