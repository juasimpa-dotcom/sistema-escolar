<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distritos_municipales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('macrodistrito_id')->nullable()->constrained('macrodistritos')->nullOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->string('nombre')->nullable();
            $table->timestamps();

            $table->unique('numero');
            $table->index('macrodistrito_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distritos_municipales');
    }
};
