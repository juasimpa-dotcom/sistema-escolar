<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('resultado_final', 20)->nullable()->after('estado'); // en_curso | promovido | reprobado | retirado | abandono
            $table->decimal('promedio_final', 5, 2)->nullable()->after('resultado_final');
            $table->date('fecha_resultado')->nullable()->after('promedio_final');

            $table->index('resultado_final');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropIndex(['resultado_final']);
            $table->dropColumn(['resultado_final', 'promedio_final', 'fecha_resultado']);
        });
    }
};
