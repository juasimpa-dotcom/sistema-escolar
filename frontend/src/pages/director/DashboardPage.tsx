import { useState } from 'react';
import {
  Bar,
  BarChart,
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
import { AlertTriangle, CalendarCheck, FileWarning, Users } from 'lucide-react';
import { useDashboardResumen } from '../../api/citaciones';
import { useAulas } from '../../api/academico';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';

const COLORES: Record<string, string> = {
  presente: '#22c55e',
  atraso: '#f59e0b',
  falta: '#ef4444',
  licencia: '#3b82f6',
};

const hoy = () => new Date().toISOString().slice(0, 10);

export const DashboardPage = () => {
  const [fecha, setFecha] = useState(hoy());
  const [aulaId, setAulaId] = useState<string>('');
  const [umbral, setUmbral] = useState(51);

  const { data: aulas } = useAulas({ por_pagina: 100 });
  const { data, isLoading } = useDashboardResumen({
    fecha,
    aula_id: aulaId === '' ? undefined : Number(aulaId),
    umbral,
  });

  return (
    <div className="space-y-6">
      <PageHeader
        title="Panel del Director"
        description="Indicadores de asistencia, seguimiento y alertas académicas"
      />

      <Card className="border-border/60 shadow-sm">
        <CardContent className="flex flex-wrap items-end gap-4 p-4">
          <div className="space-y-1">
            <Label htmlFor="fecha">Fecha</Label>
            <Input id="fecha" type="date" value={fecha} onChange={(e) => setFecha(e.target.value)} className="w-[200px]" />
          </div>
          <div className="space-y-1">
            <Label>Aula</Label>
            <Select value={aulaId || 'all'} onValueChange={(value) => setAulaId(!value || value === 'all' ? '' : value)}>
              <SelectTrigger className="w-[220px]">
                <SelectValue placeholder="Todas las aulas" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todas las aulas</SelectItem>
                {aulas?.data.map((a) => (
                  <SelectItem key={a.id} value={String(a.id)}>
                    {a.nombre}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1">
            <Label htmlFor="umbral">Umbral de notas bajas</Label>
            <Input
              id="umbral"
              type="number"
              min={0}
              max={100}
              value={umbral}
              onChange={(e) => setUmbral(Number(e.target.value))}
              className="w-[160px]"
            />
          </div>
        </CardContent>
      </Card>

      {isLoading ? (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Card key={i} className="border-border/60">
              <CardContent className="flex items-center gap-4 p-6">
                <Skeleton className="h-12 w-12 rounded-xl" />
                <div className="flex-1 space-y-2">
                  <Skeleton className="h-4 w-24" />
                  <Skeleton className="h-8 w-16" />
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : null}

      {data && !isLoading && (
        <>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard title="Estudiantes inscritos" value={data.total_estudiantes} icon={<Users className="h-5 w-5" />} />
            <StatCard title="% de presencia" value={`${data.porcentaje_presentes}%`} icon={<CalendarCheck className="h-5 w-5" />} />
            <StatCard title="Estudiantes con faltas" value={data.con_faltas} icon={<AlertTriangle className="h-5 w-5" />} />
            <StatCard title="Estudiantes con notas bajas" value={data.con_notas_bajas} icon={<FileWarning className="h-5 w-5" />} />
          </div>

          <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <Card className="border-border/60 shadow-sm">
              <CardHeader>
                <CardTitle>Asistencia por estado</CardTitle>
                <CardDescription>
                  {data.total_registros_asistencia} registro(s) · {data.con_licencia} estudiante(s) con licencia
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                  <BarChart data={data.asistencia_por_estado}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} className="stroke-border" />
                    <XAxis dataKey="label" fontSize={12} tickLine={false} axisLine={false} />
                    <YAxis allowDecimals={false} fontSize={12} tickLine={false} axisLine={false} />
                    <Tooltip />
                    <Bar dataKey="total" name="Estudiantes" radius={[8, 8, 0, 0]}>
                      {data.asistencia_por_estado.map((e) => (
                        <Cell key={e.estado} fill={COLORES[e.estado] ?? '#94a3b8'} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            <Card className="border-border/60 shadow-sm">
              <CardHeader>
                <CardTitle>Distribución de asistencia</CardTitle>
                <CardDescription>Proporción por estado en el rango seleccionado</CardDescription>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                  <PieChart>
                    <Pie
                      data={data.asistencia_por_estado.filter((e) => e.total > 0)}
                      dataKey="total"
                      nameKey="label"
                      outerRadius={100}
                      label
                    >
                      {data.asistencia_por_estado.filter((e) => e.total > 0).map((e) => (
                        <Cell key={e.estado} fill={COLORES[e.estado] ?? '#94a3b8'} />
                      ))}
                    </Pie>
                    <Tooltip />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </div>
  );
};
