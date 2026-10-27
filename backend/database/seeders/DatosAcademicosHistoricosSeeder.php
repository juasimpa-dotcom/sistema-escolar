<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Adds a repeatable 2026 academic dataset for local reporting and acceptance checks.
 * All people and contact details are synthetic; email addresses use the reserved .invalid TLD.
 * Existing records are never deleted or renamed.
 */
class DatosAcademicosHistoricosSeeder extends Seeder
{
    private const FECHA_INICIO = '2026-01-05';
    private const FECHA_FIN = '2026-10-26';

    private int $personSequence = 0;

    public function run(): void
    {
        DB::transaction(function (): void {
            $schools = $this->schools();
            $totals = [
                'unidades' => count($schools), 'docentes' => 0, 'estudiantes' => 0,
                'tutores' => 0, 'aulas' => 0, 'materias' => 0, 'asistencias_estudiantes' => 0,
                'asistencias_docentes' => 0, 'notas' => 0, 'avances_curriculares' => 0,
            ];

            foreach ($schools as $school) {
                $schoolData = $this->seedSchool($school);
                foreach ($totals as $key => $value) {
                    if ($key !== 'unidades') {
                        $totals[$key] += $schoolData[$key] ?? 0;
                    }
                }
            }

            $this->command?->table(['Indicador', 'Registros creados o actualizados'], collect($totals)
                ->map(fn ($value, $key) => [$key, $value])->values()->all());
        }, 3);
    }

    /** @return array<int, array<string, mixed>> */
    private function schools(): array
    {
        $schools = DB::table('unidades_educativas')
            ->whereIn('codigo', ['UE-NH', 'UE-VF'])
            ->get()->map(fn ($row) => (array) $row)->all();

        $newSchools = [
            ['UE-2026-SAN-PEDRO', 'Unidad Educativa San Pedro', 2, 7, 'fiscal', 'Zona San Pedro'],
            ['UE-2026-ANTOFAGASTA', 'Unidad Educativa Antofagasta', 3, 11, 'convenio', 'Zona Alto Lima'],
            ['UE-2026-TUPAC-KATARI', 'Unidad Educativa Túpac Katari', 5, 18, 'fiscal', 'Zona Chasquipampa'],
            ['UE-2026-MARIA-AUX', 'Unidad Educativa María Auxiliadora', 1, 4, 'convenio', 'Zona Tembladerani'],
        ];

        foreach ($newSchools as [$code, $name, $macroId, $districtNumber, $type, $zone]) {
            $district = DB::table('distritos_municipales')->where('numero', $districtNumber)->first();
            if (! $district || (int) $district->macrodistrito_id !== $macroId) {
                throw new \RuntimeException("No se encontró la correspondencia territorial para {$code}.");
            }

            DB::table('unidades_educativas')->updateOrInsert(
                ['codigo' => $code],
                [
                    'nombre' => $name, 'direccion' => 'La Paz, Bolivia', 'zona' => $zone,
                    'macrodistrito_id' => $macroId, 'distrito_municipal_id' => $district->id,
                    'tipo' => $type, 'estado' => 'activa', 'updated_at' => now(), 'created_at' => now(),
                ]
            );
            $schools[] = (array) DB::table('unidades_educativas')->where('codigo', $code)->first();
        }

        return $schools;
    }

