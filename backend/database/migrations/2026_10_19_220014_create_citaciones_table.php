<?php

use App\Enums\EstadoCitacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->constrained('unidades_educativas')->restrictOnDelete();
            $table->foreignId('emisor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('destinatario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('asunto');
            $table->text('mensaje');
            $table->dateTime('fecha_hora');
            $table->string('lugar')->nullable();
            $table->string('estado', 20)->default(EstadoCitacion::ENVIADA->value)->index();
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();

            $table->index(['destinatario_id', 'estado']);
            $table->index(['unidad_educativa_id', 'fecha_hora']);
            $table->index('emisor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citaciones');
    }
};
