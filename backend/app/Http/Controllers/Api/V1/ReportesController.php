<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\ReporteExport;
use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use App\Support\Reporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AsistenciaDocente;
use App\Models\AvanceCurricular;
use App\Models\FichaEstudianteEvento;
use App\Models\Inscripcion;

class ReportesController extends Controller
{
    public function catalogo(Request $request)
    {
        Gate::authorize('viewAny', Reporte::class);
        return response()->json(['data' => array_values(array_map(fn ($r) => ['slug'=>$r->slug,'titulo'=>$r->titulo,'descripcion'=>$r->descripcion,'filtros'=>$r->filtros], Reporte::paraRol($request->user()->rol)))]);
    }

    public function preview(Request $request, string $slug, ReporteService $service)
    {
        $reporte = $this->autorizar($request, $slug, 'ver');
        $filtros = $this->validarFiltros($request, $slug);
        $consulta = $service->consulta($slug, $filtros);
        $graficos = (clone $consulta)->get();
        $paginado = $consulta->paginate(min((int)$request->input('per_page', 25), 100));
        return response()->json(['reporte'=>['slug'=>$reporte->slug,'titulo'=>$reporte->titulo], 'filtros'=>$filtros, 'data'=>$paginado->items(), 'meta'=>['current_page'=>$paginado->currentPage(),'last_page'=>$paginado->lastPage(),'per_page'=>$paginado->perPage(),'total'=>$paginado->total()], 'graficos'=>$graficos]);
    }

    public function exportar(Request $request, string $slug, string $formato, ReporteService $service)
    {
        $reporte = $this->autorizar($request, $slug, 'exportar');
        abort_unless(in_array($formato,['pdf','excel'],true),404);
        $filtros = $this->validarFiltros($request, $slug);
        $filas = $service->consulta($slug, $filtros)->get()->map(fn($r)=>(array)$r)->all();
        $nombre = str($slug)->slug().'-'.now()->format('Ymd-His');
        if ($formato === 'excel') return Excel::download(new ReporteExport($filas), $nombre.'.xlsx');
        $unidad = $request->user()->unidadEducativa?->nombre ?? 'Gobierno Autónomo Municipal de La Paz';
        $logoBase64 = base64_encode(file_get_contents(public_path('images/logo.png')));
        return Pdf::loadView('reportes.pdf', ['filas'=>$filas,'titulo'=>$reporte->titulo,'unidad'=>$unidad,'filtros'=>$filtros,'usuario'=>$request->user()->nombreCompleto(),'fecha'=>now()->format('d/m/Y H:i'),'logoBase64'=>$logoBase64])->download($nombre.'.pdf');
    }

    public function registrar(Request $request, string $tipo)
    {
        abort_unless($request->user()->rol->value === 'director', 403);
        $unidadId = $request->user()->unidad_educativa_id;
        $datos = match ($tipo) {
            'asistencia-docente' => $request->validate(['profesor_id'=>'required|exists:users,id','fecha'=>'required|date','estado'=>'required|in:presente,falta,licencia,atraso','hora_ingreso'=>'nullable|date_format:H:i','observacion'=>'nullable|string|max:1000']),
            'avance-curricular' => $request->validate(['aula_materia_id'=>'required|exists:aula_materia,id','periodo_id'=>'required|exists:periodos,id','planificacion_entregada'=>'required|boolean','fecha_entrega'=>'nullable|date','clases_programadas'=>'required|integer|min:0','clases_dictadas'=>'required|integer|min:0|lte:clases_programadas']),
            'ficha-estudiante' => $request->validate(['estudiante_id'=>'required|exists:estudiantes,id','tipo'=>'required|in:academico,disciplinario,salud','fecha'=>'required|date','descripcion'=>'required|string|max:5000']),
            'resultado-inscripcion' => $request->validate(['inscripcion_id'=>'required|exists:inscripciones,id','resultado_final'=>'required|in:promovido,reprobado,abandono','promedio_final'=>'nullable|numeric|min:0|max:100','fecha_resultado'=>'required|date']),
            default => abort(404),
        };
        if ($tipo === 'asistencia-docente') {
            abort_unless(\App\Models\User::whereKey($datos['profesor_id'])->where('unidad_educativa_id',$unidadId)->where('rol','profesor')->exists(),403);
            $registro=AsistenciaDocente::updateOrCreate(['unidad_educativa_id'=>$unidadId,'profesor_id'=>$datos['profesor_id'],'fecha'=>$datos['fecha']],$datos+['registrado_por'=>$request->user()->id]);
        } elseif ($tipo === 'avance-curricular') {
            abort_unless(\App\Models\AulaMateria::whereKey($datos['aula_materia_id'])->whereHas('aula',fn($q)=>$q->where('unidad_educativa_id',$unidadId))->exists(),403);
            $registro=AvanceCurricular::updateOrCreate(['aula_materia_id'=>$datos['aula_materia_id'],'periodo_id'=>$datos['periodo_id']],$datos+['unidad_educativa_id'=>$unidadId]);
        } elseif ($tipo === 'ficha-estudiante') {
            abort_unless(\App\Models\Estudiante::whereKey($datos['estudiante_id'])->where('unidad_educativa_id',$unidadId)->exists(),403);
            $registro=FichaEstudianteEvento::create($datos+['unidad_educativa_id'=>$unidadId,'registrado_por'=>$request->user()->id]);
        } else {
            $registro=Inscripcion::whereKey($datos['inscripcion_id'])->firstOrFail();
            abort_unless($registro->aula()->where('unidad_educativa_id',$unidadId)->exists(),403);
            $registro->update(collect($datos)->except('inscripcion_id')->all());
        }
        return response()->json(['data'=>$registro],201);
    }

    private function autorizar(Request $request, string $slug, string $ability): Reporte
    {
        $reporte = Reporte::buscar($slug);
        abort_unless($reporte,404);
        Gate::authorize($ability, $reporte);
        return $reporte;
    }

    private function validarFiltros(Request $request, string $slug): array
    {
        return $request->validate([
            'gestion'=>'nullable|integer|exists:gestiones,id','nivel'=>'nullable|in:inicial,primaria,secundaria','tipo'=>'nullable|string|max:30','sexo'=>'nullable|in:M,F',
            'unidad_id'=>'nullable|integer','macrodistrito_id'=>'nullable|integer','distrito_id'=>'nullable|integer','aula_id'=>'nullable|integer','estudiante_id'=>'nullable|integer','periodo_id'=>'nullable|integer',
            'desde'=>'nullable|date','hasta'=>'nullable|date|after_or_equal:desde','agrupar'=>'nullable|in:dia,semana,mes','umbral'=>'nullable|numeric|min:0|max:100',
        ]);
    }
}
