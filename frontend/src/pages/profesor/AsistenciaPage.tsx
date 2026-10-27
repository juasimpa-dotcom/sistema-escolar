import { useMemo, useState } from 'react';
import {
  useMisMateriasAulas,
  usePlanillaAsistencia,
  useRegistrarAsistencia,
} from '../../api/academico';
import { PageHeader } from '@/components/ui/PageHeader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { EmptyState } from '@/components/ui/EmptyState';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { Clock, Loader2, BookOpen } from 'lucide-react';
import type { AulaMateria, EstadoAsistencia, FilaAsistencia } from '../../types/academico';

const hoy = () => new Date().toISOString().slice(0, 10);

const estados: { valor: EstadoAsistencia; label: string; clase: string }[] = [
  { valor: 'presente', label: 'P', clase: 'bg-green-600' },
  { valor: 'falta', label: 'F', clase: 'bg-red-600' },
  { valor: 'atraso', label: 'A', clase: 'bg-amber-500' },
  { valor: 'licencia', label: 'L', clase: 'bg-blue-600' },
];

export const ProfesorAsistenciaPage = () => {
  const { data: asignaciones, isLoading: cargandoMaterias } = useMisMateriasAulas();
  const [seleccionada, setSeleccionada] = useState<AulaMateria | null>(null);
  const [fecha, setFecha] = useState(hoy());

  return (
    <div className="space-y-6">
      <PageHeader title="Toma de asistencia" description="Registra la asistencia diaria de tus materias.">
        <div className="space-y-1.5">
          <Label htmlFor="fecha">Fecha</Label>
          <Input
            id="fecha"
            type="date"
            value={fecha}
            max={hoy()}
            onChange={(e) => setFecha(e.target.value)}
            className="w-[180px]"
          />
        </div>
      </PageHeader>

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {cargandoMaterias &&
          Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="rounded-xl border border-border bg-card p-4 shadow-sm">
              <Skeleton className="mb-2 h-4 w-1/2" />
              <Skeleton className="h-3 w-2/3" />
            </div>
          ))}
        {!cargandoMaterias && asignaciones?.map((am) => (
          <button
            key={am.id}
            onClick={() => setSeleccionada(am)}
            className={cn(
              'rounded-xl border border-border bg-card p-4 text-left shadow-sm transition hover:shadow',
              seleccionada?.id === am.id && 'border-primary ring-1 ring-primary'
            )}
          >
            <div className="font-medium">{am.materia?.nombre}</div>
            <div className="text-xs text-muted-foreground">
              {am.aula?.nombre} · {am.profesor?.nombre_completo}
            </div>
          </button>
        ))}
        {!cargandoMaterias && asignaciones && asignaciones.length === 0 && (
          <div className="sm:col-span-2 lg:col-span-3">
            <EmptyState icon={<BookOpen />} title="No tienes materias asignadas" description="Contacta al director de tu unidad." />
          </div>
        )}
      </div>

      {seleccionada && <TomaAsistencia key={`${seleccionada.id}-${fecha}`} aulaMateria={seleccionada} fecha={fecha} />}
    </div>
  );
};

