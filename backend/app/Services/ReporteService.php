<?php

namespace App\Services;

use App\Support\Reporte;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReporteService
{
    /** Datos agregados compartidos por preview y exportación. */
    public function consulta(string $slug, array $filtros): Builder
    {
        $inscritos = DB::table('inscripciones as i')->join('aulas as a', 'a.id', '=', 'i.aula_id')
            ->join('gestiones as g', 'g.id', '=', 'i.gestion_id')->join('estudiantes as e', 'e.id', '=', 'i.estudiante_id')
            ->join('users as u', 'u.id', '=', 'e.user_id')->join('unidades_educativas as ue', 'ue.id', '=', 'a.unidad_educativa_id')
            ->whereNull('i.deleted_at')->whereNull('a.deleted_at')->whereNull('e.deleted_at');
        $this->filtrosUnidad($inscritos, $filtros);
        if (!empty($filtros['gestion'])) $inscritos->where('g.anio', function ($q) use ($filtros) { $q->select('anio')->from('gestiones')->where('id', $filtros['gestion'])->limit(1); });
        if (!empty($filtros['nivel'])) $inscritos->where('a.nivel', $filtros['nivel']);
        if (!empty($filtros['sexo'])) $inscritos->where('u.sexo', $filtros['sexo']);
        if (!empty($filtros['aula_id'])) $inscritos->where('a.id', $filtros['aula_id']);

        return match ($slug) {
            'matricula-consolidado' => (clone $inscritos)->selectRaw("ue.id as unidad_id, ue.nombre as unidad, a.nivel, u.sexo, {$this->edadSql()} as edad, COUNT(*) as estudiantes")
                ->groupBy('ue.id','ue.nombre','a.nivel','u.sexo','edad')->orderBy('ue.nombre')->orderBy('a.nivel'),
            'distribucion-sexo', 'distribucion-sexo-unidad' => (clone $inscritos)->selectRaw("ue.nombre as unidad, a.grado, a.paralelo, u.sexo, COUNT(*) as inscritos, {$this->sumIf('i.resultado_final', 'reprobado')} as reprobados")
                ->groupBy('ue.nombre','a.grado','a.paralelo','u.sexo')->orderBy('ue.nombre')->orderBy('a.grado'),
            'desercion-reprobacion' => (clone $inscritos)->selectRaw("ue.nombre as unidad, a.nivel, g.anio as gestion, COUNT(*) as inscritos, {$this->sumIf('i.resultado_final', 'abandono')} as abandonos, {$this->sumIf('i.resultado_final', 'reprobado')} as reprobados, ROUND(100*{$this->sumIf('i.resultado_final', 'abandono')}/NULLIF(COUNT(*),0),2) as tasa_abandono, ROUND(100*{$this->sumIf('i.resultado_final', 'reprobado')}/NULLIF(COUNT(*),0),2) as tasa_reprobacion")
                ->groupBy('ue.nombre','a.nivel','g.anio')->orderByRaw('tasa_abandono DESC, tasa_reprobacion DESC'),
            'estadisticas-finales' => (clone $inscritos)->selectRaw("a.grado, a.paralelo, u.sexo, COUNT(*) as inscritos, {$this->sumIf('i.resultado_final', 'promovido')} as promovidos, {$this->sumIf('i.resultado_final', 'reprobado')} as reprobados, {$this->sumIf('i.resultado_final', 'abandono')} as abandonos")
                ->groupBy('a.grado','a.paralelo','u.sexo')->orderBy('a.grado'),
            'rendimiento-municipal' => $this->rendimiento($filtros),
            'disponibilidad-docente' => $this->filtrosUnidad(DB::table('plazas_docentes as p')->join('unidades_educativas as ue','ue.id','=','p.unidad_educativa_id')->leftJoin('materias as m','m.id','=','p.materia_id')->whereNull('p.deleted_at')->when(!empty($filtros['nivel']),fn($q)=>$q->where('p.nivel',$filtros['nivel']))->selectRaw("ue.nombre as unidad, COALESCE(m.nombre,'Sin materia') as materia, COUNT(*) as plazas, {$this->sumIf('p.estado', 'ocupada')} as ocupadas, {$this->sumIf('p.estado', 'vacante')} as vacantes")->groupBy('ue.nombre','m.nombre')->orderBy('ue.nombre'),$filtros),
            'ausentismo-docente', 'asistencia-docente' => $this->filtrosUnidad(DB::table('asistencia_docente as ad')->join('unidades_educativas as ue','ue.id','=','ad.unidad_educativa_id')->join('users as u','u.id','=','ad.profesor_id')->whereBetween('ad.fecha',[$filtros['desde'] ?? '1900-01-01',$filtros['hasta'] ?? '2999-12-31'])->selectRaw('ue.nombre as unidad, u.nombres, u.apellidos, ad.fecha, ad.estado, COUNT(*) as registros')->groupBy('ue.nombre','u.nombres','u.apellidos','ad.fecha','ad.estado')->orderBy('ad.fecha'),$filtros),
            'evolucion-asistencia', 'evolucion-asistencia-unidad' => $this->filtrosUnidad(DB::table('asistencias as x')->join('aula_materia as am','am.id','=','x.aula_materia_id')->join('aulas as a','a.id','=','am.aula_id')->join('unidades_educativas as ue','ue.id','=','a.unidad_educativa_id')->whereBetween('x.fecha',[$filtros['desde'] ?? '1900-01-01',$filtros['hasta'] ?? '2999-12-31'])->selectRaw($this->periodoSql($filtros).' as periodo, '.$this->sumIf('x.estado','atraso').' as atrasos, '.$this->sumIf('x.estado','falta').' as faltas, '.$this->sumIf('x.estado','licencia').' as licencias')->groupBy('periodo')->orderBy('periodo'),$filtros),
            'proyeccion-demanda' => (clone $inscritos)->selectRaw('g.anio as gestion, COUNT(*) as matricula')->groupBy('g.anio')->orderBy('g.anio'),
            'asistencia-puntualidad' => DB::table('asistencias as x')->join('estudiantes as e','e.id','=','x.estudiante_id')->join('users as u','u.id','=','e.user_id')->join('aula_materia as am','am.id','=','x.aula_materia_id')->join('aulas as a','a.id','=','am.aula_id')->where('a.unidad_educativa_id',auth()->user()->unidad_educativa_id)->when(!empty($filtros['aula_id']),fn($q)=>$q->where('a.id',$filtros['aula_id']))->whereBetween('x.fecha',[$filtros['desde'] ?? '1900-01-01',$filtros['hasta'] ?? '2999-12-31'])->whereIn('x.estado',['falta','atraso'])->selectRaw('a.grado,a.paralelo,u.nombres,u.apellidos,x.estado,COUNT(*) as incidencias')->groupBy('a.grado','a.paralelo','u.nombres','u.apellidos','x.estado')->orderByDesc('incidencias'),
            'ficha-estudiante' => $this->ficha($filtros),
            'boletas-calificaciones' => DB::table('notas as n')->join('actividades as ac','ac.id','=','n.actividad_id')->join('aula_materia as am','am.id','=','ac.aula_materia_id')->join('aulas as a','a.id','=','am.aula_id')->join('materias as m','m.id','=','am.materia_id')->join('estudiantes as e','e.id','=','n.estudiante_id')->join('users as u','u.id','=','e.user_id')->where('a.unidad_educativa_id',auth()->user()->unidad_educativa_id)->when(!empty($filtros['estudiante_id']),fn($q)=>$q->where('e.id',$filtros['estudiante_id']))->when(!empty($filtros['periodo_id']),fn($q)=>$q->where('ac.periodo_id',$filtros['periodo_id']))->when(!empty($filtros['aula_id']),fn($q)=>$q->where('a.id',$filtros['aula_id']))->selectRaw('u.nombres,u.apellidos,a.grado,a.paralelo,m.nombre as materia,AVG(n.nota) as promedio')->groupBy('u.nombres','u.apellidos','a.grado','a.paralelo','m.nombre')->orderBy('u.apellidos'),
            'alumnos-riesgo' => (clone $inscritos)->selectRaw("u.nombres,u.apellidos,a.grado,a.paralelo,i.promedio_final,i.resultado_final")->where(fn($q)=>$q->where('i.promedio_final','<',($filtros['umbral'] ?? 51))->orWhere('i.resultado_final','reprobado'))->orderBy('i.promedio_final'),
            'avance-curricular' => DB::table('avance_curricular as ac')->join('aula_materia as am','am.id','=','ac.aula_materia_id')->join('users as u','u.id','=','am.profesor_id')->join('materias as m','m.id','=','am.materia_id')->join('aulas as a','a.id','=','am.aula_id')->join('unidades_educativas as ue','ue.id','=','a.unidad_educativa_id')->where('a.unidad_educativa_id',auth()->user()->unidad_educativa_id)->when(!empty($filtros['periodo_id']),fn($q)=>$q->where('ac.periodo_id',$filtros['periodo_id']))->when(!empty($filtros['aula_id']),fn($q)=>$q->where('a.id',$filtros['aula_id']))->selectRaw('u.nombres,u.apellidos,m.nombre as materia,a.grado,a.paralelo,ac.planificacion_entregada,ac.clases_programadas,ac.clases_dictadas,ROUND(100*ac.clases_dictadas/NULLIF(ac.clases_programadas,0),2) as porcentaje')->orderBy('u.apellidos'),
            default => throw ValidationException::withMessages(['reporte' => 'El reporte solicitado no está disponible.']),
        };
    }

    private function filtrosUnidad(Builder $q, array $f): Builder
    {
        $rol = auth()->user();
        if ($rol->rol->value === 'director') $q->where('ue.id',$rol->unidad_educativa_id);
        else {
            foreach (['unidad_id'=>'ue.id','tipo'=>'ue.tipo','macrodistrito_id'=>'ue.macrodistrito_id','distrito_id'=>'ue.distrito_municipal_id'] as $k=>$c) if (!empty($f[$k])) $q->where($c,$f[$k]);
        }
        return $q;
    }

    private function ficha(array $f): Builder
    {
        return DB::table('ficha_estudiante_eventos as fe')->join('estudiantes as e','e.id','=','fe.estudiante_id')->join('users as u','u.id','=','e.user_id')->where('e.id',$f['estudiante_id'] ?? 0)->where('fe.unidad_educativa_id',auth()->user()->unidad_educativa_id)->whereBetween('fe.fecha',[$f['desde']??'1900-01-01',$f['hasta']??'2999-12-31'])->select('fe.fecha','fe.tipo','fe.descripcion','u.nombres','u.apellidos')->orderBy('fe.fecha');
    }

    private function rendimiento(array $f): Builder
    {
        $q = DB::table('inscripciones as i')->join('aulas as a','a.id','=','i.aula_id')->join('unidades_educativas as ue','ue.id','=','a.unidad_educativa_id')->whereNull('i.deleted_at')->whereNotNull('i.promedio_final');
        $this->filtrosUnidad($q,$f);
        if (!empty($f['gestion'])) $q->whereExists(fn($x)=>$x->selectRaw('1')->from('gestiones as g')->whereColumn('g.id','i.gestion_id')->where('g.anio',function($y)use($f){$y->select('anio')->from('gestiones')->where('id',$f['gestion'])->limit(1);}));
        if (!empty($f['nivel'])) $q->where('a.nivel',$f['nivel']);
        if (!empty($f['sexo'])) $q->join('estudiantes as e','e.id','=','i.estudiante_id')->join('users as u','u.id','=','e.user_id')->where('u.sexo',$f['sexo']);
        return $q->selectRaw('ue.nombre as unidad, AVG(i.promedio_final) as promedio, COUNT(*) as estudiantes')->groupBy('ue.nombre')->orderBy('ue.nombre');
    }

    private function periodoSql(array $f): string
    {
        $grupo = $f['agrupar'] ?? 'dia';
        if (DB::getDriverName() === 'pgsql') {
            return "to_char(date_trunc('".($grupo === 'semana' ? 'week' : ($grupo === 'mes' ? 'month' : 'day'))."', x.fecha), 'YYYY-MM-DD')";
        }
        if (DB::getDriverName() === 'sqlite') return "strftime('".($grupo === 'semana' ? '%Y-%W' : ($grupo === 'mes' ? '%Y-%m' : '%Y-%m-%d'))."', x.fecha)";
        $formato = match($grupo) { 'semana' => '%x-%v', 'mes' => '%Y-%m', default => '%Y-%m-%d' };
        return "DATE_FORMAT(x.fecha, '{$formato}')";
    }

    private function edadSql(): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => 'EXTRACT(YEAR FROM age(CURRENT_DATE, e.fecha_nacimiento))',
            'sqlite' => "CAST((julianday('now') - julianday(e.fecha_nacimiento)) / 365.25 AS INTEGER)",
            default => 'TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE())',
        };
    }

    private function sumIf(string $column, string $value): string
    {
        return "SUM(CASE WHEN {$column} = '{$value}' THEN 1 ELSE 0 END)";
    }
}
