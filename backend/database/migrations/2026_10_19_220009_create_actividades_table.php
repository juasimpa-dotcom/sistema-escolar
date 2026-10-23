<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_materia_id')->constrained('aula_materia')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_cumplimiento');
            $table->date('fecha_limite_notas')->nullable();
            $table->decimal('puntaje_maximo', 6, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['aula_materia_id', 'fecha_cumplimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
