import { useState } from 'react';
import { CalendarCheck, ClipboardList, FileText, GraduationCap, Mail, TrendingDown, UserRound } from 'lucide-react';
import type { PortalResumen } from '../../types/portal';

const estadoAsistenciaBadge = (estado: string) => {
  const mapa: Record<string, string> = {
    presente: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    falta: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
    atraso: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    licencia: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
  };
  return mapa[estado] ?? 'bg-muted text-muted-foreground';
};

const estadoLicenciaBadge = (estado: string) => {
  const mapa: Record<string, string> = {
    pendiente: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    aprobada: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    rechazada: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
  };
  return mapa[estado] ?? 'bg-muted text-muted-foreground';
};

const Tarjeta = ({ label, valor, icon: Icon, color }: { label: string; valor: number | string; icon: typeof Mail; color: string }) => (
  <div className="flex items-center gap-3 rounded-lg border border-border/60 bg-card p-4 shadow-sm">
    <div className={`flex h-10 w-10 items-center justify-center rounded-full ${color}`}>
      <Icon size={18} />
    </div>
    <div>
      <div className="text-xl font-semibold text-foreground">{valor}</div>
      <div className="text-xs text-muted-foreground">{label}</div>
    </div>
  </div>
);

type Pestana = 'materias' | 'asistencia' | 'citaciones' | 'licencias';

