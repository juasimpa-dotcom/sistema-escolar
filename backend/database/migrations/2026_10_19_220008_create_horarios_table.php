<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_materia_id')->constrained('aula_materia')->cascadeOnDelete();
            $table->string('dia', 20);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            $table->unique(['aula_materia_id', 'dia', 'hora_inicio']);
            $table->index(['dia', 'hora_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
