import {
  BookOpen,
  CalendarClock,
  ClipboardCheck,
  DoorOpen,
  FileWarning,
  ListChecks,
} from 'lucide-react';
import { useDatosTablero, type TableroProfesor } from '../../api/dashboard';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { ListaVacia, PanelTablero, TableroError, TableroSkeleton } from '@/components/ui/Tablero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const DIAS: Record<string, string> = {
  lunes: 'Lunes',
  martes: 'Martes',
  miercoles: 'Miércoles',
  jueves: 'Jueves',
  viernes: 'Viernes',
  sabado: 'Sábado',
  domingo: 'Domingo',
};

export const ProfesorInicio = () => {
  const { datos, isLoading, isError, refetch, isFetching } = useDatosTablero<TableroProfesor>();

  const encabezado = (
    <PageHeader
      title="Inicio — Docente"
      description={
        datos
          ? `${DIAS[datos.dia ?? ''] ?? 'Hoy'} · ${datos.fecha} · Tu jornada de hoy en un vistazo`
          : 'Tu jornada de hoy en un vistazo'
      }
      actions={
        <Button onClick={() => refetch()} variant="outline" disabled={isFetching}>
          Actualizar
        </Button>
      }
    />
  );

  if (isLoading) {
    return (
      <div className="space-y-6">
        {encabezado}
        <TableroSkeleton tarjetas={4} paneles={4} />
      </div>
    );
  }

  if (isError || !datos) {
    return (
      <div className="space-y-6">
        {encabezado}
        <TableroError onReintentar={() => refetch()} />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {encabezado}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Horario de hoy"
          value={datos.horario_hoy.total}
          icon={<CalendarClock className="h-5 w-5" />}
          accent="primary"
          hint={datos.horario_hoy.total === 1 ? 'bloque' : 'bloques'}
        />
        <StatCard
          title="Asistencias pendientes"
          value={datos.asistencias_pendientes.total}
          icon={<ClipboardCheck className="h-5 w-5" />}
          accent={datos.asistencias_pendientes.total > 0 ? 'danger' : 'success'}
        />
        <StatCard
          title="Actividades próximas"
          value={datos.actividades.total}
          icon={<ListChecks className="h-5 w-5" />}
          accent="info"
          hint={`${datos.actividades.vencidas} vencidas`}
        />
        <StatCard
          title="Notas por cargar"
          value={datos.actividades.notas_faltantes}
          icon={<FileWarning className="h-5 w-5" />}
          accent={datos.actividades.notas_faltantes > 0 ? 'warning' : 'success'}
        />
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Horario de hoy"
          descripcion="Bloques asignados para el día"
          icono={<CalendarClock className="size-4" />}
        >
          {datos.horario_hoy.items.length === 0 ? (
            <ListaVacia mensaje="No tienes bloques de horario hoy." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.horario_hoy.items.map((h) => (
                <li key={h.id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{h.materia}</p>
                    <p className="text-xs text-muted-foreground">Aula {h.aula}</p>
                  </div>
                  <span className="shrink-0 rounded-lg bg-muted px-2 py-1 text-xs font-medium tabular-nums text-foreground">
                    {h.hora_inicio} — {h.hora_fin}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Asistencias pendientes de tomar hoy"
          descripcion="Aulas cuya asistencia aún no fue registrada"
          icono={<ClipboardCheck className="size-4" />}
          verA="/profesor/asistencia"
        >
          {datos.asistencias_pendientes.items.length === 0 ? (
            <ListaVacia mensaje="Ya tomaste la asistencia de todas tus aulas. ¡Listo!" />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.asistencias_pendientes.items.map((p) => (
                <li key={p.aula_materia_id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{p.materia}</p>
                    <p className="text-xs text-muted-foreground">Aula {p.aula}</p>
                  </div>
                  <Badge
                    variant="outline"
                    className="border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/15 dark:text-red-300"
                  >
                    Pendiente
                  </Badge>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>
      </div>

      <PanelTablero
        titulo="Actividades y notas"
        descripcion="Próximas actividades con su fecha límite de carga de notas"
        icono={<ListChecks className="size-4" />}
        verA="/profesor/actividades"
      >
        {datos.actividades.items.length === 0 ? (
          <ListaVacia mensaje="No tienes actividades próximas con fecha límite." />
        ) : (
          <ul className="divide-y divide-border/60">
            {datos.actividades.items.map((a) => (
              <li key={a.id} className="flex flex-wrap items-center justify-between gap-3 py-2.5">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-foreground">{a.titulo}</p>
                  <p className="truncate text-xs text-muted-foreground">
                    {a.materia} · Aula {a.aula}
                    {a.notas_faltantes > 0 ? ` · ${a.notas_faltantes} nota(s) por cargar` : ''}
                  </p>
                </div>
                {a.vencida ? (
                  <span className="shrink-0 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-500/15 dark:text-red-300">
                    Vencida · {a.fecha_limite}
                  </span>
                ) : (
                  <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                    Límite: {a.fecha_limite}
                  </span>
                )}
              </li>
            ))}
          </ul>
        )}
      </PanelTablero>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Mis aulas"
          descripcion={`${datos.mis_aulas.total} aula(s) a tu cargo`}
          icono={<DoorOpen className="size-4" />}
          verA="/aulas"
        >
          {datos.mis_aulas.items.length === 0 ? (
            <ListaVacia mensaje="Aún no tienes aulas asignadas." />
          ) : (
            <ul className="flex flex-wrap gap-2">
              {datos.mis_aulas.items.map((a) => (
                <li
                  key={a.id}
                  className="rounded-lg border border-border/70 bg-muted/40 px-3 py-1.5 text-sm"
                >
                  {a.nombre}
                  {a.nivel ? <span className="text-muted-foreground"> · {a.nivel}</span> : null}
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Mis materias"
          descripcion={`${datos.mis_materias.total} materia(s)`}
          icono={<BookOpen className="size-4" />}
          verA="/materias"
        >
          {datos.mis_materias.items.length === 0 ? (
            <ListaVacia mensaje="Aún no tienes materias asignadas." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.mis_materias.items.map((m) => (
                <li key={m.id} className="flex items-center justify-between gap-3 py-2.5">
                  <span className="truncate text-sm font-medium text-foreground">{m.nombre}</span>
                  <span className="shrink-0 text-xs text-muted-foreground">
                    {m.aulas} {m.aulas === 1 ? 'aula' : 'aulas'}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>
      </div>
    </div>
  );
};
