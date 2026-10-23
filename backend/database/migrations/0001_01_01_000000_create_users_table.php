<?php

use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_educativas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->unique();
            $table->string('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('estado', 20)->default(EstadoUnidad::ACTIVA->value)->index();
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('nombre');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_educativa_id')->nullable()->constrained('unidades_educativas')->nullOnDelete();
            $table->string('rol', 20)->index();
            $table->string('ci', 30)->unique();
            $table->string('usuario')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('email')->nullable()->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('password');
            $table->boolean('debe_cambiar_password')->default(true);
            $table->string('estado', 20)->default(EstadoUsuario::ACTIVO->value)->index();
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unidad_educativa_id', 'rol']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('unidades_educativas');
    }
};
