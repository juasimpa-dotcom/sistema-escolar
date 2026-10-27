<?php

use App\Enums\NivelEducativo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aulas', function (Blueprint $table) {
            $table->string('nivel', 20)->default(NivelEducativo::PRIMARIA->value)->after('turno');
            $table->index(['unidad_educativa_id', 'nivel']);
        });
    }

    public function down(): void
    {
        Schema::table('aulas', function (Blueprint $table) {
            $table->dropIndex(['unidad_educativa_id', 'nivel']);
            $table->dropColumn('nivel');
        });
    }
};
