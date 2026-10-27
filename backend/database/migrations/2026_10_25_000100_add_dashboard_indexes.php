<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de soporte para las consultas agregadas del dashboard (GROUP BY)
 * que aún no estaban cubiertos por los índices existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->index('fecha_limite_notas');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['rol', 'estado']);
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->index(['gestion_id', 'resultado_final']);
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropIndex(['fecha_limite_notas']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rol', 'estado']);
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropIndex(['gestion_id', 'resultado_final']);
        });
    }
};