    /** @return array<string, int> */
    private function seedSchool(array $school): array
    {
        $unitId = (int) $school['id'];
        $year = 2026;
        $now = now();

        DB::table('gestiones')->updateOrInsert(
            ['unidad_educativa_id' => $unitId, 'anio' => $year],
            ['fecha_inicio' => '2026-01-05', 'fecha_fin' => '2026-12-18', 'es_actual' => true,
                'estado' => 'activa', 'updated_at' => $now, 'created_at' => $now]
        );
        $gestionId = (int) DB::table('gestiones')->where('unidad_educativa_id', $unitId)->where('anio', $year)->value('id');

        $director = DB::table('users')->where('unidad_educativa_id', $unitId)->where('rol', 'director')->whereNull('deleted_at')->first();
        $directorId = $director?->id ?? $this->person($unitId, 'director', 'director');

        $teachers = DB::table('users')->where('unidad_educativa_id', $unitId)->where('rol', 'profesor')->whereNull('deleted_at')->pluck('id')->map(fn ($id) => (int) $id)->all();
        while (count($teachers) < 8) {
            $teachers[] = $this->person($unitId, 'profesor', 'docente');
        }

        $periods = $this->periods($unitId, $gestionId);
        $subjectNames = [
            'inicial' => ['Comunicación y Lenguajes', 'Pensamiento Lógico Matemático', 'Ciencias de la Vida', 'Artes Plásticas'],
            'primaria' => ['Matemáticas', 'Comunicación y Lenguajes', 'Ciencias Naturales', 'Ciencias Sociales', 'Educación Musical', 'Educación Física'],
            'secundaria' => ['Matemáticas', 'Lengua y Literatura', 'Biología', 'Física', 'Química', 'Historia', 'Geografía', 'Inglés'],
        ];
        $classSpecs = [
            ['nivel' => 'inicial', 'grado' => '4 años'], ['nivel' => 'inicial', 'grado' => '5 años'],
            ['nivel' => 'primaria', 'grado' => '1ro'], ['nivel' => 'primaria', 'grado' => '2do'],
            ['nivel' => 'secundaria', 'grado' => '1ro Sec'], ['nivel' => 'secundaria', 'grado' => '2do Sec'],
        ];

        $classrooms = [];
        $subjectIds = [];
        foreach ($subjectNames as $level => $names) {
            foreach ($names as $subjectName) {
                DB::table('materias')->updateOrInsert(
                    ['unidad_educativa_id' => $unitId, 'nombre' => $subjectName],
                    ['estado' => 'activo', 'updated_at' => $now, 'created_at' => $now]
                );
                $subjectIds[$level][$subjectName] = (int) DB::table('materias')->where('unidad_educativa_id', $unitId)->where('nombre', $subjectName)->value('id');
            }
        }

        foreach ($classSpecs as $specIndex => $spec) {
            DB::table('aulas')->updateOrInsert(
                ['gestion_id' => $gestionId, 'grado' => $spec['grado'], 'paralelo' => 'B', 'turno' => $specIndex % 2 ? 'tarde' : 'manana'],
                ['unidad_educativa_id' => $unitId, 'nivel' => $spec['nivel'], 'estado' => 'activo', 'updated_at' => $now, 'created_at' => $now]
            );
            $classroom = DB::table('aulas')->where('gestion_id', $gestionId)->where('grado', $spec['grado'])
                ->where('paralelo', 'B')->where('turno', $specIndex % 2 ? 'tarde' : 'manana')->first();
            $classrooms[] = ['row' => $classroom, 'spec' => $spec];
        }

        $students = [];
        $aulaMateriaRows = [];
        $teacherAssignment = 0;
        foreach ($classrooms as $classIndex => $class) {
            $classroomId = (int) $class['row']->id;
            $spec = $class['spec'];
            foreach ($subjectIds[$spec['nivel']] as $subjectName => $subjectId) {
                $teacherId = $teachers[$teacherAssignment++ % count($teachers)];
                DB::table('aula_materia')->updateOrInsert(
                    ['aula_id' => $classroomId, 'materia_id' => $subjectId],
                    ['profesor_id' => $teacherId, 'updated_at' => $now, 'created_at' => $now]
                );
                $link = DB::table('aula_materia')->where('aula_id', $classroomId)->where('materia_id', $subjectId)->first();
                $aulaMateriaRows[] = (array) $link;
            }

            $students[$classroomId] = [];
            for ($i = 0; $i < 15; $i++) {
                $studentId = $this->student($unitId, $classroomId, $gestionId, $i, $classIndex);
                $students[$classroomId][] = $studentId;
            }
        }

        $this->plazas($unitId, $teachers, $subjectIds);
        $this->schedules($aulaMateriaRows);
        $attendanceCount = $this->studentAttendance($unitId, $directorId, $aulaMateriaRows, $students);
        $teacherAttendanceCount = $this->teacherAttendance($unitId, $directorId, $teachers);
        $gradeCount = $this->grades($unitId, $directorId, $periods, $aulaMateriaRows, $students);
        $advancesCount = $this->curricularProgress($unitId, $periods, $aulaMateriaRows);
        $this->studentEvents($unitId, $directorId, $students);
        $this->studentLicenses($unitId, $directorId, $students);

        return [
            'docentes' => count($teachers), 'estudiantes' => count($students) * 15,
            'tutores' => count($students) * 15, 'aulas' => count($classrooms),
            'materias' => array_sum(array_map('count', $subjectIds)),
            'asistencias_estudiantes' => $attendanceCount, 'asistencias_docentes' => $teacherAttendanceCount,
            'notas' => $gradeCount, 'avances_curriculares' => $advancesCount,
        ];
    }

