<?php

namespace Database\Seeders;

use App\Enums\DiaSemana;
use App\Enums\EstadoAsistencia;
use App\Enums\EstadoCitacion;
use App\Enums\EstadoGenerico;
use App\Enums\EstadoGestion;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoLicencia;
use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\Parentesco;
use App\Enums\Rol;
use App\Enums\TipoCitacion;
use App\Enums\Turno;
use App\Models\Actividad;
use App\Models\Asistencia;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\Citacion;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\UnidadEducativa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $unidad = UnidadEducativa::updateOrCreate(
            ['codigo' => 'UE-DEMO'],
            [
                'nombre' => 'Unidad Educativa Demo',
                'direccion' => 'Av. Principal 123, La Paz',
                'telefono' => '22222222',
                'estado' => EstadoUnidad::ACTIVA,
            ]
        );

        $gestion = Gestion::updateOrCreate(
            ['unidad_educativa_id' => $unidad->id, 'anio' => now()->year],
            [
                'fecha_inicio' => now()->year.'-02-01',
                'fecha_fin' => now()->year.'-12-15',
                'es_actual' => true,
                'estado' => EstadoGestion::ACTIVA,
            ]
        );

        $director = $this->crearUsuario('20000001', Rol::DIRECTOR, 'María', 'Rojas Vargas', $unidad);
        $administrativo = $this->crearUsuario('20000002', Rol::ADMINISTRATIVO, 'Carlos', 'Mamani Quispe', $unidad);
        $profesorMate = $this->crearUsuario('20000003', Rol::PROFESOR, 'Ana', 'Flores Choque', $unidad);
        $profesorLeng = $this->crearUsuario('20000004', Rol::PROFESOR, 'Luis', 'Huanca Mamani', $unidad);
        $padre = $this->crearUsuario('20000005', Rol::PADRE, 'Jorge', 'Pérez Condori', $unidad);

        $estudiante1 = $this->crearEstudiante('20000006', 'Lucía', 'Pérez Mamani', $unidad, '2015-03-12');
        $estudiante2 = $this->crearEstudiante('20000007', 'Diego', 'Pérez Mamani', $unidad, '2017-08-25');

        foreach ([$estudiante1, $estudiante2] as $hijo) {
            if (! $padre->hijos()->whereKey($hijo->id)->exists()) {
                $padre->hijos()->attach($hijo->id, ['parentesco' => Parentesco::PADRE->value]);
            }
        }

        $aula1 = Aula::updateOrCreate(
            ['gestion_id' => $gestion->id, 'grado' => '1ro', 'paralelo' => 'A', 'turno' => Turno::MANANA->value],
            ['unidad_educativa_id' => $unidad->id, 'estado' => EstadoGenerico::ACTIVO]
        );
        $aula2 = Aula::updateOrCreate(
            ['gestion_id' => $gestion->id, 'grado' => '5to', 'paralelo' => 'B', 'turno' => Turno::TARDE->value],
            ['unidad_educativa_id' => $unidad->id, 'estado' => EstadoGenerico::ACTIVO]
        );

        $mates = Materia::updateOrCreate(
            ['unidad_educativa_id' => $unidad->id, 'nombre' => 'Matemáticas'],
            ['estado' => EstadoGenerico::ACTIVO]
        );
        $lenguaje = Materia::updateOrCreate(
            ['unidad_educativa_id' => $unidad->id, 'nombre' => 'Lenguaje y Literatura'],
            ['estado' => EstadoGenerico::ACTIVO]
        );

        $am1 = AulaMateria::updateOrCreate(
            ['aula_id' => $aula1->id, 'materia_id' => $mates->id],
            ['profesor_id' => $profesorMate->id]
        );
        $am2 = AulaMateria::updateOrCreate(
            ['aula_id' => $aula1->id, 'materia_id' => $lenguaje->id],
            ['profesor_id' => $profesorLeng->id]
        );
        $am3 = AulaMateria::updateOrCreate(
            ['aula_id' => $aula2->id, 'materia_id' => $mates->id],
            ['profesor_id' => $profesorMate->id]
        );

        foreach ([[$am1, DiaSemana::LUNES, '08:00', '08:45'], [$am1, DiaSemana::MIERCOLES, '08:00', '08:45'], [$am2, DiaSemana::MARTES, '08:45', '09:30'], [$am3, DiaSemana::JUEVES, '14:00', '14:45']] as [$am, $dia, $ini, $fin]) {
            Horario::updateOrCreate(
                ['aula_materia_id' => $am->id, 'dia' => $dia->value, 'hora_inicio' => $ini],
                ['hora_fin' => $fin]
            );
        }

        Inscripcion::updateOrCreate(
            ['estudiante_id' => $estudiante1->id, 'gestion_id' => $gestion->id],
            ['aula_id' => $aula1->id, 'fecha_inscripcion' => now()->subMonths(2)->toDateString(), 'estado' => EstadoInscripcion::INSCRITO]
        );
        Inscripcion::updateOrCreate(
            ['estudiante_id' => $estudiante2->id, 'gestion_id' => $gestion->id],
            ['aula_id' => $aula1->id, 'fecha_inscripcion' => now()->subMonths(2)->toDateString(), 'estado' => EstadoInscripcion::INSCRITO]
        );

        $actividad = Actividad::firstOrCreate(
            ['aula_materia_id' => $am1->id, 'titulo' => 'Examen parcial de Matemáticas'],
            [
                'descripcion' => 'Examen parcial sobre las cuatro operaciones.',
                'fecha_cumplimiento' => now()->subWeek()->toDateString(),
                'fecha_limite_notas' => now()->addWeek()->toDateString(),
                'puntaje_maximo' => 100,
            ]
        );

        foreach ([[$estudiante1, 85.5], [$estudiante2, 92]] as [$est, $valor]) {
            Nota::firstOrCreate(
                ['actividad_id' => $actividad->id, 'estudiante_id' => $est->id],
                [
                    'nota' => $valor,
                    'fue_modificada' => false,
                    'registrado_por' => $profesorMate->id,
                ]
            );
        }

        foreach ([$estudiante1, $estudiante2] as $est) {
            Asistencia::firstOrCreate(
                ['aula_materia_id' => $am1->id, 'estudiante_id' => $est->id, 'fecha' => now()->subDay()->toDateString()],
                ['estado' => EstadoAsistencia::PRESENTE, 'registrado_por' => $profesorMate->id]
            );
        }

        Licencia::firstOrCreate(
            ['estudiante_id' => $estudiante2->id, 'fecha_inicio' => now()->addDays(3)->toDateString()],
            [
                'padre_id' => $padre->id,
                'fecha_fin' => now()->addDays(5)->toDateString(),
                'motivo' => 'Cita médica de control.',
                'estado' => EstadoLicencia::PENDIENTE,
            ]
        );

        Citacion::firstOrCreate(
            ['unidad_educativa_id' => $unidad->id, 'emisor_id' => $director->id, 'destinatario_id' => $padre->id, 'asunto' => 'Reunión de padres'],
            [
                'tipo' => TipoCitacion::PADRE,
                'mensaje' => 'Se solicita su presencia para la reunión trimestral de padres de familia.',
                'fecha_hora' => now()->addDays(7)->setTime(15, 0),
                'lugar' => 'Aula de la dirección',
                'estado' => EstadoCitacion::ENVIADA,
            ]
        );
    }

    private function crearUsuario(string $ci, Rol $rol, string $nombres, string $apellidos, UnidadEducativa $unidad): User
    {
        return User::updateOrCreate(
            ['ci' => $ci],
            [
                'unidad_educativa_id' => $unidad->id,
                'rol' => $rol,
                'usuario' => $ci,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'email' => strtolower($ci).'@demo.test',
                'telefono' => '7'.substr($ci, -7),
                'password' => Hash::make($ci),
                'debe_cambiar_password' => true,
                'estado' => EstadoUsuario::ACTIVO,
            ]
        );
    }

    private function crearEstudiante(string $ci, string $nombres, string $apellidos, UnidadEducativa $unidad, string $fechaNacimiento): Estudiante
    {
        $user = $this->crearUsuario($ci, Rol::ESTUDIANTE, $nombres, $apellidos, $unidad);

        return Estudiante::updateOrCreate(
            ['user_id' => $user->id],
            [
                'unidad_educativa_id' => $unidad->id,
                'fecha_nacimiento' => $fechaNacimiento,
                'estado' => EstadoUsuario::ACTIVO,
            ]
        );
    }
}
