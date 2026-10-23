<?php

use App\Enums\EstadoGenerico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->string('nombre');
            $table->string('estado', 20)->default(EstadoGenerico::ACTIVO->value)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['unidad_educativa_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