    private function person(int $unitId, string $role, string $group): int
    {
        $this->personSequence++;
        $firstNames = ['Valeria', 'Mateo', 'Camila', 'Diego', 'Lucía', 'Andrés', 'Fernanda', 'Joaquín', 'Daniela', 'Gabriel', 'Mariana', 'Sebastián', 'Paola', 'Nicolás', 'Elena', 'Santiago', 'Carla', 'Martín', 'Alejandra', 'Bruno', 'Micaela', 'Álvaro', 'Rocío', 'Tomás', 'Ximena', 'Esteban', 'Natalia', 'Pablo', 'Verónica', 'Renato', 'Adriana', 'Felipe', 'Claudia', 'Iván', 'Jimena', 'Rodrigo', 'Marisol', 'Óscar', 'Noelia', 'Hugo'];
        $surnames = ['Quispe', 'Mamani', 'Flores', 'Rojas', 'Choque', 'Vargas', 'Condori', 'Rivera', 'Gutiérrez', 'Cruz', 'Fernández', 'Paredes', 'Calle', 'Mendoza', 'Soria', 'López', 'Aruquipa', 'Poma', 'García', 'Lima', 'Aguilar', 'Ticona', 'Salazar', 'Cárdenas', 'Mollo', 'Copa', 'Alarcón', 'Montaño', 'Huanca', 'Medina'];
        $n = $this->personSequence;
        $names = $firstNames[($n * 7 + (int) ($unitId * 3)) % count($firstNames)];
        $last = $surnames[($n * 11 + $unitId) % count($surnames)].' '.$surnames[($n * 13 + $unitId * 2 + 1) % count($surnames)];
        $sex = in_array($names, ['Valeria', 'Camila', 'Lucía', 'Fernanda', 'Daniela', 'Mariana', 'Paola', 'Elena', 'Carla', 'Alejandra', 'Micaela', 'Rocío', 'Ximena', 'Natalia', 'Verónica', 'Adriana', 'Claudia', 'Jimena', 'Marisol', 'Noelia'], true) ? 'F' : 'M';
        $key = sprintf('%05d', $n);
        $prefix = match ($role) { 'estudiante' => 'EST', 'padre' => 'FAM', 'director' => 'DIR', default => 'DOC' };
        $values = [
            'unidad_educativa_id' => $unitId, 'rol' => $role,
            'ci' => sprintf('%08d', 26000000 + $n), 'usuario' => sprintf('%08d', 26000000 + $n),
            'nombres' => $names, 'apellidos' => $last,
            'email' => "lpz26.{$prefix}.{$key}@example.invalid", 'telefono' => null,
            'sexo' => $sex, 'password' => Hash::make(sprintf('%08d', 26000000 + $n)), 'debe_cambiar_password' => true,
            'estado' => 'activo', 'created_at' => now(), 'updated_at' => now(),
        ];

        DB::table('users')->updateOrInsert(['ci' => $values['ci']], $values);
        return (int) DB::table('users')->where('ci', $values['ci'])->value('id');
    }

