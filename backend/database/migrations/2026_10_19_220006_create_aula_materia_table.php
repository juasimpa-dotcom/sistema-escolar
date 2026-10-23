<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aula_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->foreignId('profesor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['aula_id', 'materia_id']);
            $table->index('profesor_id');
            $table->index('materia_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aula_materia');
    }
};
