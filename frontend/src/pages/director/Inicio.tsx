import { useState } from 'react';
import {
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import {
  AlertTriangle,
  BookOpenCheck,
  CalendarCheck,
  FileWarning,
  FileText,
  GraduationCap,
  Mail,
  UserMinus,
} from 'lucide-react';
import { useDatosTablero, type TableroDirector } from '../../api/dashboard';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { ListaVacia, PanelTablero, TableroError, TableroSkeleton } from '@/components/ui/Tablero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export const DirectorInicio = () => {
  const [umbral, setUmbral] = useState(51);

  const { datos, isLoading, isError, refetch, isFetching } = useDatosTablero<TableroDirector>({
    umbral,
  });

  const encabezado = (
    <PageHeader
      title="Inicio — Dirección"
      description="Asistencia de hoy, alertas académicas y seguimiento de la unidad"
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

  const serie = datos.tendencia.map((d) => ({ ...d, dia: d.fecha.slice(5) }));

  return (
    <div className="space-y-6">
      {encabezado}

      {/* Umbral configurable de notas bajas */}
      <Card className="border-border/60 shadow-sm">
        <CardContent className="flex flex-wrap items-end gap-4 p-4">
          <div className="space-y-1">
            <Label htmlFor="umbral">Umbral de notas bajas</Label>
            <Input
              id="umbral"
              type="number"
              min={0}
              max={100}
              value={umbral}
              onChange={(e) => setUmbral(Number(e.target.value))}
              className="w-[180px]"
            />
          </div>
          <p className="text-xs text-muted-foreground">
            Considera como riesgo pedagógico todo promedio menor a {umbral} puntos.
          </p>
        </CardContent>
      </Card>

      {/* Indicadores de hoy */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <StatCard
          title="Estudiantes"
          value={datos.totales.estudiantes}
          icon={<GraduationCap className="h-5 w-5" />}
          accent="primary"
        />
        <StatCard
          title="% presentes hoy"
          value={`${datos.totales.porcentaje_presentes_hoy}%`}
          icon={<CalendarCheck className="h-5 w-5" />}
          accent="success"
          hint={`${datos.totales.registros_hoy} registros`}
        />
        <StatCard
          title="Con faltas hoy"
          value={datos.totales.faltas_hoy}
          icon={<AlertTriangle className="h-5 w-5" />}
          accent="danger"
        />
        <StatCard
          title="Con licencia hoy"
          value={datos.totales.licencias_hoy}
          icon={<FileText className="h-5 w-5" />}
          accent="info"
        />
        <StatCard
          title="Con atrasos hoy"
          value={datos.totales.atrasos_hoy}
          icon={<UserMinus className="h-5 w-5" />}
          accent="warning"
        />
        <StatCard
          title="Con notas bajas"
          value={datos.totales.notas_bajas}
          icon={<FileWarning className="h-5 w-5" />}
          accent="warning"
          hint={`umbral ${datos.umbral_notas}`}
        />
      </div>

      {/* Tendencia de faltas y atrasos */}
      <PanelTablero
        titulo="Tendencia de faltas y atrasos"
        descripcion="Últimos 30 días"
        icono={<CalendarCheck className="size-4" />}
      >
        {serie.every((d) => d.faltas === 0 && d.atrasos === 0) ? (
          <ListaVacia mensaje="No hay faltas ni atrasos registrados en los últimos 30 días." />
        ) : (
          <ResponsiveContainer width="100%" height={300}>
            <LineChart data={serie}>
              <CartesianGrid strokeDasharray="3 3" vertical={false} className="stroke-border" />
              <XAxis dataKey="dia" fontSize={11} tickLine={false} axisLine={false} interval={2} />
              <YAxis allowDecimals={false} fontSize={12} tickLine={false} axisLine={false} />
              <Tooltip />
              <Legend />
              <Line
                type="monotone"
                dataKey="faltas"
                name="Faltas"
                stroke="#ef4444"
                strokeWidth={2}
                dot={false}
              />
              <Line
                type="monotone"
                dataKey="atrasos"
                name="Atrasos"
                stroke="#f59e0b"
                strokeWidth={2}
                dot={false}
              />
            </LineChart>
          </ResponsiveContainer>
        )}
      </PanelTablero>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Licencias pendientes de aprobar"
          descripcion={`${datos.licencias_pendientes.total} solicitud(es) esperando resolución`}
          icono={<FileText className="size-4" />}
          verA="/licencias"
        >
          {datos.licencias_pendientes.items.length === 0 ? (
            <ListaVacia mensaje="No hay licencias pendientes de aprobación." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.licencias_pendientes.items.map((l) => (
                <li key={l.id} className="py-2.5">
                  <div className="flex items-center justify-between gap-3">
                    <p className="truncate text-sm font-medium text-foreground">{l.estudiante}</p>
                    <Badge variant="outline" className="shrink-0 text-xs">
                      {l.fecha_inicio} → {l.fecha_fin}
                    </Badge>
                  </div>
                  <p className="truncate text-xs text-muted-foreground">{l.motivo}</p>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Alumnos en riesgo pedagógico"
          descripcion={`Top 5 con promedio menor a ${datos.umbral_notas} · ${datos.riesgo.total} en total`}
          icono={<FileWarning className="size-4" />}
        >
          {datos.riesgo.items.length === 0 ? (
            <ListaVacia mensaje="Ningún alumno está por debajo del umbral. ¡Sigue así!" />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.riesgo.items.map((r) => (
                <li
                  key={r.estudiante_id}
                  className="flex items-center justify-between gap-3 py-2.5"
                >
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{r.nombre}</p>
                    <p className="text-xs text-muted-foreground">Aula {r.aula}</p>
                  </div>
                  <Badge className="shrink-0 bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300">
                    {r.promedio}
                  </Badge>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Citaciones recientes"
          descripcion={`${datos.citaciones_recientes.total} citación(es) en total`}
          icono={<Mail className="size-4" />}
          verA="/citaciones"
        >
          {datos.citaciones_recientes.items.length === 0 ? (
            <ListaVacia mensaje="Aún no se han enviado citaciones." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.citaciones_recientes.items.map((c) => (
                <li key={c.id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{c.asunto}</p>
                    <p className="truncate text-xs text-muted-foreground">
                      {c.destinatario} · {c.tipo}
                    </p>
                  </div>
                  <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                    {c.fecha_hora?.slice(0, 10)}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Profesores ausentes hoy"
          descripcion={`${datos.profesores_ausentes.total} docente(s) ausente(s)`}
          icono={<BookOpenCheck className="size-4" />}
        >
          {datos.profesores_ausentes.items.length === 0 ? (
            <ListaVacia mensaje="Todos los docentes están presentes hoy." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.profesores_ausentes.items.map((p, i) => (
                <li key={`${p.nombre}-${i}`} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{p.nombre}</p>
                    {p.observacion && (
                      <p className="truncate text-xs text-muted-foreground">{p.observacion}</p>
                    )}
                  </div>
                  <Badge
                    variant="outline"
                    className={
                      p.estado === 'falta'
                        ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/15 dark:text-red-300'
                        : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300'
                    }
                  >
                    {p.estado === 'falta' ? 'Falta' : 'Licencia'}
                  </Badge>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>
      </div>
    </div>
  );
};
