<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices de rendimiento para columnas usadas en filtros/orden que no
     * estaban cubiertas. En PostgreSQL, foreignId()->constrained() crea la
     * restricción FK pero no un índice sobre la columna referenciante.
     */
    public function up(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->index('padre_id');
        });

        Schema::table('citaciones', function (Blueprint $table) {
            $table->index('estudiante_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->index(['aula_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->dropIndex(['padre_id']);
        });

        Schema::table('citaciones', function (Blueprint $table) {
            $table->dropIndex(['estudiante_id']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropIndex(['aula_id', 'estado']);
        });
    }
};