const TomaAsistencia = ({ aulaMateria, fecha }: { aulaMateria: AulaMateria; fecha: string }) => {
  const { data: planilla, isLoading } = usePlanillaAsistencia(aulaMateria.id, fecha);
  const registrar = useRegistrarAsistencia(aulaMateria.id, fecha);
  const [valores, setValores] = useState<Record<number, EstadoAsistencia>>({});
  const [observaciones, setObservaciones] = useState<Record<number, string>>({});

  const getEstado = (fila: FilaAsistencia): EstadoAsistencia =>
    valores[fila.estudiante_id] ?? (fila.tiene_licencia ? 'licencia' : fila.estado ?? 'presente');

  const setValor = (estudianteId: number, estado: EstadoAsistencia) => {
    setValores((prev) => ({ ...prev, [estudianteId]: estado }));
  };

  const faltantes = useMemo(
    () => (planilla?.filas ?? []).filter((f) => f.estado === null && !f.tiene_licencia).length,
    [planilla]
  );

  const handleGuardar = async () => {
    const filas = planilla?.filas ?? [];

    if (filas.length === 0) {
      toast.error('No hay estudiantes inscritos.');
      return;
    }

    const payload = filas.map((f) => ({
      estudiante_id: f.estudiante_id,
      estado: getEstado(f),
      observacion: observaciones[f.estudiante_id] ?? f.observacion ?? undefined,
    }));

    try {
      await registrar.mutateAsync(payload);
      setValores({});
      setObservaciones({});
      toast.success('Asistencia registrada');
    } catch (err: any) {
      const errs = err.response?.data?.errors;
      toast.error(errs ? Object.values(errs).flat().join(' ') : 'No se pudo registrar la asistencia.');
    }
  };

  return (
    <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
      <div className="flex items-center justify-between border-b border-border px-4 py-3">
        <div>
          <div className="font-medium">{aulaMateria.materia?.nombre} — {aulaMateria.aula?.nombre}</div>
          <div className="text-xs text-muted-foreground">
            {fecha} · {planilla?.filas.length ?? 0} estudiantes
            {faltantes > 0 && <span className="ml-2 text-amber-600 dark:text-amber-400">{faltantes} sin marcar</span>}
          </div>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/40 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              <th className="px-4 py-3">Estudiante</th>
              <th className="px-4 py-3 text-center">Estado</th>
              <th className="px-4 py-3">Observación</th>
            </tr>
          </thead>
          <tbody>
            {isLoading &&
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-border last:border-0">
                  <td className="px-4 py-3"><Skeleton className="h-4 w-40" /></td>
                  <td className="px-4 py-3"><Skeleton className="mx-auto h-8 w-40" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-8 w-full" /></td>
                </tr>
              ))}
            {!isLoading && planilla?.filas.map((f) => {
              const estado = getEstado(f);
              return (
                <tr key={f.estudiante_id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3">
                    <div>{f.nombre_completo}</div>
                    <div className="font-mono text-xs text-muted-foreground">{f.ci}</div>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-center gap-1">
                      {f.tiene_licencia ? (
                        <span className="rounded bg-blue-100 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">Licencia (automática)</span>
                      ) : (
                        estados.map((op) => (
                          <button
                            key={op.valor}
                            onClick={() => setValor(f.estudiante_id, op.valor)}
                            className={cn(
                              'h-8 w-8 rounded text-xs font-semibold text-white',
                              estado === op.valor ? op.clase : 'bg-muted-foreground/40 hover:bg-muted-foreground/60'
                            )}
                            title={op.valor}
                          >
                            {op.label}
                          </button>
                        ))
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <Input
                      type="text"
                      value={observaciones[f.estudiante_id] ?? f.observacion ?? ''}
                      disabled={f.tiene_licencia}
                      onChange={(e) => setObservaciones((prev) => ({ ...prev, [f.estudiante_id]: e.target.value }))}
                      placeholder="Opcional"
                    />
                  </td>
                </tr>
              );
            })}
            {!isLoading && planilla && planilla.filas.length === 0 && (
              <tr><td colSpan={3} className="px-4 py-2"><EmptyState title="No hay estudiantes inscritos" compact className="border-0 bg-transparent" /></td></tr>
            )}
          </tbody>
        </table>
      </div>

      <div className="flex items-center justify-between border-t border-border px-4 py-3">
        <p className="flex items-center gap-1 text-xs text-muted-foreground">
          <Clock size={12} /> P=Presente, F=Falta, A=Atraso, L=Licencia. Las licencias aprobadas se marcan automáticamente.
        </p>
        <Button
          onClick={handleGuardar}
          disabled={registrar.isPending || !planilla || planilla.filas.length === 0}
        >
          {registrar.isPending && <Loader2 className="animate-spin" />} Guardar asistencia
        </Button>
      </div>
    </div>
  );
};
