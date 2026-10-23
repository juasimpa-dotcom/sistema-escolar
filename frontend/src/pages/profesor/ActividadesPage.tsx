import { useMemo, useState } from 'react';
import { useMisActividades, usePlanilla, useGuardarPlanilla, useModificarNota, useHistorialNota } from '../../api/academico';
import { PageHeader } from '@/components/ui/PageHeader';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { EmptyState } from '@/components/ui/EmptyState';
import { Paginacion } from '@/components/ui/Paginacion';
import { CampoTexto } from '@/components/ui/CampoTexto';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractErrors, extractMessage } from '@/lib/errors';
import { longitud, numeroMax, numeroMin, requerido } from '@/lib/validacion';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { Clock, BookOpen, History, AlertTriangle, Loader2 } from 'lucide-react';
import type { Actividad, FilaPlanilla } from '../../types/academico';

const hoy = () => new Date().toISOString().slice(0, 10);

const estadoActividad = (a: Actividad): { label: string; clase: string } => {
  if (!a.fecha_limite_notas) return { label: 'Sin fecha límite', clase: 'bg-muted text-muted-foreground' };
  const limite = a.fecha_limite_notas;
  if (limite < hoy()) return { label: 'Vencida', clase: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' };
  const dias = Math.ceil((new Date(limite).getTime() - new Date(hoy()).getTime()) / 86400000);
  if (dias <= 3) return { label: `Vence en ${dias} día(s)`, clase: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' };
  return { label: 'En plazo', clase: 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' };
};

export const ProfesorActividadesPage = () => {
  const [page, setPage] = useState(1);
  const { data: paginado, isLoading } = useMisActividades({ page });
  const actividades = paginado?.data ?? [];
  const [seleccionada, setSeleccionada] = useState<Actividad | null>(null);

  const pendientes = useMemo(
    () => actividades.filter((a) => a.fecha_limite_notas && a.fecha_limite_notas >= hoy()),
    [actividades]
  );

  return (
    <div className="space-y-6">
      <PageHeader title="Mis actividades" description="Registra y corrige notas de tus materias." />

      {pendientes.length > 0 && (
        <div className="rounded-lg border-l-4 border-amber-500 bg-amber-50 p-4 dark:bg-amber-500/10">
          <div className="flex items-center gap-2 text-sm font-medium text-amber-800 dark:text-amber-300">
            <AlertTriangle size={16} /> {pendientes.length} actividad(es) por vencer o en plazo
          </div>
          <div className="mt-1 text-xs text-amber-700 dark:text-amber-400">
            {pendientes.slice(0, 3).map((a) => a.titulo).join(' · ')}
            {pendientes.length > 3 && ` · +${pendientes.length - 3} más`}
          </div>
        </div>
      )}

      <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border bg-muted/40 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                <th className="px-4 py-3">Actividad</th>
                <th className="px-4 py-3">Materia / Aula</th>
                <th className="px-4 py-3">Cumplimiento</th>
                <th className="px-4 py-3">Límite notas</th>
                <th className="px-4 py-3">Estado</th>
                <th className="px-4 py-3">Notas</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              {isLoading &&
                Array.from({ length: 4 }).map((_, i) => (
                  <tr key={i} className="border-b border-border last:border-0">
                    {Array.from({ length: 7 }).map((__, j) => (
                      <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full max-w-[120px]" /></td>
                    ))}
                  </tr>
                ))}
              {!isLoading && actividades?.map((a) => {
                const est = estadoActividad(a);
                return (
                  <tr key={a.id} className="border-b border-border last:border-0 hover:bg-muted/40">
                    <td className="px-4 py-3 font-medium">{a.titulo}</td>
                    <td className="px-4 py-3">
                      <div>{a.aula_materia?.materia}</div>
                      <div className="text-xs text-muted-foreground">{a.aula_materia?.aula}</div>
                    </td>
                    <td className="px-4 py-3">{a.fecha_cumplimiento || '-'}</td>
                    <td className="px-4 py-3">{a.fecha_limite_notas || '-'}</td>
                    <td className="px-4 py-3"><span className={cn('rounded px-2 py-0.5 text-xs', est.clase)}>{est.label}</span></td>
                    <td className="px-4 py-3">{a.notas_count ?? 0}</td>
                    <td className="px-4 py-3 text-right">
                      <Button size="sm" onClick={() => setSeleccionada(a)}>
                        <BookOpen /> Planilla
                      </Button>
                    </td>
                  </tr>
                );
              })}
              {!isLoading && actividades.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-2">
                    <EmptyState
                      icon={<BookOpen />}
                      title="No tienes actividades asignadas"
                      description="Cuando se te asigne una actividad aparecerá aquí."
                    />
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {paginado && (
        <Paginacion
          page={paginado.current_page}
          lastPage={paginado.last_page}
          total={paginado.total}
          onChange={setPage}
        />
      )}

      {seleccionada && (
        <PlanillaModal actividad={seleccionada} onClose={() => setSeleccionada(null)} />
      )}
    </div>
  );
};

const PlanillaModal = ({ actividad, onClose }: { actividad: Actividad; onClose: () => void }) => {
  const { data: planilla, isLoading } = usePlanilla(actividad.id);
  const guardar = useGuardarPlanilla(actividad.id);
  const [valores, setValores] = useState<Record<number, string>>({});
  const [notaHistorial, setNotaHistorial] = useState<number | null>(null);

  const getValor = (fila: FilaPlanilla): string =>
    valores[fila.estudiante_id] !== undefined ? valores[fila.estudiante_id] : (fila.nota !== null ? String(fila.nota) : '');

  const setValor = (estudianteId: number, v: string) => {
    setValores((prev) => ({ ...prev, [estudianteId]: v }));
  };

  const handleGuardar = async () => {
    const filas = planilla?.filas ?? [];
    const payload = filas
      .filter((f) => getValor(f) !== '' && valores[f.estudiante_id] !== undefined)
      .map((f) => ({ estudiante_id: f.estudiante_id, nota: Number(getValor(f)) }));

    if (payload.length === 0) {
      toast.error('No hay cambios para guardar.');
      return;
    }

    try {
      await guardar.mutateAsync(payload);
      setValores({});
      toast.success('Notas guardadas');
    } catch (err: any) {
      const errs = err.response?.data?.errors;
      toast.error(errs ? Object.values(errs).flat().join(' ') : 'No se pudo guardar la planilla.');
    }
  };

  return (
    <Modal open onClose={onClose} title={`Planilla — ${actividad.titulo}`}>
      <div className="mb-3 text-xs text-muted-foreground">
        Puntaje máximo: <span className="font-medium text-foreground">{actividad.puntaje_maximo}</span> ·
        Límite de notas: <span className="font-medium text-foreground">{actividad.fecha_limite_notas || 'sin límite'}</span>
      </div>

      <div className="max-h-[55vh] overflow-y-auto rounded-lg border border-border">
        <table className="w-full text-sm">
          <thead className="sticky top-0 border-b border-border bg-card text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            <tr>
              <th className="px-3 py-2">Estudiante</th>
              <th className="px-3 py-2 text-center">Nota</th>
              <th className="px-3 py-2"></th>
            </tr>
          </thead>
          <tbody>
            {isLoading &&
              Array.from({ length: 4 }).map((_, i) => (
                <tr key={i} className="border-b border-border last:border-0">
                  <td className="px-3 py-2"><Skeleton className="h-4 w-40" /></td>
                  <td className="px-3 py-2"><Skeleton className="mx-auto h-8 w-20" /></td>
                  <td className="px-3 py-2"></td>
                </tr>
              ))}
            {!isLoading && planilla?.filas.map((f) => (
              <tr key={f.estudiante_id} className="border-b border-border last:border-0">
                <td className="px-3 py-2">
                  <div>{f.nombre_completo}</div>
                  <div className="font-mono text-xs text-muted-foreground">{f.ci}</div>
                </td>
                <td className="px-3 py-2 text-center">
                  <Input
                    type="number" step="0.01" min={0} max={actividad.puntaje_maximo}
                    value={getValor(f)}
                    disabled={f.fue_modificada}
                    onChange={(e) => setValor(f.estudiante_id, e.target.value)}
                    className="mx-auto w-20 text-center"
                  />
                </td>
                <td className="px-3 py-2 text-right">
                  {f.fue_modificada && <span className="mr-2 text-xs text-amber-600">corregida</span>}
                  {f.nota_id && (
                    <Button variant="ghost" size="icon" onClick={() => setNotaHistorial(f.nota_id)} title="Ver historial">
                      <History />
                    </Button>
                  )}
                </td>
              </tr>
            ))}
            {!isLoading && planilla && planilla.filas.length === 0 && (
              <tr><td colSpan={3} className="px-3 py-6 text-center text-muted-foreground">No hay estudiantes inscritos</td></tr>
            )}
          </tbody>
        </table>
      </div>

      <div className="mt-4 flex justify-end gap-2">
        <Button type="button" variant="outline" onClick={onClose}>Cerrar</Button>
        <Button onClick={handleGuardar} disabled={guardar.isPending}>
          {guardar.isPending && <Loader2 className="animate-spin" />} Guardar cambios
        </Button>
      </div>

      <p className="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
        <Clock size={12} /> Una nota solo puede corregirse una vez. Las notas corregidas se bloquean.
      </p>

      {notaHistorial !== null && (
        <HistorialModal
          notaId={notaHistorial}
          actividadId={actividad.id}
          puntajeMaximo={actividad.puntaje_maximo}
          onClose={() => setNotaHistorial(null)}
        />
      )}
    </Modal>
  );
};

const HistorialModal = ({
  notaId,
  actividadId,
  puntajeMaximo,
  onClose,
}: {
  notaId: number;
  actividadId: number;
  puntajeMaximo: number;
  onClose: () => void;
}) => {
  const { data: historial, isLoading } = useHistorialNota(notaId);
  const modificar = useModificarNota(actividadId);
  const [error, setError] = useState('');

  const form = useFormValidacion(
    { nota: '', observacion: '' },
    {
      nota: [
        requerido('Ingresá la nueva nota'),
        numeroMin(0, 'La nota no puede ser negativa'),
        numeroMax(puntajeMaximo, `La nota no puede superar ${puntajeMaximo}`),
      ],
      observacion: [
        requerido('La observación es obligatoria'),
        longitud(3, 500, 'La observación debe tener entre 3 y 500 caracteres'),
      ],
    }
  );

  const yaCorregida = (historial?.length ?? 0) > 0;

  const handleCorregir = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;
    try {
      await modificar.mutateAsync({
        id: notaId,
        payload: { nota: Number(form.valores.nota), observacion: form.valores.observacion },
      });
      toast.success('Nota corregida');
      onClose();
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo corregir la nota.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  return (
    <Modal open onClose={onClose} title="Historial de la nota">
      <div className="mb-4 space-y-2">
        {isLoading && <div className="text-sm text-muted-foreground">Cargando...</div>}
        {historial?.map((h) => (
          <div key={h.id} className="rounded-lg border border-border bg-muted/40 p-3 text-sm">
            <div className="font-medium">{h.nota_anterior} → {h.nota_nueva}</div>
            <div className="text-xs text-muted-foreground">{h.observacion}</div>
            <div className="mt-1 text-xs text-muted-foreground">{h.usuario} · {h.created_at}</div>
          </div>
        ))}
        {historial && historial.length === 0 && (
          <div className="text-sm text-muted-foreground">Sin correcciones registradas.</div>
        )}
      </div>

      {yaCorregida ? (
        <div className="rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
          Esta nota ya fue corregida una vez; no se permite otra modificación.
        </div>
      ) : (
        <form onSubmit={handleCorregir} className="space-y-3 border-t border-border pt-4" noValidate>
          {error && (
            <div role="alert" className="rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
          )}
          <CampoTexto
            label="Nueva nota"
            type="number"
            step="0.01"
            min={0}
            max={puntajeMaximo}
            value={form.valores.nota}
            onChange={(e) => form.setCampo('nota', e.target.value)}
            onBlur={form.onBlur('nota')}
            error={form.errorDe('nota')}
            hint={`Máximo ${puntajeMaximo}`}
            required
          />
          <CampoTexto
            label="Observación (obligatoria)"
            multilinea
            rows={2}
            value={form.valores.observacion}
            onChange={(e) => form.setCampo('observacion', e.target.value)}
            onBlur={form.onBlur('observacion')}
            error={form.errorDe('observacion')}
            placeholder="Explicá el motivo de la corrección"
            required
          />
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
            <Button type="submit" disabled={modificar.isPending}>
              {modificar.isPending && <Loader2 className="animate-spin" />}
              Corregir nota
            </Button>
          </div>
        </form>
      )}
    </Modal>
  );
};
