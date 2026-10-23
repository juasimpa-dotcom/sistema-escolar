<?php

use App\Enums\EstadoLicencia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('padre_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->text('motivo');
            $table->string('comprobante_path')->nullable();
            $table->string('estado', 20)->default(EstadoLicencia::PENDIENTE->value)->index();
            $table->foreignId('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_resolucion')->nullable();
            $table->string('motivo_rechazo')->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'estado']);
            $table->index(['fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};
