<?php

use App\Enums\TipoUnidad;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades_educativas', function (Blueprint $table) {
            $table->foreignId('macrodistrito_id')->nullable()->after('direccion')->constrained('macrodistritos')->nullOnDelete();
            $table->foreignId('distrito_municipal_id')->nullable()->after('macrodistrito_id')->constrained('distritos_municipales')->nullOnDelete();
            $table->string('zona')->nullable()->after('direccion');
            $table->string('tipo', 20)->default(TipoUnidad::FISCAL->value)->after('estado');

            $table->index('macrodistrito_id');
            $table->index('distrito_municipal_id');
        });
    }

    public function down(): void
    {
        Schema::table('unidades_educativas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('macrodistrito_id');
            $table->dropConstrainedForeignId('distrito_municipal_id');
            $table->dropColumn(['zona', 'tipo']);
        });
    }
};
