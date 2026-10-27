<?php

namespace Database\Seeders;

use App\Enums\DiaSemana;
use App\Enums\EstadoAsistencia;
use App\Enums\EstadoCitacion;
use App\Enums\EstadoGenerico;
use App\Enums\EstadoGestion;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoLicencia;
use App\Enums\EstadoPlaza;
use App\Enums\EstadoUnidad;
use App\Enums\EstadoUsuario;
use App\Enums\NivelEducativo;
use App\Enums\Parentesco;
use App\Enums\ResultadoInscripcion;
use App\Enums\Rol;
use App\Enums\Sexo;
use App\Enums\TipoCitacion;
use App\Enums\TipoFichaEvento;
use App\Enums\TipoPeriodo;
use App\Enums\TipoUnidad;
use App\Enums\Turno;
use App\Models\Actividad;
use App\Models\Asistencia;
use App\Models\AsistenciaDocente;
use App\Models\Aula;
use App\Models\AulaMateria;
use App\Models\AvanceCurricular;
use App\Models\Citacion;
use App\Models\DistritoMunicipal;
use App\Models\Estudiante;
use App\Models\FichaEstudianteEvento;
use App\Models\Gestion;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Licencia;
use App\Models\Macrodistrito;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\Periodo;
use App\Models\PlazaDocente;
use App\Models\UnidadEducativa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos demo distribuidos en varias unidades educativas y macrodistritos
 * para poder probar los reportes de la Fase A.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->sembrarUnidad($this->configUnidadDemo());
        $this->sembrarUnidad($this->configUnidadNuevaHorizonte());
        $this->sembrarUnidad($this->configUnidadValleFertil());
        $this->datosLegadosDemo();
    }

    // ------------------------------------------------------------------
    // Configuración de las unidades
    // ------------------------------------------------------------------

    private function configUnidadDemo(): array
    {
        return [
            'codigo' => 'UE-DEMO',
            'nombre' => 'Unidad Educativa Demo',
            'direccion' => 'Av. Principal 123, La Paz',
            'zona' => 'Zona Periférica',
            'telefono' => '22222222',
            'tipo' => TipoUnidad::FISCAL,
            'macrodistrito' => 'MD1',
            'periodos_tipo' => TipoPeriodo::BIMESTRE,
            'usuarios' => [
                ['ci' => '20000001', 'rol' => Rol::DIRECTOR, 'nombres' => 'María', 'apellidos' => 'Rojas Vargas', 'sexo' => Sexo::FEMENINO],
                ['ci' => '20000002', 'rol' => Rol::ADMINISTRATIVO, 'nombres' => 'Carlos', 'apellidos' => 'Mamani Quispe', 'sexo' => Sexo::MASCULINO],
                ['ci' => '20000003', 'rol' => Rol::PROFESOR, 'nombres' => 'Ana', 'apellidos' => 'Flores Choque', 'sexo' => Sexo::FEMENINO],
                ['ci' => '20000004', 'rol' => Rol::PROFESOR, 'nombres' => 'Luis', 'apellidos' => 'Huanca Mamani', 'sexo' => Sexo::MASCULINO],
                ['ci' => '20000005', 'rol' => Rol::PADRE, 'nombres' => 'Jorge', 'apellidos' => 'Pérez Condori', 'sexo' => Sexo::MASCULINO],
                ['ci' => '20000008', 'rol' => Rol::PROFESOR, 'nombres' => 'Rosa', 'apellidos' => 'Colque Mamani', 'sexo' => Sexo::FEMENINO],
                ['ci' => '20000009', 'rol' => Rol::PROFESOR, 'nombres' => 'Pedro', 'apellidos' => 'Quispe Flores', 'sexo' => Sexo::MASCULINO],
            ],
            'aulas' => [
                'primaria1' => ['grado' => '1ro', 'paralelo' => 'A', 'turno' => Turno::MANANA, 'nivel' => NivelEducativo::PRIMARIA],
                'primaria2' => ['grado' => '5to', 'paralelo' => 'B', 'turno' => Turno::TARDE, 'nivel' => NivelEducativo::PRIMARIA],
                'inicial1' => ['grado' => '5 años', 'paralelo' => 'A', 'turno' => Turno::MANANA, 'nivel' => NivelEducativo::INICIAL],
                'secundaria1' => ['grado' => '1ro Sec', 'paralelo' => 'A', 'turno' => Turno::MANANA, 'nivel' => NivelEducativo::SECUNDARIA],
            ],
            'materias' => ['Matemáticas', 'Lenguaje y Literatura', 'Ciencias Naturales', 'Historia de Bolivia'],
            'asignaciones' => [
                ['aula' => 'primaria1', 'materia' => 'Matemáticas', 'profesor' => '20000003'],
                ['aula' => 'primaria1', 'materia' => 'Lenguaje y Literatura', 'profesor' => '20000004'],
                ['aula' => 'primaria2', 'materia' => 'Matemáticas', 'profesor' => '20000003'],
                ['aula' => 'primaria2', 'materia' => 'Lenguaje y Literatura', 'profesor' => '20000004'],
                ['aula' => 'inicial1', 'materia' => 'Lenguaje y Literatura', 'profesor' => '20000008'],
                ['aula' => 'secundaria1', 'materia' => 'Matemáticas', 'profesor' => '20000003'],
                ['aula' => 'secundaria1', 'materia' => 'Historia de Bolivia', 'profesor' => '20000009'],
            ],
            'estudiantes' => [
                ['ci' => '20000006', 'nombres' => 'Lucía', 'apellidos' => 'Pérez Mamani', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2015-03-12', 'aula' => 'primaria1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 85.5, 'padre' => '20000005'],
                ['ci' => '20000007', 'nombres' => 'Diego', 'apellidos' => 'Pérez Mamani', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2017-08-25', 'aula' => 'primaria1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 92, 'padre' => '20000005'],
                ['ci' => '20000010', 'nombres' => 'Valeria', 'apellidos' => 'Choque Mamani', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2014-06-10', 'aula' => 'primaria2', 'resultado' => ResultadoInscripcion::REPROBADO, 'promedio' => 38],
                ['ci' => '20000011', 'nombres' => 'Mateo', 'apellidos' => 'Condori Flores', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2014-01-22', 'aula' => 'primaria2', 'resultado' => ResultadoInscripcion::ABANDONO, 'promedio' => null],
                ['ci' => '20000012', 'nombres' => 'Camila', 'apellidos' => 'Mamani Quispe', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2012-09-03', 'aula' => 'secundaria1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 76],
                ['ci' => '20000013', 'nombres' => 'Rodrigo', 'apellidos' => 'Flores Condori', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2012-04-18', 'aula' => 'secundaria1', 'resultado' => ResultadoInscripcion::REPROBADO, 'promedio' => 42],
                ['ci' => '20000014', 'nombres' => 'Andrés', 'apellidos' => 'Quispe Mamani', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2021-11-05', 'aula' => 'inicial1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 88],
                ['ci' => '20000015', 'nombres' => 'Sofía', 'apellidos' => 'Mamani Flores', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2021-07-30', 'aula' => 'inicial1', 'resultado' => ResultadoInscripcion::RETIRADO, 'promedio' => null],
            ],
            'plazas' => [
                ['materia' => 'Matemáticas', 'nivel' => NivelEducativo::SECUNDARIA, 'estado' => EstadoPlaza::OCUPADA, 'profesor' => '20000003'],
                ['materia' => 'Lenguaje y Literatura', 'nivel' => NivelEducativo::SECUNDARIA, 'estado' => EstadoPlaza::OCUPADA, 'profesor' => '20000004'],
                ['materia' => 'Historia de Bolivia', 'nivel' => NivelEducativo::SECUNDARIA, 'estado' => EstadoPlaza::VACANTE],
                ['materia' => 'Ciencias Naturales', 'nivel' => NivelEducativo::PRIMARIA, 'estado' => EstadoPlaza::VACANTE],
                ['materia' => 'Lenguaje y Literatura', 'nivel' => NivelEducativo::INICIAL, 'estado' => EstadoPlaza::VACANTE],
            ],
            'profesores_asistencia' => ['20000003', '20000004', '20000008', '20000009'],
            'asistencia_registrado_por' => '20000001',
            'ficha' => [
                ['ci' => '20000012', 'tipo' => TipoFichaEvento::ACADEMICO, 'fecha' => '-8 days', 'descripcion' => 'Representó a la unidad en la olimpiada de matemáticas del distrito.', 'registrado_por' => '20000001'],
                ['ci' => '20000013', 'tipo' => TipoFichaEvento::DISCIPLINARIO, 'fecha' => '-5 days', 'descripcion' => 'Ingresó tarde en tres ocasiones durante la semana.', 'registrado_por' => '20000002'],
                ['ci' => '20000015', 'tipo' => TipoFichaEvento::SALUD, 'fecha' => '-3 days', 'descripcion' => 'Presentó cuadro gripal; se envió a casa con autorización del tutor.', 'registrado_por' => '20000001'],
            ],
        ];
    }

    private function configUnidadNuevaHorizonte(): array
    {
        return [
            'codigo' => 'UE-NH',
            'nombre' => 'Unidad Educativa Nueva Horizonte',
            'direccion' => 'Av. Busch 456, La Paz',
            'zona' => 'Villa Fátima',
            'telefono' => '22771122',
            'tipo' => TipoUnidad::CONVENIO,
            'macrodistrito' => 'MD4',
            'periodos_tipo' => TipoPeriodo::TRIMESTRE,
            'usuarios' => [
                ['ci' => '21000001', 'rol' => Rol::DIRECTOR, 'nombres' => 'Iván', 'apellidos' => 'Flores Rocha', 'sexo' => Sexo::MASCULINO],
                ['ci' => '21000002', 'rol' => Rol::ADMINISTRATIVO, 'nombres' => 'Patricia', 'apellidos' => 'Colque Lima', 'sexo' => Sexo::FEMENINO],
                ['ci' => '21000003', 'rol' => Rol::PROFESOR, 'nombres' => 'Marco', 'apellidos' => 'Salazar Vega', 'sexo' => Sexo::MASCULINO],
                ['ci' => '21000004', 'rol' => Rol::PROFESOR, 'nombres' => 'Julia', 'apellidos' => 'Rocha Mamani', 'sexo' => Sexo::FEMENINO],
            ],
            'aulas' => [
                'inicial1' => ['grado' => '4 años', 'paralelo' => 'A', 'turno' => Turno::MANANA, 'nivel' => NivelEducativo::INICIAL],
                'primaria1' => ['grado' => '2do', 'paralelo' => 'A', 'turno' => Turno::MANANA, 'nivel' => NivelEducativo::PRIMARIA],
                'secundaria1' => ['grado' => '3ro Sec', 'paralelo' => 'A', 'turno' => Turno::MANANA, 'nivel' => NivelEducativo::SECUNDARIA],
            ],
            'materias' => ['Matemáticas', 'Lenguaje y Literatura', 'Ciencias Naturales'],
            'asignaciones' => [
                ['aula' => 'primaria1', 'materia' => 'Matemáticas', 'profesor' => '21000003'],
                ['aula' => 'primaria1', 'materia' => 'Lenguaje y Literatura', 'profesor' => '21000004'],
                ['aula' => 'secundaria1', 'materia' => 'Matemáticas', 'profesor' => '21000003'],
                ['aula' => 'secundaria1', 'materia' => 'Ciencias Naturales', 'profesor' => '21000004'],
                ['aula' => 'inicial1', 'materia' => 'Lenguaje y Literatura', 'profesor' => '21000004'],
            ],
            'estudiantes' => [
                ['ci' => '21000007', 'nombres' => 'Gabriel', 'apellidos' => 'Rojas Mamani', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2013-05-14', 'aula' => 'primaria1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 78.5],
                ['ci' => '21000008', 'nombres' => 'Daniela', 'apellidos' => 'Vargas Colque', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2013-02-20', 'aula' => 'primaria1', 'resultado' => ResultadoInscripcion::REPROBADO, 'promedio' => 41],
                ['ci' => '21000009', 'nombres' => 'Fernanda', 'apellidos' => 'Quispe Lima', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2011-08-08', 'aula' => 'secundaria1', 'resultado' => ResultadoInscripcion::PROMOVIDO, 'promedio' => 91],
                ['ci' => '21000010', 'nombres' => 'Sebastián', 'apellidos' => 'Mamani Rojas', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2011-12-01', 'aula' => 'secundaria1', 'resultado' => ResultadoInscripcion::ABANDONO, 'promedio' => null],
                ['ci' => '21000011', 'nombres' => 'Martín', 'apellidos' => 'Colque Flores', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2022-03-17', 'aula' => 'inicial1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 84],
                ['ci' => '21000012', 'nombres' => 'Valentina', 'apellidos' => 'Flores Choque', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2022-06-25', 'aula' => 'inicial1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 80],
            ],
            'plazas' => [
                ['materia' => 'Ciencias Naturales', 'nivel' => NivelEducativo::SECUNDARIA, 'estado' => EstadoPlaza::OCUPADA, 'profesor' => '21000004'],
                ['materia' => 'Matemáticas', 'nivel' => NivelEducativo::SECUNDARIA, 'estado' => EstadoPlaza::VACANTE],
                ['materia' => 'Lenguaje y Literatura', 'nivel' => NivelEducativo::PRIMARIA, 'estado' => EstadoPlaza::VACANTE],
                ['materia' => 'Ciencias Naturales', 'nivel' => NivelEducativo::INICIAL, 'estado' => EstadoPlaza::VACANTE],
            ],
            'profesores_asistencia' => ['21000003', '21000004'],
            'asistencia_registrado_por' => '21000002',
            'ficha' => [
                ['ci' => '21000008', 'tipo' => TipoFichaEvento::ACADEMICO, 'fecha' => '-6 days', 'descripcion' => 'Bajó rendimiento en matemáticas; requiere acompañamiento del tutor.', 'registrado_por' => '21000002'],
                ['ci' => '21000010', 'tipo' => TipoFichaEvento::DISCIPLINARIO, 'fecha' => '-4 days', 'descripcion' => 'Dejó de asistir sin justificación durante una semana.', 'registrado_por' => '21000001'],
            ],
        ];
    }

    private function configUnidadValleFertil(): array
    {
        return [
            'codigo' => 'UE-VF',
            'nombre' => 'Unidad Educativa Valle Fértil',
            'direccion' => 'Camino al Zongo Km 8',
            'zona' => 'Zongo Llito',
            'telefono' => '22334455',
            'tipo' => TipoUnidad::PRIVADA,
            'macrodistrito' => 'MD8',
            'periodos_tipo' => TipoPeriodo::SEMESTRE,
            'usuarios' => [
                ['ci' => '22000001', 'rol' => Rol::DIRECTOR, 'nombres' => 'Elena', 'apellidos' => 'Condori Mamani', 'sexo' => Sexo::FEMENINO],
                ['ci' => '22000002', 'rol' => Rol::PROFESOR, 'nombres' => 'Hugo', 'apellidos' => 'Mamani Flores', 'sexo' => Sexo::MASCULINO],
                ['ci' => '22000003', 'rol' => Rol::PROFESOR, 'nombres' => 'Natalia', 'apellidos' => 'Flores Colque', 'sexo' => Sexo::FEMENINO],
            ],
            'aulas' => [
                'primaria1' => ['grado' => '1ro', 'paralelo' => 'A', 'turno' => Turno::MANANA, 'nivel' => NivelEducativo::PRIMARIA],
                'secundaria1' => ['grado' => '2do Sec', 'paralelo' => 'A', 'turno' => Turno::TARDE, 'nivel' => NivelEducativo::SECUNDARIA],
            ],
            'materias' => ['Matemáticas', 'Lenguaje y Literatura'],
            'asignaciones' => [
                ['aula' => 'primaria1', 'materia' => 'Matemáticas', 'profesor' => '22000002'],
                ['aula' => 'primaria1', 'materia' => 'Lenguaje y Literatura', 'profesor' => '22000003'],
                ['aula' => 'secundaria1', 'materia' => 'Matemáticas', 'profesor' => '22000002'],
                ['aula' => 'secundaria1', 'materia' => 'Lenguaje y Literatura', 'profesor' => '22000003'],
            ],
            'estudiantes' => [
                ['ci' => '22000004', 'nombres' => 'Ariana', 'apellidos' => 'Flores Mamani', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2015-01-09', 'aula' => 'primaria1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 82],
                ['ci' => '22000005', 'nombres' => 'Bruno', 'apellidos' => 'Quispe Condori', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2015-10-30', 'aula' => 'primaria1', 'resultado' => ResultadoInscripcion::REPROBADO, 'promedio' => 45],
                ['ci' => '22000006', 'nombres' => 'Karla', 'apellidos' => 'Mamani Rocha', 'sexo' => Sexo::FEMENINO, 'nacimiento' => '2013-03-12', 'aula' => 'secundaria1', 'resultado' => ResultadoInscripcion::EN_CURSO, 'promedio' => 74],
                ['ci' => '22000007', 'nombres' => 'Diego', 'apellidos' => 'Ramos Flores', 'sexo' => Sexo::MASCULINO, 'nacimiento' => '2013-07-19', 'aula' => 'secundaria1', 'resultado' => ResultadoInscripcion::RETIRADO, 'promedio' => 60],
            ],
            'plazas' => [
                ['materia' => 'Matemáticas', 'nivel' => NivelEducativo::SECUNDARIA, 'estado' => EstadoPlaza::OCUPADA, 'profesor' => '22000002'],
                ['materia' => 'Lenguaje y Literatura', 'nivel' => NivelEducativo::SECUNDARIA, 'estado' => EstadoPlaza::VACANTE],
                ['materia' => 'Matemáticas', 'nivel' => NivelEducativo::PRIMARIA, 'estado' => EstadoPlaza::VACANTE],
            ],
            'profesores_asistencia' => ['22000002', '22000003'],
            'asistencia_registrado_por' => '22000001',
            'ficha' => [
                ['ci' => '22000005', 'tipo' => TipoFichaEvento::ACADEMICO, 'fecha' => '-7 days', 'descripcion' => 'Requiere recuperación de matemáticas del primer semestre.', 'registrado_por' => '22000001'],
                ['ci' => '22000004', 'tipo' => TipoFichaEvento::SALUD, 'fecha' => '-2 days', 'descripcion' => 'Control de salud anual sin observaciones.', 'registrado_por' => '22000001'],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Siembra de una unidad
    // ------------------------------------------------------------------

    private function sembrarUnidad(array $cfg): void
    {
        $macro = Macrodistrito::where('codigo', $cfg['macrodistrito'])->first();
        $distrito = $macro
            ? DistritoMunicipal::where('macrodistrito_id', $macro->id)->first()
            : null;

        $unidad = UnidadEducativa::updateOrCreate(
            ['codigo' => $cfg['codigo']],
            [
                'nombre' => $cfg['nombre'],
                'direccion' => $cfg['direccion'],
                'zona' => $cfg['zona'],
                'telefono' => $cfg['telefono'],
                'tipo' => $cfg['tipo'],
                'macrodistrito_id' => $macro?->id,
                'distrito_municipal_id' => $distrito?->id,
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

        $usuarios = [];

        foreach ($cfg['usuarios'] as $u) {
            $usuarios[$u['ci']] = $this->crearUsuario($u['ci'], $u['rol'], $u['nombres'], $u['apellidos'], $unidad, $u['sexo']);
        }

        $periodos = $this->crearPeriodos($unidad, $gestion, $cfg['periodos_tipo']);

        $aulas = [];

        foreach ($cfg['aulas'] as $key => $a) {
            $aulas[$key] = Aula::updateOrCreate(
                ['gestion_id' => $gestion->id, 'grado' => $a['grado'], 'paralelo' => $a['paralelo'], 'turno' => $a['turno']->value],
                ['unidad_educativa_id' => $unidad->id, 'nivel' => $a['nivel'], 'estado' => EstadoGenerico::ACTIVO]
            );
        }

        $materias = [];

        foreach ($cfg['materias'] as $nombre) {
            $materias[$nombre] = Materia::updateOrCreate(
                ['unidad_educativa_id' => $unidad->id, 'nombre' => $nombre],
                ['estado' => EstadoGenerico::ACTIVO]
            );
        }

        // Asignaciones materia-aula-profesor
        $amsPorAula = [];

        foreach ($cfg['asignaciones'] as $asig) {
            $am = AulaMateria::updateOrCreate(
                ['aula_id' => $aulas[$asig['aula']]->id, 'materia_id' => $materias[$asig['materia']]->id],
                ['profesor_id' => $usuarios[$asig['profesor']]->id]
            );

            $amsPorAula[$asig['aula']][] = $am;
        }

        // Estudiantes e inscripciones con resultado final
        $inscritos = [];
        $promedioPorEstudiante = [];

        foreach ($cfg['estudiantes'] as $e) {
            $estudiante = $this->crearEstudiante($e['ci'], $e['nombres'], $e['apellidos'], $unidad, $e['nacimiento'], $e['sexo']);
            $resultado = $e['resultado'] ?? ResultadoInscripcion::EN_CURSO;

            Inscripcion::updateOrCreate(
                ['estudiante_id' => $estudiante->id, 'gestion_id' => $gestion->id],
                [
                    'aula_id' => $aulas[$e['aula']]->id,
                    'fecha_inscripcion' => now()->subMonths(2)->toDateString(),
                    'estado' => EstadoInscripcion::INSCRITO,
                    'resultado_final' => $resultado,
                    'promedio_final' => $e['promedio'],
                    'fecha_resultado' => $resultado === ResultadoInscripcion::EN_CURSO
                        ? null
                        : now()->subWeek()->toDateString(),
                ]
            );

            if (! empty($e['padre']) && isset($usuarios[$e['padre']])) {
                $padre = $usuarios[$e['padre']];

                if (! $padre->hijos()->whereKey($estudiante->id)->exists()) {
                    $padre->hijos()->attach($estudiante->id, ['parentesco' => Parentesco::PADRE->value]);
                }
            }

            $inscritos[$e['aula']][] = $estudiante;
            $promedioPorEstudiante[$estudiante->id] = $e['promedio'];
        }

        // Actividades ligadas al primer periodo + notas
        foreach ($amsPorAula as $aulaKey => $ams) {
            foreach ($ams as $am) {
                $actividad = Actividad::firstOrCreate(
                    ['aula_materia_id' => $am->id, 'titulo' => 'Examen parcial de '.$am->materia()->first()?->nombre],
                    [
                        'periodo_id' => $periodos[0]->id,
                        'descripcion' => 'Evaluación del periodo académico.',
                        'fecha_cumplimiento' => now()->subDays(10)->toDateString(),
                        'fecha_limite_notas' => now()->addDays(7)->toDateString(),
                        'puntaje_maximo' => 100,
                    ]
                );

                foreach ($inscritos[$aulaKey] ?? [] as $estudiante) {
                    Nota::firstOrCreate(
                        ['actividad_id' => $actividad->id, 'estudiante_id' => $estudiante->id],
                        [
                            'nota' => $promedioPorEstudiante[$estudiante->id] ?? fake()->numberBetween(45, 95),
                            'fue_modificada' => false,
                            'registrado_por' => $am->profesor_id,
                        ]
                    );
                }
            }
        }

        // Asistencia de estudiantes en los últimos días hábiles
        $fechas = $this->diasHabiles(6);

        foreach ($amsPorAula as $aulaKey => $ams) {
            foreach ($ams as $am) {
                foreach ($fechas as $fecha) {
                    foreach ($inscritos[$aulaKey] ?? [] as $estudiante) {
                        Asistencia::firstOrCreate(
                            ['aula_materia_id' => $am->id, 'estudiante_id' => $estudiante->id, 'fecha' => $fecha],
                            ['estado' => $this->estadoAsistenciaDemo(), 'registrado_por' => $am->profesor_id]
                        );
                    }
                }
            }
        }

        // Plazas docentes (ocupadas y vacantes)
        foreach ($cfg['plazas'] as $p) {
            $profesorId = null;

            if (! empty($p['profesor']) && isset($usuarios[$p['profesor']])) {
                $profesorId = $usuarios[$p['profesor']]->id;
            }

            PlazaDocente::updateOrCreate(
                [
                    'unidad_educativa_id' => $unidad->id,
                    'materia_id' => $materias[$p['materia']]->id,
                    'nivel' => $p['nivel']->value,
                ],
                ['estado' => $p['estado'], 'profesor_id' => $profesorId]
            );
        }

        // Asistencia docente manual (varios días)
        $this->sembrarAsistenciaDocente($cfg, $usuarios, $unidad);

        // Ficha del estudiante (académico, disciplinario, salud)
        foreach ($cfg['ficha'] as $f) {
            $estudiante = Estudiante::whereHas('user', fn ($q) => $q->where('ci', $f['ci']))->first();

            if (! $estudiante) {
                continue;
            }

            FichaEstudianteEvento::firstOrCreate(
                [
                    'estudiante_id' => $estudiante->id,
                    'tipo' => $f['tipo']->value,
                    'fecha' => now()->modify($f['fecha'])->toDateString(),
                ],
                [
                    'unidad_educativa_id' => $unidad->id,
                    'descripcion' => $f['descripcion'],
                    'registrado_por' => $usuarios[$f['registrado_por']]->id,
                ]
            );
        }

        // Avance curricular por materia-aula y periodo
        foreach ($amsPorAula as $ams) {
            foreach ($ams as $am) {
                foreach (array_slice($periodos, 0, 2) as $indice => $periodo) {
                    AvanceCurricular::updateOrCreate(
                        ['aula_materia_id' => $am->id, 'periodo_id' => $periodo->id],
                        [
                            'unidad_educativa_id' => $unidad->id,
                            'planificacion_entregada' => $indice === 0,
                            'fecha_entrega' => $indice === 0
                                ? $periodo->fecha_inicio->copy()->addDays(5)->toDateString()
                                : null,
                            'clases_programadas' => 32,
                            'clases_dictadas' => $indice === 0 ? 32 : 18,
                        ]
                    );
                }
            }
        }
    }

    private function sembrarAsistenciaDocente(array $cfg, array $usuarios, UnidadEducativa $unidad): void
    {
        $registrador = $usuarios[$cfg['asistencia_registrado_por']] ?? null;

        if (! $registrador) {
            return;
        }

        $fechas = $this->diasHabiles(5);
        $estados = [
            EstadoAsistencia::PRESENTE,
            EstadoAsistencia::PRESENTE,
            EstadoAsistencia::ATRASO,
            EstadoAsistencia::LICENCIA,
            EstadoAsistencia::FALTA,
        ];

        foreach (array_values($cfg['profesores_asistencia']) as $indiceProfesor => $ci) {
            $profesor = $usuarios[$ci] ?? null;

            if (! $profesor) {
                continue;
            }

            foreach ($fechas as $indice => $fecha) {
                $estado = $estados[($indice + $indiceProfesor) % count($estados)];

                AsistenciaDocente::firstOrCreate(
                    [
                        'unidad_educativa_id' => $unidad->id,
                        'profesor_id' => $profesor->id,
                        'fecha' => $fecha,
                    ],
                    [
                        'estado' => $estado,
                        'hora_ingreso' => in_array($estado, [EstadoAsistencia::FALTA, EstadoAsistencia::LICENCIA], true)
                            ? null
                            : ($estado === EstadoAsistencia::ATRASO ? '08:15' : '07:55'),
                        'observacion' => match ($estado) {
                            EstadoAsistencia::ATRASO => 'Llegó 15 minutos tarde.',
                            EstadoAsistencia::FALTA => 'Sin registro de ingreso.',
                            EstadoAsistencia::LICENCIA => 'Licencia médica aprobada.',
                            default => null,
                        },
                        'registrado_por' => $registrador->id,
                    ]
                );
            }
        }
    }

    // ------------------------------------------------------------------
    // Datos heredados del demo original (horarios, licencia, citación)
    // ------------------------------------------------------------------

    private function datosLegadosDemo(): void
    {
        $unidad = UnidadEducativa::where('codigo', 'UE-DEMO')->first();

        if (! $unidad) {
            return;
        }

        $gestion = Gestion::where('unidad_educativa_id', $unidad->id)
            ->where('anio', now()->year)
            ->first();

        if (! $gestion) {
            return;
        }

        $aula1 = Aula::where('gestion_id', $gestion->id)->where('grado', '1ro')->where('paralelo', 'A')->first();
        $aula2 = Aula::where('gestion_id', $gestion->id)->where('grado', '5to')->where('paralelo', 'B')->first();
        $mates = Materia::where('unidad_educativa_id', $unidad->id)->where('nombre', 'Matemáticas')->first();
        $lenguaje = Materia::where('unidad_educativa_id', $unidad->id)->where('nombre', 'Lenguaje y Literatura')->first();

        if (! $aula1 || ! $aula2 || ! $mates || ! $lenguaje) {
            return;
        }

        $am1 = AulaMateria::where('aula_id', $aula1->id)->where('materia_id', $mates->id)->first();
        $am2 = AulaMateria::where('aula_id', $aula1->id)->where('materia_id', $lenguaje->id)->first();
        $am3 = AulaMateria::where('aula_id', $aula2->id)->where('materia_id', $mates->id)->first();

        foreach ([
            [$am1, DiaSemana::LUNES, '08:00', '08:45'],
            [$am1, DiaSemana::MIERCOLES, '08:00', '08:45'],
            [$am2, DiaSemana::MARTES, '08:45', '09:30'],
            [$am3, DiaSemana::JUEVES, '14:00', '14:45'],
        ] as [$am, $dia, $ini, $fin]) {
            if (! $am) {
                continue;
            }

            Horario::updateOrCreate(
                ['aula_materia_id' => $am->id, 'dia' => $dia->value, 'hora_inicio' => $ini],
                ['hora_fin' => $fin]
            );
        }

        $padre = User::where('ci', '20000005')->first();
        $director = User::where('ci', '20000001')->first();
        $diego = Estudiante::whereHas('user', fn ($q) => $q->where('ci', '20000007'))->first();

        if ($padre && $diego) {
            Licencia::firstOrCreate(
                ['estudiante_id' => $diego->id, 'fecha_inicio' => now()->addDays(3)->toDateString()],
                [
                    'padre_id' => $padre->id,
                    'fecha_fin' => now()->addDays(5)->toDateString(),
                    'motivo' => 'Cita médica de control.',
                    'estado' => EstadoLicencia::PENDIENTE,
                ]
            );
        }

        if ($director && $padre) {
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
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function crearUsuario(string $ci, Rol $rol, string $nombres, string $apellidos, UnidadEducativa $unidad, ?Sexo $sexo = null): User
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
                'sexo' => $sexo,
                'password' => Hash::make($ci),
                'debe_cambiar_password' => true,
                'estado' => EstadoUsuario::ACTIVO,
            ]
        );
    }

    private function crearEstudiante(string $ci, string $nombres, string $apellidos, UnidadEducativa $unidad, string $fechaNacimiento, ?Sexo $sexo = null): Estudiante
    {
        $user = $this->crearUsuario($ci, Rol::ESTUDIANTE, $nombres, $apellidos, $unidad, $sexo);

        return Estudiante::updateOrCreate(
            ['user_id' => $user->id],
            [
                'unidad_educativa_id' => $unidad->id,
                'fecha_nacimiento' => $fechaNacimiento,
                'estado' => EstadoUsuario::ACTIVO,
            ]
        );
    }

    /**
     * @return array<int, Periodo>
     */
    private function crearPeriodos(UnidadEducativa $unidad, Gestion $gestion, TipoPeriodo $tipo): array
    {
        $anio = (int) $gestion->anio;

        $defs = match ($tipo) {
            TipoPeriodo::TRIMESTRE => [
                ['nombre' => '1er Trimestre', 'inicio' => sprintf('%04d-02-02', $anio), 'fin' => sprintf('%04d-05-02', $anio)],
                ['nombre' => '2do Trimestre', 'inicio' => sprintf('%04d-05-04', $anio), 'fin' => sprintf('%04d-08-07', $anio)],
                ['nombre' => '3er Trimestre', 'inicio' => sprintf('%04d-08-10', $anio), 'fin' => sprintf('%04d-11-13', $anio)],
            ],
            TipoPeriodo::SEMESTRE => [
                ['nombre' => '1er Semestre', 'inicio' => sprintf('%04d-02-02', $anio), 'fin' => sprintf('%04d-07-31', $anio)],
                ['nombre' => '2do Semestre', 'inicio' => sprintf('%04d-08-03', $anio), 'fin' => sprintf('%04d-12-15', $anio)],
            ],
            default => [
                ['nombre' => '1er Bimestre', 'inicio' => sprintf('%04d-02-02', $anio), 'fin' => sprintf('%04d-04-03', $anio)],
                ['nombre' => '2do Bimestre', 'inicio' => sprintf('%04d-04-06', $anio), 'fin' => sprintf('%04d-06-05', $anio)],
                ['nombre' => '3er Bimestre', 'inicio' => sprintf('%04d-06-08', $anio), 'fin' => sprintf('%04d-07-31', $anio)],
            ],
        };

        $periodos = [];

        foreach ($defs as $def) {
            $periodos[] = Periodo::updateOrCreate(
                ['gestion_id' => $gestion->id, 'nombre' => $def['nombre']],
                [
                    'unidad_educativa_id' => $unidad->id,
                    'tipo' => $tipo,
                    'fecha_inicio' => $def['inicio'],
                    'fecha_fin' => $def['fin'],
                ]
            );
        }

        return $periodos;
    }

    /**
     * Últimas $cantidad fechas excluyendo domingos (asistencia no futura).
     *
     * @return array<int, string>
     */
    private function diasHabiles(int $cantidad): array
    {
        $fechas = [];
        $fecha = now()->startOfDay();

        while (count($fechas) < $cantidad && $fecha->year > 2000) {
            if (! $fecha->isSunday()) {
                $fechas[] = $fecha->toDateString();
            }

            $fecha->subDay();
        }

        return $fechas;
    }

    private function estadoAsistenciaDemo(): EstadoAsistencia
    {
        $valor = fake()->numberBetween(1, 100);

        return match (true) {
            $valor <= 70 => EstadoAsistencia::PRESENTE,
            $valor <= 82 => EstadoAsistencia::FALTA,
            $valor <= 92 => EstadoAsistencia::ATRASO,
            default => EstadoAsistencia::LICENCIA,
        };
    }
}