export const PortalResumenView = ({ data }: { data: PortalResumen }) => {
  const [pestana, setPestana] = useState<Pestana>('materias');

  const pestanas: { id: Pestana; label: string }[] = [
    { id: 'materias', label: 'Materias y notas' },
    { id: 'asistencia', label: 'Asistencia' },
    { id: 'citaciones', label: 'Citaciones' },
    { id: 'licencias', label: 'Licencias' },
  ];

  return (
    <div className="space-y-4">
      <div className="rounded-lg border border-border/60 bg-card p-4 shadow-sm">
        <div className="flex items-center gap-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
            <UserRound size={22} />
          </div>
          <div>
            <h2 className="text-lg font-semibold text-foreground">{data.estudiante.nombre_completo ?? 'Estudiante'}</h2>
            <p className="text-sm text-muted-foreground">
              CI: {data.estudiante.ci ?? '-'}
              {data.aula && <> · {data.aula.nombre} · {data.aula.gestion ?? ''}</>}
            </p>
          </div>
        </div>
      </div>

      {!data.aula && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
          El estudiante no tiene inscripción activa en una gestión.
        </div>
      )}

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Tarjeta label="Presentes" valor={data.asistencia.presentes} icon={CalendarCheck} color="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300" />
        <Tarjeta label="Faltas" valor={data.asistencia.faltas} icon={TrendingDown} color="bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300" />
        <Tarjeta label="Atrasos" valor={data.asistencia.atrasos} icon={CalendarCheck} color="bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300" />
        <Tarjeta label="Pendientes" valor={data.pendientes.length} icon={ClipboardList} color="bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300" />
      </div>

      <div className="flex flex-wrap gap-2">
        {pestanas.map((p) => (
          <button
            key={p.id}
            type="button"
            onClick={() => setPestana(p.id)}
            aria-pressed={pestana === p.id}
            className={`rounded-full border px-4 py-1.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50 ${pestana === p.id ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground hover:bg-accent hover:text-accent-foreground'}`}
          >
            {p.label}
          </button>
        ))}
      </div>

      {pestana === 'materias' && (
        <div className="space-y-3">
          {data.pendientes.length > 0 && (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
              <h3 className="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-800 dark:text-amber-300">
                <ClipboardList size={16} /> Actividades pendientes de calificación
              </h3>
              <ul className="space-y-1 text-sm text-amber-800 dark:text-amber-300">
                {data.pendientes.map((p) => (
                  <li key={p.actividad_id}>
                    {p.titulo} — {p.materia ?? 'Materia'} ({p.fecha_cumplimiento ?? 'sin fecha'})
                  </li>
                ))}
              </ul>
            </div>
          )}
          {data.materias.length === 0 && (
            <p className="rounded-lg border border-border/60 bg-card p-6 text-center text-sm text-muted-foreground shadow-sm">
              Sin materias asignadas.
            </p>
          )}
          {data.materias.map((m) => (
            <div key={m.aula_materia_id} className="rounded-lg border border-border/60 bg-card p-4 shadow-sm">
              <div className="mb-2 flex items-center justify-between">
                <div>
                  <h3 className="font-semibold text-foreground">{m.materia ?? 'Materia'}</h3>
                  <p className="text-xs text-muted-foreground">Prof. {m.profesor ?? '-'}</p>
                </div>
                <div className="text-right">
                  <div className="text-lg font-semibold text-primary">{m.promedio ?? '-'}</div>
                  <div className="text-xs text-muted-foreground">Promedio</div>
                </div>
              </div>
              <div className="overflow-x-auto">
                <table className="min-w-full text-sm">
                  <thead className="text-left text-xs text-muted-foreground">
                    <tr>
                      <th className="py-1">Actividad</th>
                      <th className="py-1">Fecha</th>
                      <th className="py-1 text-right">Nota</th>
                    </tr>
                  </thead>
                  <tbody>
                    {m.actividades.map((a) => (
                      <tr key={a.actividad_id} className="border-t border-border">
                        <td className="py-1.5 text-foreground">{a.titulo}</td>
                        <td className="py-1.5 text-muted-foreground">{a.fecha_cumplimiento ?? '-'}</td>
                        <td className="py-1.5 text-right">
                          {a.registrada ? (
                            <span className="font-medium text-foreground">
                              {a.nota} / {a.puntaje_maximo}
                              {a.fue_modificada && <span className="ml-1 text-xs text-amber-600 dark:text-amber-400">(mod.)</span>}
                            </span>
                          ) : (
                            <span className="text-xs text-muted-foreground/70">Pendiente</span>
                          )}
                        </td>
                      </tr>
                    ))}
                    {m.actividades.length === 0 && (
                      <tr><td colSpan={3} className="py-2 text-center text-xs text-muted-foreground/70">Sin actividades</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          ))}
        </div>
      )}

      {pestana === 'asistencia' && (
        <div className="overflow-x-auto rounded-lg border border-border/60 bg-card shadow-sm">
          <table className="min-w-full text-sm">
            <thead className="border-b border-border bg-muted/50 text-left text-muted-foreground">
              <tr>
                <th className="px-4 py-3">Fecha</th>
                <th className="px-4 py-3">Estado</th>
                <th className="px-4 py-3">Materia</th>
                <th className="px-4 py-3">Observación</th>
              </tr>
            </thead>
            <tbody>
              {data.asistencia.detalle.map((a) => (
                <tr key={a.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-2 text-foreground">{a.fecha}</td>
                  <td className="px-4 py-2">
                    <span className={`rounded px-2 py-0.5 text-xs ${estadoAsistenciaBadge(a.estado)}`}>{a.estado_label}</span>
                  </td>
                  <td className="px-4 py-2 text-foreground">{a.materia ?? '-'}</td>
                  <td className="px-4 py-2 text-muted-foreground">{a.observacion ?? '-'}</td>
                </tr>
              ))}
              {data.asistencia.detalle.length === 0 && (
                <tr><td colSpan={4} className="px-4 py-6 text-center text-muted-foreground">Sin registros de asistencia.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {pestana === 'citaciones' && (
        <div className="space-y-3">
          {data.citaciones.length === 0 && (
            <p className="rounded-lg border border-border/60 bg-card p-6 text-center text-sm text-muted-foreground shadow-sm">Sin citaciones.</p>
          )}
          {data.citaciones.map((c) => (
            <div key={c.id} className="rounded-lg border border-border/60 bg-card p-4 shadow-sm">
              <div className="flex items-center justify-between">
                <h3 className="flex items-center gap-2 font-semibold text-foreground"><Mail size={16} /> {c.asunto}</h3>
                <span className="text-xs text-muted-foreground">{c.fecha_hora}</span>
              </div>
              <p className="mt-1 text-sm text-muted-foreground">{c.mensaje}</p>
              <div className="mt-2 flex flex-wrap gap-3 text-xs text-muted-foreground">
                <span>{c.tipo_label}</span>
                {c.lugar && <span>Lugar: {c.lugar}</span>}
                <span>De: {c.emisor ?? '-'}</span>
              </div>
            </div>
          ))}
        </div>
      )}

      {pestana === 'licencias' && (
        <div className="space-y-3">
          {data.licencias.length === 0 && (
            <p className="rounded-lg border border-border/60 bg-card p-6 text-center text-sm text-muted-foreground shadow-sm">Sin licencias registradas.</p>
          )}
          {data.licencias.map((l) => (
            <div key={l.id} className="rounded-lg border border-border/60 bg-card p-4 shadow-sm">
              <div className="flex items-center justify-between">
                <h3 className="flex items-center gap-2 font-semibold text-foreground"><FileText size={16} /> {l.fecha_inicio} → {l.fecha_fin}</h3>
                <span className={`rounded px-2 py-0.5 text-xs ${estadoLicenciaBadge(l.estado)}`}>{l.estado_label}</span>
              </div>
              <p className="mt-1 text-sm text-muted-foreground">{l.motivo}</p>
              {l.motivo_rechazo && <p className="mt-1 text-xs text-destructive">Motivo de rechazo: {l.motivo_rechazo}</p>}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export const PortalIconoMateria = GraduationCap;
