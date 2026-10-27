import { useNavigate } from 'react-router-dom';
import {
  AlertTriangle,
  CalendarDays,
  ClipboardList,
  DoorOpen,
  FilePlus,
  GraduationCap,
  Mail,
  MailPlus,
  Send,
  UserPlus,
} from 'lucide-react';
import { useDatosTablero, type TableroAdministrativo } from '../../api/dashboard';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { ListaVacia, PanelTablero, TableroError, TableroSkeleton } from '@/components/ui/Tablero';
import { Button } from '@/components/ui/button';

export const AdministrativoInicio = () => {
  const navigate = useNavigate();
  const { datos, isLoading, isError, refetch, isFetching } =
    useDatosTablero<TableroAdministrativo>();

  const encabezado = (
    <PageHeader
      title="Inicio — Administración"
      description="Pendientes operativos de la unidad educativa"
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
        <TableroSkeleton tarjetas={6} paneles={4} />
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

      {/* Accesos rápidos */}
      <div className="flex flex-wrap gap-3">
        <Button onClick={() => navigate('/estudiantes')}>
          <UserPlus className="h-4 w-4" /> Crear estudiante
        </Button>
        <Button onClick={() => navigate('/citaciones')} variant="secondary">
          <MailPlus className="h-4 w-4" /> Crear citación
        </Button>
        <Button onClick={() => navigate('/aulas')} variant="secondary">
          <FilePlus className="h-4 w-4" /> Crear actividad
        </Button>
      </div>

      {/* Indicadores */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <StatCard
          title="Aulas sin profesor"
          value={datos.aulas_sin_profesor.total}
          icon={<DoorOpen className="h-5 w-5" />}
          accent={datos.aulas_sin_profesor.total > 0 ? 'danger' : 'success'}
          hint={datos.gestion_actual ? `gestión ${datos.gestion_actual}` : undefined}
        />
        <StatCard
          title="Materias sin horario"
          value={datos.materias_sin_horario.total}
          icon={<ClipboardList className="h-5 w-5" />}
          accent={datos.materias_sin_horario.total > 0 ? 'warning' : 'success'}
        />
        <StatCard
          title="Actividades esta semana"
          value={datos.actividades_semana.total}
          icon={<CalendarDays className="h-5 w-5" />}
          accent="info"
        />
        <StatCard
          title="Estudiantes sin inscribir"
          value={datos.estudiantes_sin_inscribir.total}
          icon={<GraduationCap className="h-5 w-5" />}
          accent={datos.estudiantes_sin_inscribir.total > 0 ? 'warning' : 'success'}
        />
        <StatCard
          title="Citaciones enviadas"
          value={datos.citaciones.enviadas}
          icon={<Send className="h-5 w-5" />}
          accent="primary"
          hint={`${datos.citaciones.vistas} vistas`}
        />
        <StatCard
          title="Citaciones por enviar"
          value={datos.citaciones.sin_leer}
          icon={<Mail className="h-5 w-5" />}
          accent={datos.citaciones.sin_leer > 0 ? 'danger' : 'success'}
        />
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Aulas sin profesor asignado"
          descripcion="Requieren asignación de docente"
          icono={<DoorOpen className="size-4" />}
          verA="/aulas"
        >
          {datos.aulas_sin_profesor.items.length === 0 ? (
            <ListaVacia mensaje="Todas las aulas tienen profesor asignado." />
          ) : (
            <ul className="flex flex-wrap gap-2">
              {datos.aulas_sin_profesor.items.map((a) => (
                <li
                  key={a.id}
                  className="rounded-lg border border-border/70 bg-muted/40 px-3 py-1.5 text-sm"
                >
                  {a.nombre}
                  {a.turno ? <span className="text-muted-foreground"> · {a.turno}</span> : null}
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Materias sin horario"
          descripcion="Materias con aula asignada pero sin bloque de horario"
          icono={<ClipboardList className="size-4" />}
          verA="/materias"
        >
          {datos.materias_sin_horario.items.length === 0 ? (
            <ListaVacia mensaje="Todas las materias tienen horario definido." />
          ) : (
            <ul className="flex flex-wrap gap-2">
              {datos.materias_sin_horario.items.map((m) => (
                <li
                  key={m.id}
                  className="rounded-lg border border-border/70 bg-muted/40 px-3 py-1.5 text-sm"
                >
                  {m.nombre}
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Actividades que vencen esta semana"
          descripcion={`${datos.actividades_semana.total} actividad(es)`}
          icono={<CalendarDays className="size-4" />}
        >
          {datos.actividades_semana.items.length === 0 ? (
            <ListaVacia mensaje="No hay actividades con vencimiento esta semana." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.actividades_semana.items.map((a) => (
                <li key={a.id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{a.titulo}</p>
                    <p className="truncate text-xs text-muted-foreground">
                      {a.materia ?? '—'} · Aula {a.aula}
                    </p>
                  </div>
                  {a.vencida ? (
                    <span className="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/15 dark:text-red-300">
                      Vencida · {a.fecha}
                    </span>
                  ) : (
                    <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                      {a.fecha}
                    </span>
                  )}
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Estudiantes sin inscribir"
          descripcion={`${datos.estudiantes_sin_inscribir.total} en la gestión actual`}
          icono={<GraduationCap className="size-4" />}
          verA="/estudiantes"
        >
          {datos.estudiantes_sin_inscribir.items.length === 0 ? (
            <ListaVacia
              icon={<AlertTriangle className="size-5" />}
              mensaje="Todos los estudiantes activos están inscritos."
            />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.estudiantes_sin_inscribir.items.map((e) => (
                <li key={e.id} className="flex items-center justify-between gap-3 py-2.5">
                  <p className="truncate text-sm font-medium text-foreground">{e.nombre}</p>
                  <span className="shrink-0 text-xs text-muted-foreground">Sin inscribir</span>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>
      </div>
    </div>
  );
};
