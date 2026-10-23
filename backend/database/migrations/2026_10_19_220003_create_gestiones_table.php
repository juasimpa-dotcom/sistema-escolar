<?php

use App\Enums\EstadoGestion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->year('anio');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('es_actual')->default(false);
            $table->string('estado', 20)->default(EstadoGestion::ACTIVA->value)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['unidad_educativa_id', 'anio']);
            $table->index(['unidad_educativa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestiones');
    }
};
