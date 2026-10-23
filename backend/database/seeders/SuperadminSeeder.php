<?php

namespace Database\Seeders;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $ci = '10000001';

        User::updateOrCreate(
            ['ci' => $ci],
            [
                'unidad_educativa_id' => null,
                'rol' => Rol::SUPERADMIN,
                'usuario' => $ci,
                'nombres' => 'Super',
                'apellidos' => 'Administrador',
                'email' => 'superadmin@sistema.test',
                'telefono' => '70000001',
                'password' => Hash::make($ci),
                'debe_cambiar_password' => true,
                'estado' => EstadoUsuario::ACTIVO,
            ]
        );
    }
}
