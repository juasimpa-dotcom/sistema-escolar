<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('macrodistritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->string('codigo', 10);
            $table->string('nombre');
            $table->string('tipo', 10); // urbano | rural
            $table->timestamps();

            $table->unique('codigo');
            $table->index('municipio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('macrodistritos');
    }
};