    private function student(int $unitId, int $classroomId, int $gestionId, int $index, int $classIndex): int
    {
        $studentUserId = $this->person($unitId, 'estudiante', 'estudiante');
        $user = DB::table('users')->where('id', $studentUserId)->first();
        $birthYear = match (true) {
            $classIndex < 2 => 2021 + ($classIndex === 0 ? 1 : 0),
            $classIndex < 4 => 2018 + ($classIndex - 2),
            default => 2012 + ($classIndex - 4),
        };
        DB::table('estudiantes')->updateOrInsert(
            ['user_id' => $studentUserId],
            ['unidad_educativa_id' => $unitId, 'fecha_nacimiento' => sprintf('%04d-%02d-%02d', $birthYear, 1 + ($index % 12), 1 + (($index * 3) % 27)),
                'estado' => 'activo', 'updated_at' => now(), 'created_at' => now()]
        );
        $studentId = (int) DB::table('estudiantes')->where('user_id', $studentUserId)->value('id');
        DB::table('inscripciones')->updateOrInsert(
            ['estudiante_id' => $studentId, 'gestion_id' => $gestionId],
            ['aula_id' => $classroomId, 'fecha_inscripcion' => '2026-01-15', 'estado' => 'inscrito',
                'resultado_final' => 'en_curso', 'updated_at' => now(), 'created_at' => now()]
        );

        $parentId = $this->person($unitId, 'padre', 'tutor');
        DB::table('padre_estudiante')->updateOrInsert(
            ['padre_id' => $parentId, 'estudiante_id' => $studentId],
            ['parentesco' => $index % 2 ? 'madre' : 'padre', 'updated_at' => now(), 'created_at' => now()]
        );

        return $studentId;
    }

    /** @return array<int, int> */
    private function periods(int $unitId, int $gestionId): array
    {
        $periods = [];
        foreach ([
            ['Primer bimestre', '2026-02-02', '2026-04-10'],
            ['Segundo bimestre', '2026-04-13', '2026-06-19'],
            ['Tercer bimestre', '2026-06-22', '2026-08-28'],
            ['Cuarto bimestre', '2026-08-31', '2026-10-26'],
        ] as [$name, $start, $end]) {
            DB::table('periodos')->updateOrInsert(
                ['gestion_id' => $gestionId, 'nombre' => $name],
                ['unidad_educativa_id' => $unitId, 'tipo' => 'bimestre', 'fecha_inicio' => $start,
                    'fecha_fin' => $end, 'updated_at' => now(), 'created_at' => now()]
            );
            $periods[] = (int) DB::table('periodos')->where('gestion_id', $gestionId)->where('nombre', $name)->value('id');
        }
        return $periods;
    }

