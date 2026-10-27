import { CalendarCheck, CalendarClock, GraduationCap, ListChecks } from 'lucide-react';
import { useDatosTablero, type TableroEstudiante } from '../../api/dashboard';
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

export const EstudianteInicio = () => {
  const { datos, isLoading, isError, refetch, isFetching } =
    useDatosTablero<TableroEstudiante>();

  const promedio =
    datos && datos.ultimas_notas.total > 0
      ? (
          datos.ultimas_notas.items.reduce((acc, n) => acc + n.nota, 0) /
          datos.ultimas_notas.total
        ).toFixed(1)
      : '—';

  const encabezado = (
    <PageHeader
      title="Mi inicio"
      description={
        datos
          ? `${DIAS[datos.dia ?? ''] ?? 'Hoy'} · ${datos.fecha}${datos.aula ? ` · Aula ${datos.aula}` : ''}`
          : 'Tu día en un vistazo'
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
        <TableroSkeleton tarjetas={4} paneles={3} />
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
          title="Mi asistencia del mes"
          value={`${datos.asistencia_mes.porcentaje}%`}
          icon={<CalendarCheck className="h-5 w-5" />}
          accent={datos.asistencia_mes.porcentaje >= 80 ? 'success' : 'warning'}
          hint={`${datos.asistencia_mes.total} registros`}
        />
        <StatCard
          title="Horario de hoy"
          value={datos.horario_hoy.total}
          icon={<CalendarClock className="h-5 w-5" />}
          accent="primary"
          hint={datos.horario_hoy.total === 1 ? 'clase' : 'clases'}
        />
        <StatCard
          title="Próximas actividades"
          value={datos.proximas_actividades.total}
          icon={<ListChecks className="h-5 w-5" />}
          accent="info"
        />
        <StatCard
          title="Promedio últimas notas"
          value={promedio}
          icon={<GraduationCap className="h-5 w-5" />}
          accent="success"
        />
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Horario de hoy"
          descripcion="Tus clases del día"
          icono={<CalendarClock className="size-4" />}
        >
          {datos.horario_hoy.items.length === 0 ? (
            <ListaVacia mensaje="No tienes clases programadas hoy." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.horario_hoy.items.map((h) => (
                <li key={h.id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{h.materia}</p>
                    <p className="truncate text-xs text-muted-foreground">Prof. {h.profesor}</p>
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
          titulo="Próximas actividades"
          descripcion="Entregas próximas de tu aula"
          icono={<ListChecks className="size-4" />}
        >
          {datos.proximas_actividades.items.length === 0 ? (
            <ListaVacia mensaje="No tienes actividades próximas. ¡Buen momento para repasar!" />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.proximas_actividades.items.map((a) => (
                <li key={a.id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{a.titulo}</p>
                    <p className="text-xs text-muted-foreground">{a.materia}</p>
                  </div>
                  <div className="flex shrink-0 items-center gap-2">
                    {a.sin_nota && <Badge variant="outline">Sin nota</Badge>}
                    <span className="text-xs tabular-nums text-muted-foreground">{a.fecha}</span>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Últimas notas"
          descripcion={`Promedio de las últimas: ${promedio}`}
          icono={<GraduationCap className="size-4" />}
        >
          {datos.ultimas_notas.items.length === 0 ? (
            <ListaVacia mensaje="Aún no tienes notas registradas." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.ultimas_notas.items.map((n, i) => (
                <li
                  key={`${n.actividad}-${i}`}
                  className="flex items-center justify-between gap-3 py-2.5"
                >
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{n.actividad}</p>
                    <p className="text-xs text-muted-foreground">{n.fecha}</p>
                  </div>
                  <span className="shrink-0 text-sm font-semibold tabular-nums text-foreground">
                    {n.nota}
                    {n.puntaje_maximo ? (
                      <span className="text-muted-foreground">/{n.puntaje_maximo}</span>
                    ) : null}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Mi asistencia del mes"
          descripcion="Asistencia registrada y licencias aprobadas"
          icono={<CalendarCheck className="size-4" />}
        >
          {datos.asistencia_mes.total === 0 && datos.asistencia_mes.licencias_aprobadas === 0 ? (
            <ListaVacia mensaje="Aún no hay registros de asistencia este mes." />
          ) : (
            <div className="space-y-4">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Porcentaje de asistencia</span>
                <span className="text-xl font-semibold tabular-nums text-foreground">
                  {datos.asistencia_mes.porcentaje}%
                </span>
              </div>
              <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                <div
                  className={`h-2.5 rounded-full ${
                    datos.asistencia_mes.porcentaje >= 80 ? 'bg-green-500' : 'bg-amber-500'
                  }`}
                  style={{ width: `${Math.min(100, datos.asistencia_mes.porcentaje)}%` }}
                />
              </div>
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div className="rounded-lg bg-green-50 p-3 text-center dark:bg-green-500/10">
                  <p className="text-lg font-semibold text-green-700 dark:text-green-300">
                    {datos.asistencia_mes.presentes}
                  </p>
                  <p className="text-xs text-muted-foreground">Presentes</p>
                </div>
                <div className="rounded-lg bg-red-50 p-3 text-center dark:bg-red-500/10">
                  <p className="text-lg font-semibold text-red-700 dark:text-red-300">
                    {datos.asistencia_mes.faltas}
                  </p>
                  <p className="text-xs text-muted-foreground">Faltas</p>
                </div>
                <div className="rounded-lg bg-amber-50 p-3 text-center dark:bg-amber-500/10">
                  <p className="text-lg font-semibold text-amber-700 dark:text-amber-300">
                    {datos.asistencia_mes.atrasos}
                  </p>
                  <p className="text-xs text-muted-foreground">Atrasos</p>
                </div>
                <div className="rounded-lg bg-blue-50 p-3 text-center dark:bg-blue-500/10">
                  <p className="text-lg font-semibold text-blue-700 dark:text-blue-300">
                    {datos.asistencia_mes.licencias_aprobadas}
                  </p>
                  <p className="text-xs text-muted-foreground">Licencias aprobadas</p>
                </div>
              </div>
            </div>
          )}
        </PanelTablero>
      </div>
    </div>
  );
};
