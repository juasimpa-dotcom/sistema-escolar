import { useNavigate } from 'react-router-dom';
import {
  CalendarCheck,
  FileText,
  GraduationCap,
  Mail,
  UserRound,
  Users,
} from 'lucide-react';
import { useDatosTablero, type TableroPadre, type ResumenAsistencia } from '../../api/dashboard';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { ListaVacia, PanelTablero, TableroError, TableroSkeleton } from '@/components/ui/Tablero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { EmptyState } from '@/components/ui/EmptyState';

const BarraAsistencia = ({ resumen }: { resumen: ResumenAsistencia }) => (
  <div className="space-y-1.5">
    <div className="flex items-center justify-between text-sm">
      <span className="text-muted-foreground">Asistencia del mes</span>
      <span className="font-semibold tabular-nums text-foreground">{resumen.porcentaje}%</span>
    </div>
    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
      <div
        className={`h-2 rounded-full ${resumen.porcentaje >= 80 ? 'bg-green-500' : 'bg-amber-500'}`}
        style={{ width: `${Math.min(100, resumen.porcentaje)}%` }}
      />
    </div>
    <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
      <span>{resumen.presentes} presentes</span>
      <span>{resumen.faltas} faltas</span>
      <span>{resumen.atrasos} atrasos</span>
      <span>{resumen.licencias} con licencia</span>
    </div>
  </div>
);

export const FamiliaInicio = () => {
  const navigate = useNavigate();
  const { datos, isLoading, isError, refetch, isFetching } = useDatosTablero<TableroPadre>();

  const encabezado = (
    <PageHeader
      title="Panel de familia"
      description="Resumen académico de tus hijos en un solo lugar"
      actions={
        <div className="flex gap-2">
          <Button onClick={() => refetch()} variant="outline" disabled={isFetching}>
            Actualizar
          </Button>
          <Button onClick={() => navigate('/familia/licencias')}>
            <FileText className="h-4 w-4" /> Solicitar licencia
          </Button>
        </div>
      }
    />
  );

  if (isLoading) {
    return (
      <div className="space-y-6">
        {encabezado}
        <TableroSkeleton tarjetas={3} paneles={2} />
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

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatCard
          title="Hijos registrados"
          value={datos.totales.hijos}
          icon={<Users className="h-5 w-5" />}
          accent="primary"
        />
        <StatCard
          title="Citaciones sin leer"
          value={datos.totales.citaciones_sin_leer}
          icon={<Mail className="h-5 w-5" />}
          accent={datos.totales.citaciones_sin_leer > 0 ? 'danger' : 'success'}
        />
        <StatCard
          title="Licencias en curso"
          value={datos.totales.licencias_en_curso}
          icon={<FileText className="h-5 w-5" />}
          accent={datos.totales.licencias_en_curso > 0 ? 'info' : 'success'}
        />
      </div>

      {datos.hijos.length === 0 ? (
        <PanelTablero titulo="Mis hijos" icono={<GraduationCap className="size-4" />}>
          <ListaVacia
            icon={<UserRound className="size-5" />}
            mensaje="No tienes estudiantes a tu cargo. Comunícate con la unidad educativa."
          />
        </PanelTablero>
      ) : (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
          {datos.hijos.map((hijo) => (
            <Card key={hijo.estudiante_id} className="border-border/60 shadow-sm">
              <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <CardTitle className="flex items-center gap-2">
                    <UserRound className="size-4 text-muted-foreground" />
                    {hijo.nombre}
                  </CardTitle>
                  <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-foreground">
                    {hijo.aula}
                  </span>
                </div>
              </CardHeader>

              <CardContent className="space-y-4">
                <BarraAsistencia resumen={hijo.asistencia_mes} />

                <div className="flex flex-wrap gap-2 text-xs">
                  <span className="rounded-full bg-amber-100 px-2.5 py-1 font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                    {hijo.actividades_pendientes} actividad(es) pendiente(s)
                  </span>
                  <span className="rounded-full bg-blue-100 px-2.5 py-1 font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                    {hijo.licencias_en_curso} licencia(s) en curso
                  </span>
                  <span
                    className={`rounded-full px-2.5 py-1 font-medium ${
                      hijo.citaciones_sin_leer > 0
                        ? 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'
                        : 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300'
                    }`}
                  >
                    {hijo.citaciones_sin_leer} citación(es) sin leer
                  </span>
                </div>

                <div className="space-y-2">
                  <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    Últimas notas
                  </p>
                  {hijo.ultimas_notas.length === 0 ? (
                    <EmptyState title="Sin notas registradas aún" compact className="border-0 bg-transparent px-0" />
                  ) : (
                    <ul className="divide-y divide-border/60">
                      {hijo.ultimas_notas.map((n, i) => (
                        <li key={`${n.actividad}-${i}`} className="flex items-center justify-between gap-3 py-1.5">
                          <span className="truncate text-sm text-foreground">{n.actividad}</span>
                          <span className="shrink-0 text-sm font-semibold tabular-nums text-foreground">
                            {n.nota}
                            {n.puntaje_maximo ? <span className="text-muted-foreground">/{n.puntaje_maximo}</span> : null}
                          </span>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>

                <Button variant="outline" size="sm" onClick={() => navigate('/familia/portal')}>
                  <CalendarCheck className="h-4 w-4" /> Ver portal completo
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
};
