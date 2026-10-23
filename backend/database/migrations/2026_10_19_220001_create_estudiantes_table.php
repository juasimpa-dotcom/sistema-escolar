<?php

use App\Enums\EstadoUsuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('estado', 20)->default(EstadoUsuario::ACTIVO->value)->index();
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');
            $table->index(['unidad_educativa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
