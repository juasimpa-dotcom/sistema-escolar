<?php

use App\Enums\EstadoPlaza;
use App\Enums\NivelEducativo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plazas_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->foreignId('materia_id')->nullable()->constrained('materias')->nullOnDelete();
            $table->string('nivel', 20)->default(NivelEducativo::SECUNDARIA->value);
            $table->string('estado', 20)->default(EstadoPlaza::VACANTE->value);
            $table->foreignId('profesor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('unidad_educativa_id');
            $table->index('materia_id');
            $table->index('profesor_id');
            $table->index(['unidad_educativa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plazas_docentes');
    }
};
