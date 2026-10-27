import { useState } from 'react';
import {
  BarChart,
  Bar,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import { AlertTriangle, Building2, CalendarCheck, GraduationCap, MapPin, Plus, Users } from 'lucide-react';
import { useDatosTablero, type FiltrosTablero, type TableroSuperadmin } from '../../api/dashboard';
import { useCatalogoDistritos, useCatalogoMacrodistritos } from '../../api/catalogos';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { MacroBadge } from '@/components/ui/MacroBadge';
import { ListaVacia, PanelTablero, TableroError, TableroSkeleton } from '@/components/ui/Tablero';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const COLOR_SEXO: Record<string, string> = {
  F: '#ec4899',
  M: '#3b82f6',
  N: '#94a3b8',
};

export const SuperadminInicio = () => {
  const [macro, setMacro] = useState('');
  const [distrito, setDistrito] = useState('');

  const { data: macros } = useCatalogoMacrodistritos();
  const { data: distritos } = useCatalogoDistritos(macro);

  const filtros: FiltrosTablero = {
    macrodistrito_id: macro ? Number(macro) : '',
    distrito_municipal_id: distrito ? Number(distrito) : '',
  };

  const { datos, isLoading, isError, refetch, isFetching } = useDatosTablero<TableroSuperadmin>(filtros);

  const limpiar = () => {
    setMacro('');
    setDistrito('');
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Inicio — Visión municipal"
          description="Indicadores globales de unidades, matrícula y asistencia"
        />
        <TableroSkeleton tarjetas={4} paneles={4} />
      </div>
    );
  }

  if (isError || !datos) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Inicio — Visión municipal"
          description="Indicadores globales de unidades, matrícula y asistencia"
        />
        <TableroError onReintentar={() => refetch()} />
      </div>
    );
  }

  const maxNivel = Math.max(1, ...datos.matricula_por_nivel.map((n) => n.total));

  return (
    <div className="space-y-6">
      <PageHeader
        title="Inicio — Visión municipal"
        description="Indicadores globales de unidades, matrícula y asistencia"
        actions={
          <Button onClick={() => refetch()} variant="outline" disabled={isFetching}>
            Actualizar
          </Button>
        }
      />

      {/* Filtros territoriales: actualizan todo el tablero */}
      <Card className="border-border/60 shadow-sm">
        <CardContent className="flex flex-wrap items-end gap-4 p-4">
          <div className="space-y-1">
            <Label>Macrodistrito</Label>
            <Select
              value={macro || 'todos'}
              onValueChange={(v) => {
                setMacro(!v || v === 'todos' ? '' : v);
                setDistrito('');
              }}
            >
              <SelectTrigger className="w-[240px]">
                <SelectValue placeholder="Todos los macrodistritos" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="todos">Todos los macrodistritos</SelectItem>
                {macros?.map((m) => (
                  <SelectItem key={m.id} value={String(m.id)}>
                    {m.codigo} — {m.nombre}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1">
            <Label>Distrito municipal</Label>
            <Select
              value={distrito || 'todos'}
              onValueChange={(v) => setDistrito(!v || v === 'todos' ? '' : v)}
              disabled={!macro}
            >
              <SelectTrigger className="w-[240px]">
                <SelectValue placeholder="Todos los distritos" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="todos">Todos los distritos</SelectItem>
                {distritos?.map((d) => (
                  <SelectItem key={d.id} value={String(d.id)}>
                    {d.nombre}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <Button variant="ghost" onClick={limpiar} disabled={!macro && !distrito}>
            Limpiar filtros
          </Button>
        </CardContent>
      </Card>

      {/* Indicadores */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Unidades educativas"
          value={datos.totales.unidades}
          icon={<Building2 className="h-5 w-5" />}
          accent="primary"
        />
        <StatCard
          title="Estudiantes"
          value={datos.totales.estudiantes}
          icon={<GraduationCap className="h-5 w-5" />}
          accent="info"
        />
        <StatCard
          title="Docentes"
          value={datos.totales.docentes}
          icon={<Users className="h-5 w-5" />}
          accent="success"
        />
        <StatCard
          title="% de asistencia de hoy"
          value={`${datos.asistencia_hoy.porcentaje}%`}
          icon={<CalendarCheck className="h-5 w-5" />}
          accent={datos.asistencia_hoy.porcentaje >= 80 ? 'success' : 'warning'}
          hint={`${datos.asistencia_hoy.presentes} presentes de ${datos.asistencia_hoy.total}`}
        />
      </div>

      {/* Gráficos */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Matrícula por macrodistrito"
          descripcion="Estudiantes inscritos agrupados por macrodistrito"
          icono={<MapPin className="size-4" />}
        >
          {datos.matricula_por_macrodistrito.length === 0 ? (
            <ListaVacia mensaje="No hay matrícula registrada con estos filtros." />
          ) : (
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={datos.matricula_por_macrodistrito}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} className="stroke-border" />
                <XAxis dataKey="codigo" fontSize={12} tickLine={false} axisLine={false} />
                <YAxis allowDecimals={false} fontSize={12} tickLine={false} axisLine={false} />
                <Tooltip />
                <Bar dataKey="total" name="Estudiantes" fill="#6366f1" radius={[8, 8, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Estudiantes mujeres vs varones"
          descripcion="Distribución por sexo de la matrícula activa"
          icono={<Users className="size-4" />}
        >
          {datos.estudiantes_por_sexo.length === 0 ? (
            <ListaVacia mensaje="Sin datos de sexo para el rango seleccionado." />
          ) : (
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={datos.estudiantes_por_sexo}
                  dataKey="total"
                  nameKey="label"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={2}
                  label
                >
                  {datos.estudiantes_por_sexo.map((s) => (
                    <Cell key={s.clave} fill={COLOR_SEXO[s.clave] ?? '#94a3b8'} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          )}
        </PanelTablero>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <PanelTablero
          titulo="Matrícula por nivel"
          descripcion="Estudiantes inscritos por nivel educativo"
          icono={<GraduationCap className="size-4" />}
        >
          {datos.matricula_por_nivel.length === 0 ? (
            <ListaVacia mensaje="No hay inscripciones registradas." />
          ) : (
            <ul className="space-y-3">
              {datos.matricula_por_nivel.map((nivel) => (
                <li key={nivel.nivel} className="space-y-1">
                  <div className="flex items-center justify-between text-sm">
                    <span className="font-medium text-foreground">{nivel.label}</span>
                    <span className="tabular-nums text-muted-foreground">{nivel.total}</span>
                  </div>
                  <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                      className="h-2 rounded-full bg-indigo-500"
                      style={{ width: `${Math.round((nivel.total / maxNivel) * 100)}%` }}
                    />
                  </div>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>

        <PanelTablero
          titulo="Últimas unidades creadas"
          descripcion="Alta reciente de establecimientos"
          icono={<Plus className="size-4" />}
          verA="/unidades"
        >
          {datos.ultimas_unidades.length === 0 ? (
            <ListaVacia mensaje="Todavía no se han creado unidades." />
          ) : (
            <ul className="divide-y divide-border/60">
              {datos.ultimas_unidades.map((u) => (
                <li key={u.id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-foreground">{u.nombre}</p>
                    <p className="text-xs text-muted-foreground">
                      {u.codigo}
                      {u.tipo ? ` · ${u.tipo}` : ''}
                      {u.zona ? ` · ${u.zona}` : ''}
                    </p>
                  </div>
                  <div className="flex shrink-0 items-center gap-2">
                    {u.macrodistrito && <MacroBadge codigo={u.macrodistrito} />}
                    <span className="text-xs tabular-nums text-muted-foreground">{u.creada}</span>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </PanelTablero>
      </div>

      <PanelTablero
        titulo="Unidades con más faltas o abandono"
        descripcion="Top 5 de establecimientos en alerta (últimos 30 días)"
        icono={<AlertTriangle className="size-4" />}
        verA="/unidades"
      >
        {datos.unidades_alerta.length === 0 ? (
          <ListaVacia mensaje="Ninguna unidad está en alerta. ¡Todo en orden!" />
        ) : (
          <ul className="divide-y divide-border/60">
            {datos.unidades_alerta.map((u) => (
              <li key={u.id} className="flex flex-wrap items-center justify-between gap-3 py-2.5">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-foreground">{u.nombre}</p>
                  <p className="text-xs text-muted-foreground">
                    {u.codigo}
                    {u.macrodistrito ? ` · ${u.macrodistrito}` : ''}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-2 text-xs">
                  <span className="rounded-full bg-red-100 px-2 py-0.5 font-medium text-red-700 dark:bg-red-500/15 dark:text-red-300">
                    {u.faltas} faltas
                  </span>
                  <span className="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                    {u.abandono} abandono
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </PanelTablero>
    </div>
  );
};