    private function plazas(int $unitId, array $teachers, array $subjectIds): void
    {
        $subjects = [];
        foreach ($subjectIds as $level => $list) {
            foreach ($list as $subjectId) $subjects[] = [$level, $subjectId];
        }
        foreach ($subjects as $index => [$level, $subjectId]) {
            DB::table('plazas_docentes')->updateOrInsert(
                ['unidad_educativa_id' => $unitId, 'materia_id' => $subjectId, 'nivel' => $level],
                ['profesor_id' => $teachers[$index % count($teachers)], 'estado' => 'ocupada', 'updated_at' => now(), 'created_at' => now()]
            );
        }
        foreach (['inicial', 'primaria', 'secundaria'] as $level) {
            DB::table('plazas_docentes')->updateOrInsert(
                ['unidad_educativa_id' => $unitId, 'materia_id' => null, 'nivel' => $level],
                ['profesor_id' => null, 'estado' => 'vacante', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function schedules(array $links): void
    {
        $days = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'];
        foreach ($links as $index => $link) {
            $linkId = (int) $link['id'];
            $day = $days[$index % count($days)];
            DB::table('horarios')->updateOrInsert(
                ['aula_materia_id' => $linkId, 'dia' => $day, 'hora_inicio' => sprintf('%02d:00:00', 8 + ($index % 4))],
                ['hora_fin' => sprintf('%02d:00:00', 9 + ($index % 4)), 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function studentAttendance(int $unitId, int $directorId, array $links, array $studentsByClass): int
    {
        $linkByClass = [];
        foreach ($links as $link) $linkByClass[(int) $link['aula_id']] ??= (int) $link['id'];
        $days = $this->schoolDays('2026-02-02', self::FECHA_FIN);
        $rows = [];
        $count = 0;
        foreach ($studentsByClass as $classroomId => $studentIds) {
            $linkId = $linkByClass[$classroomId];
            foreach ($studentIds as $studentId) {
                foreach ($days as $day) {
                    $seed = crc32("{$studentId}:{$day}") % 100;
                    $state = $seed < 87 ? 'presente' : ($seed < 92 ? 'atraso' : ($seed < 97 ? 'falta' : 'licencia'));
                    $rows[] = [
                        'aula_materia_id' => $linkId, 'estudiante_id' => $studentId, 'fecha' => $day,
                        'estado' => $state, 'observacion' => $state === 'atraso' ? 'Ingreso posterior a la hora establecida' : null,
                        'registrado_por' => $directorId, 'created_at' => $day.' 08:10:00', 'updated_at' => $day.' 08:10:00',
                    ];
                    if (count($rows) >= 1000) {
                        DB::table('asistencias')->upsert($rows, ['aula_materia_id', 'estudiante_id', 'fecha'], ['estado', 'observacion', 'registrado_por', 'updated_at']);
                        $count += count($rows); $rows = [];
                    }
                }
            }
        }
        if ($rows) {
            DB::table('asistencias')->upsert($rows, ['aula_materia_id', 'estudiante_id', 'fecha'], ['estado', 'observacion', 'registrado_por', 'updated_at']);
            $count += count($rows);
        }
        return $count;
    }

    private function teacherAttendance(int $unitId, int $directorId, array $teachers): int
    {
        $rows = [];
        foreach ($teachers as $teacherIndex => $teacherId) {
            foreach ($this->schoolDays(self::FECHA_INICIO, self::FECHA_FIN) as $day) {
                $seed = crc32("{$teacherId}:{$day}") % 100;
                $state = $seed < 92 ? 'presente' : ($seed < 96 ? 'atraso' : ($seed < 98 ? 'falta' : 'licencia'));
                $rows[] = [
                    'unidad_educativa_id' => $unitId, 'profesor_id' => $teacherId, 'fecha' => $day,
                    'estado' => $state, 'hora_ingreso' => $state === 'presente' ? '07:30:00' : ($state === 'atraso' ? '07:48:00' : null),
                    'observacion' => $state === 'atraso' ? 'Registro de ingreso tardío' : null,
                    'registrado_por' => $directorId, 'created_at' => $day.' 08:00:00', 'updated_at' => $day.' 08:00:00',
                ];
            }
        }
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('asistencia_docente')->upsert($chunk, ['unidad_educativa_id', 'profesor_id', 'fecha'], ['estado', 'hora_ingreso', 'observacion', 'registrado_por', 'updated_at']);
        }
        return count($rows);
    }

    private function grades(int $unitId, int $directorId, array $periods, array $links, array $studentsByClass): int
    {
        $count = 0;
        foreach ($links as $linkIndex => $link) {
            $classId = (int) $link['aula_id'];
            $studentIds = $studentsByClass[$classId] ?? [];
            foreach ($periods as $periodIndex => $periodId) {
                foreach (['Evaluación de proceso', 'Trabajo práctico'] as $activityIndex => $title) {
                    $activityDate = Carbon::parse(['2026-03-15', '2026-05-20', '2026-07-25', '2026-09-25'][$periodIndex]);
                    $activityName = "{$title} - {$periodIndex}";
                    DB::table('actividades')->updateOrInsert(
                        ['aula_materia_id' => $link['id'], 'titulo' => $activityName],
                        ['descripcion' => "Actividad de evaluación del periodo: {$title}.", 'fecha_cumplimiento' => $activityDate->toDateString(),
                            'fecha_limite_notas' => $activityDate->copy()->addDays(7)->toDateString(), 'puntaje_maximo' => 100,
                            'periodo_id' => $periodId, 'updated_at' => now(), 'created_at' => now()]
                    );
                    $activityId = (int) DB::table('actividades')->where('aula_materia_id', $link['id'])->where('titulo', $activityName)->value('id');
                    $rows = [];
                    foreach ($studentIds as $studentId) {
                        $score = 42 + (crc32("{$studentId}:{$periodIndex}:{$activityIndex}:{$linkIndex}") % 59);
                        $rows[] = [
                            'actividad_id' => $activityId, 'estudiante_id' => $studentId,
                            'nota' => number_format((float) $score, 2, '.', ''),
                            'observacion' => $score < 51 ? 'Requiere acompañamiento pedagógico' : null,
                            'fue_modificada' => false, 'registrado_por' => $link['profesor_id'], 'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                    foreach (array_chunk($rows, 500) as $chunk) {
                        DB::table('notas')->upsert($chunk, ['actividad_id', 'estudiante_id'], ['nota', 'observacion', 'registrado_por', 'updated_at']);
                        $count += count($chunk);
                    }
                }
            }
        }
        return $count;
    }

    private function curricularProgress(int $unitId, array $periods, array $links): int
    {
        $rows = [];
        foreach ($links as $linkIndex => $link) {
            foreach ($periods as $periodIndex => $periodId) {
                $scheduled = 34 + (($linkIndex + $periodIndex) % 9);
                $taught = max(0, $scheduled - (($linkIndex * 3 + $periodIndex) % 6));
                $rows[] = [
                    'unidad_educativa_id' => $unitId, 'aula_materia_id' => $link['id'], 'periodo_id' => $periodId,
                    'planificacion_entregada' => true, 'fecha_entrega' => ['2026-02-05', '2026-04-15', '2026-06-25', '2026-09-03'][$periodIndex],
                    'clases_programadas' => $scheduled, 'clases_dictadas' => $taught,
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
        }
        DB::table('avance_curricular')->upsert($rows, ['aula_materia_id', 'periodo_id'], ['planificacion_entregada', 'fecha_entrega', 'clases_programadas', 'clases_dictadas', 'updated_at']);
        return count($rows);
    }

    private function studentEvents(int $unitId, int $directorId, array $studentsByClass): void
    {
        $count = 0;
        foreach ($studentsByClass as $students) {
            foreach ($students as $index => $studentId) {
                if ($index % 5 !== 0) continue;
                $type = $index % 2 === 0 ? 'academico' : 'disciplinario';
                $date = Carbon::parse('2026-03-02')->addDays(($studentId * 7) % 220)->toDateString();
                $description = $type === 'academico'
                    ? 'Seguimiento de avance y acompañamiento pedagógico.'
                    : 'Registro de convivencia escolar y orientación formativa.';
                DB::table('ficha_estudiante_eventos')->updateOrInsert(
                    ['unidad_educativa_id' => $unitId, 'estudiante_id' => $studentId, 'fecha' => $date, 'tipo' => $type],
                    ['descripcion' => $description, 'registrado_por' => $directorId, 'created_at' => $date.' 10:00:00', 'updated_at' => $date.' 10:00:00']
                );
                $count++;
            }
        }
    }

    private function studentLicenses(int $unitId, int $directorId, array $studentsByClass): void
    {
        $studentIds = collect($studentsByClass)->flatten()->values();
        foreach ($studentIds->filter(fn ($id, $index) => $index % 37 === 0) as $index => $studentId) {
            $parentId = DB::table('padre_estudiante')->where('estudiante_id', $studentId)->value('padre_id');
            $date = Carbon::parse('2026-03-10')->addDays(($studentId * 3) % 190)->toDateString();
            DB::table('licencias')->updateOrInsert(
                ['estudiante_id' => $studentId, 'fecha_inicio' => $date, 'fecha_fin' => $date],
                ['padre_id' => $parentId, 'motivo' => $index % 2 ? 'Atención de salud con respaldo presentado.' : 'Situación familiar comunicada por el tutor.',
                    'estado' => 'aprobada', 'resuelto_por' => $directorId, 'fecha_resolucion' => $date.' 09:00:00',
                    'created_at' => $date.' 08:00:00', 'updated_at' => $date.' 09:00:00']
            );
        }
    }

    /** @return list<string> */
    private function schoolDays(string $start, string $end): array
    {
        return collect(CarbonPeriod::create($start, $end))
            ->filter(fn (Carbon $date) => $date->isWeekday())
            ->map(fn (Carbon $date) => $date->toDateString())->values()->all();
    }
}
