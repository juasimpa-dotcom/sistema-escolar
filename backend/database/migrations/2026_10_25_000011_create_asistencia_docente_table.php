<?php

use App\Enums\EstadoAsistencia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia_docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->foreignId('profesor_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->string('estado', 20)->default(EstadoAsistencia::PRESENTE->value);
            $table->time('hora_ingreso')->nullable();
            $table->string('observacion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['unidad_educativa_id', 'profesor_id', 'fecha']);
            $table->index(['unidad_educativa_id', 'fecha']);
            $table->index(['profesor_id', 'fecha']);
            $table->index('registrado_por');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_docente');
    }
};
