import { useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  useAsignarMateria, useAulaMaterias, useAulas, useCrearHorario, useEliminarHorario,
  useHorario, useInscripciones, useInscribir, useMaterias, useQuitarMateria, useRetirarInscripcion,
  useActividades, useCrearActividad, useEliminarActividad,
} from '../../api/academico';
import { useEstudiantes, usePersonas } from '../../api/gestion';
import { Modal } from '@/components/ui/Modal';
import { BajaModal } from '@/components/ui/BajaModal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { Buscador } from '@/components/ui/Buscador';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { CampoSelect, CampoTexto } from '@/components/ui/CampoTexto';
import { Select } from '@/components/ui/select-native';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractErrors, extractMessage } from '@/lib/errors';
import { longitud, numeroMin, requerido } from '@/lib/validacion';
import { toast } from 'sonner';
import { ArrowLeft, Loader2, Plus, Trash2 } from 'lucide-react';
import type { Actividad, AulaMateria, DiaSemana, Horario, Inscripcion } from '../../types/academico';

const DIAS: { value: DiaSemana; label: string }[] = [
  { value: 'lunes', label: 'Lunes' },
  { value: 'martes', label: 'Martes' },
  { value: 'miercoles', label: 'Miércoles' },
  { value: 'jueves', label: 'Jueves' },
  { value: 'viernes', label: 'Viernes' },
  { value: 'sabado', label: 'Sábado' },
];

type Tab = 'materias' | 'inscripciones' | 'horario' | 'actividades';

export const AulaDetallePage = () => {
  const { id } = useParams();
  const aulaId = Number(id);
  const [tab, setTab] = useState<Tab>('materias');

  const { data: aulasData } = useAulas({ por_pagina: 100 });
  const aula = useMemo(() => aulasData?.data.find((a) => a.id === aulaId), [aulasData, aulaId]);

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-3">
        <Link to="/aulas" className="text-muted-foreground hover:text-foreground"><ArrowLeft size={20} /></Link>
        <h1 className="text-2xl font-semibold tracking-tight">
          Aula {aula ? `${aula.nombre} — ${aula.gestion?.anio ?? ''} (${aula.turno_label})` : aulaId}
        </h1>
      </div>

      <Tabs value={tab} onValueChange={(v) => setTab(v as Tab)}>
        <TabsList variant="line" className="border-b border-border">
          <TabsTrigger value="materias">Materias y profesores</TabsTrigger>
          <TabsTrigger value="inscripciones">Estudiantes inscritos</TabsTrigger>
          <TabsTrigger value="horario">Horario semanal</TabsTrigger>
          <TabsTrigger value="actividades">Actividades</TabsTrigger>
        </TabsList>

        <TabsContent value="materias"><TabMaterias aulaId={aulaId} /></TabsContent>
        <TabsContent value="inscripciones"><TabInscripciones aulaId={aulaId} /></TabsContent>
        <TabsContent value="horario"><TabHorario aulaId={aulaId} /></TabsContent>
        <TabsContent value="actividades"><TabActividades aulaId={aulaId} /></TabsContent>
      </Tabs>
    </div>
  );
};

const TabMaterias = ({ aulaId }: { aulaId: number }) => {
  const { data: asignaciones, isLoading } = useAulaMaterias(aulaId);
  const { data: materiasData } = useMaterias({ por_pagina: 100 });
  const { data: profesoresData } = usePersonas({ rol: 'profesor', por_pagina: 100 });
  const asignar = useAsignarMateria(aulaId);
  const quitar = useQuitarMateria(aulaId);

  const [modalOpen, setModalOpen] = useState(false);
  const [error, setError] = useState('');
  const [aQuitar, setAQuitar] = useState<AulaMateria | null>(null);

  const form = useFormValidacion(
    { materia_id: '', profesor_id: '' },
    {
      materia_id: [requerido('Seleccioná una materia')],
      profesor_id: [requerido('Seleccioná un profesor')],
    }
  );

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;
    try {
      await asignar.mutateAsync({
        materia_id: Number(form.valores.materia_id),
        profesor_id: Number(form.valores.profesor_id),
      });
      form.reiniciar({ materia_id: '', profesor_id: '' });
      setModalOpen(false);
      toast.success('Materia asignada');
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo asignar la materia.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const columns: DataTableColumn<AulaMateria>[] = [
    { key: 'materia', header: 'Materia', render: (am) => am.materia?.nombre },
    { key: 'profesor', header: 'Profesor', render: (am) => am.profesor?.nombre_completo },
    { key: 'bloques', header: 'Bloques', render: (am) => am.horarios?.length ?? 0 },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (am) => (
        <Button variant="ghost" size="icon" onClick={() => setAQuitar(am)} title="Quitar materia">
          <Trash2 />
        </Button>
      ),
    },
  ];

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <Button onClick={() => setModalOpen(true)}>
          <Plus /> Asignar materia
        </Button>
      </div>

      <DataTable
        data={asignaciones ?? []}
        columns={columns}
        rowKey={(am) => am.id}
        loading={isLoading}
        empty={<EmptyState icon={<Plus />} title="Sin materias asignadas" description="Asigna una materia y su profesor." />}
      />

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Asignar materia y profesor">
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          {error && (
            <div role="alert" className="rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
          )}
          <CampoSelect
            label="Materia"
            value={form.valores.materia_id}
            onChange={(e) => form.setCampo('materia_id', e.target.value)}
            onBlur={form.onBlur('materia_id')}
            error={form.errorDe('materia_id')}
            required
          >
            <option value="">Seleccione...</option>
            {materiasData?.data.map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
          </CampoSelect>
          <CampoSelect
            label="Profesor"
            value={form.valores.profesor_id}
            onChange={(e) => form.setCampo('profesor_id', e.target.value)}
            onBlur={form.onBlur('profesor_id')}
            error={form.errorDe('profesor_id')}
            required
          >
            <option value="">Seleccione...</option>
            {profesoresData?.data.map((p) => <option key={p.id} value={p.id}>{p.nombre_completo}</option>)}
          </CampoSelect>
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => setModalOpen(false)} disabled={asignar.isPending}>Cancelar</Button>
            <Button type="submit" disabled={asignar.isPending}>
              {asignar.isPending && <Loader2 className="animate-spin" />} Asignar
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={!!aQuitar}
        onClose={() => setAQuitar(null)}
        onConfirm={async () => {
          try {
            await quitar.mutateAsync(aQuitar!.id);
            toast.success('Materia quitada del aula');
          } catch {
            toast.error('No se pudo quitar la materia');
            throw new Error();
          }
        }}
        title="Quitar materia"
        description={`¿Quitar ${aQuitar?.materia?.nombre} del aula?`}
        confirmLabel="Quitar"
        destructive
      />
    </div>
  );
};

const TabInscripciones = ({ aulaId }: { aulaId: number }) => {
  const { data: inscripciones, isLoading } = useInscripciones(aulaId);
  const [buscar, setBuscar] = useState('');
  const { data: estudiantesData } = useEstudiantes({ buscar, por_pagina: 20 });
  const inscribir = useInscribir(aulaId);
  const retirar = useRetirarInscripcion(aulaId);

  const [modalOpen, setModalOpen] = useState(false);
  const [error, setError] = useState('');
  const [aRetirar, setARetirar] = useState<Inscripcion | null>(null);

  const form = useFormValidacion(
    { estudiante_id: '' },
    { estudiante_id: [requerido('Seleccioná un estudiante')] }
  );

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;
    try {
      await inscribir.mutateAsync(Number(form.valores.estudiante_id));
      form.reiniciar({ estudiante_id: '' });
      setModalOpen(false);
      toast.success('Estudiante inscrito');
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo inscribir al estudiante.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const columns: DataTableColumn<Inscripcion>[] = [
    { key: 'ci', header: 'CI', render: (i) => <span className="font-mono">{i.estudiante?.ci}</span> },
    { key: 'nombre', header: 'Estudiante', render: (i) => i.estudiante?.nombre_completo },
    { key: 'fecha', header: 'Fecha inscripción', render: (i) => i.fecha_inscripcion || '-' },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (i) => (
        <Button variant="ghost" size="sm" className="text-destructive" onClick={() => setARetirar(i)}>
          Retirar
        </Button>
      ),
    },
  ];

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <Button onClick={() => setModalOpen(true)}>
          <Plus /> Inscribir estudiante
        </Button>
      </div>

      <DataTable
        data={inscripciones ?? []}
        columns={columns}
        rowKey={(i) => i.id}
        loading={isLoading}
        empty={<EmptyState icon={<Plus />} title="Sin estudiantes inscritos" description="Inscribe al primer estudiante." />}
      />

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Inscribir estudiante">
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          {error && (
            <div role="alert" className="rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
          )}
          <div className="space-y-1.5">
            <label className="text-sm font-medium leading-none">Buscar estudiante</label>
            <Buscador value={buscar} onChange={setBuscar} placeholder="Nombre o CI..." />
          </div>
          <CampoSelect
            label="Estudiante"
            value={form.valores.estudiante_id}
            onChange={(e) => form.setCampo('estudiante_id', e.target.value)}
            onBlur={form.onBlur('estudiante_id')}
            error={form.errorDe('estudiante_id')}
            required
          >
            <option value="">Seleccione...</option>
            {estudiantesData?.data.filter((e) => e.estado === 'activo').map((e) => (
              <option key={e.id} value={e.id}>{e.nombre_completo} (CI {e.ci})</option>
            ))}
          </CampoSelect>
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => setModalOpen(false)} disabled={inscribir.isPending}>Cancelar</Button>
            <Button type="submit" disabled={inscribir.isPending}>
              {inscribir.isPending && <Loader2 className="animate-spin" />} Inscribir
            </Button>
          </div>
        </form>
      </Modal>

      <BajaModal
        open={!!aRetirar}
        onClose={() => setARetirar(null)}
        nombre={aRetirar?.estudiante?.nombre_completo || ''}
        titulo="Retirar estudiante"
        onConfirm={(motivo, fecha) => retirar.mutateAsync({ id: aRetirar!.id, payload: { motivo, fecha } })}
      />
    </div>
  );
};

const TabHorario = ({ aulaId }: { aulaId: number }) => {
  const { data: horarios, isLoading } = useHorario(aulaId);
  const { data: asignaciones } = useAulaMaterias(aulaId);
  const crear = useCrearHorario(aulaId);
  const eliminar = useEliminarHorario(aulaId);

  const [modalOpen, setModalOpen] = useState(false);
  const [error, setError] = useState('');
  const [aEliminar, setAEliminar] = useState<Horario | null>(null);

  const form = useFormValidacion(
    { aula_materia_id: '', dia: 'lunes' as DiaSemana, hora_inicio: '08:00', hora_fin: '09:00' },
    {
      aula_materia_id: [requerido('Seleccioná una materia y profesor')],
      hora_inicio: [requerido('La hora de inicio es obligatoria')],
      hora_fin: [requerido('La hora de fin es obligatoria')],
    }
  );

  const errorHoraFin = (() => {
    const { hora_inicio, hora_fin } = form.valores;
    if (hora_inicio && hora_fin && hora_fin <= hora_inicio) {
      return 'La hora de fin debe ser posterior a la de inicio';
    }
    return form.errorDe('hora_fin');
  })();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (form.valores.hora_fin <= form.valores.hora_inicio) {
      form.setErrorServidor('hora_fin', 'La hora de fin debe ser posterior a la de inicio');
      return;
    }
    if (!form.validarTodo()) return;
    try {
      await crear.mutateAsync({
        aula_materia_id: Number(form.valores.aula_materia_id),
        dia: form.valores.dia,
        hora_inicio: form.valores.hora_inicio,
        hora_fin: form.valores.hora_fin,
      });
      setModalOpen(false);
      toast.success('Bloque agregado');
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo registrar el horario.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const porDia = (d: DiaSemana) =>
    (horarios ?? []).filter((h) => h.dia === d).sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <Button onClick={() => setModalOpen(true)}>
          <Plus /> Agregar bloque
        </Button>
      </div>

      {isLoading ? (
        <div className="py-6 text-center text-muted-foreground">Cargando...</div>
      ) : (
        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
          {DIAS.map((d) => (
            <div key={d.value} className="rounded-xl border border-border bg-card p-3 shadow-sm">
              <h3 className="mb-2 text-sm font-semibold">{d.label}</h3>
              <div className="space-y-2">
                {porDia(d.value).map((h) => (
                  <div key={h.id} className="flex items-center justify-between rounded border-l-4 border-primary bg-muted/40 px-3 py-2">
                    <div>
                      <div className="text-sm font-medium">{h.materia}</div>
                      <div className="text-xs text-muted-foreground">{h.hora_inicio.slice(0, 5)} - {h.hora_fin.slice(0, 5)}</div>
                    </div>
                    <Button variant="ghost" size="icon" onClick={() => setAEliminar(h)} title="Eliminar bloque">
                      <Trash2 />
                    </Button>
                  </div>
                ))}
                {porDia(d.value).length === 0 && <div className="text-xs text-muted-foreground">Sin clases</div>}
              </div>
            </div>
          ))}
        </div>
      )}

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Agregar bloque de horario">
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          {error && (
            <div role="alert" className="rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
          )}
          <CampoSelect
            label="Materia / Profesor"
            value={form.valores.aula_materia_id}
            onChange={(e) => form.setCampo('aula_materia_id', e.target.value)}
            onBlur={form.onBlur('aula_materia_id')}
            error={form.errorDe('aula_materia_id')}
            required
          >
            <option value="">Seleccione...</option>
            {asignaciones?.map((am) => (
              <option key={am.id} value={am.id}>{am.materia?.nombre} — {am.profesor?.nombre_completo}</option>
            ))}
          </CampoSelect>
          <CampoSelect
            label="Día"
            value={form.valores.dia}
            onChange={(e) => form.setCampo('dia', e.target.value)}
          >
            {DIAS.map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
          </CampoSelect>
          <div className="grid grid-cols-2 gap-3">
            <CampoTexto
              label="Hora inicio"
              type="time"
              value={form.valores.hora_inicio}
              onChange={(e) => form.setCampo('hora_inicio', e.target.value)}
              onBlur={form.onBlur('hora_inicio')}
              error={form.errorDe('hora_inicio')}
              required
            />
            <CampoTexto
              label="Hora fin"
              type="time"
              value={form.valores.hora_fin}
              onChange={(e) => form.setCampo('hora_fin', e.target.value)}
              onBlur={form.onBlur('hora_fin')}
              error={errorHoraFin}
              required
            />
          </div>
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => setModalOpen(false)} disabled={crear.isPending}>Cancelar</Button>
            <Button type="submit" disabled={crear.isPending}>
              {crear.isPending && <Loader2 className="animate-spin" />} Agregar
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={!!aEliminar}
        onClose={() => setAEliminar(null)}
        onConfirm={async () => {
          try {
            await eliminar.mutateAsync(aEliminar!.id);
            toast.success('Bloque eliminado');
          } catch {
            toast.error('No se pudo eliminar el bloque');
            throw new Error();
          }
        }}
        title="Eliminar bloque"
        description={`¿Eliminar ${aEliminar?.materia ?? 'este bloque'} del horario?`}
        confirmLabel="Eliminar"
        destructive
      />
    </div>
  );
};

const TabActividades = ({ aulaId }: { aulaId: number }) => {
  const { data: asignaciones } = useAulaMaterias(aulaId);
  const [aulaMateriaId, setAulaMateriaId] = useState<number | null>(null);

  const seleccion = aulaMateriaId ?? asignaciones?.[0]?.id ?? null;

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-3">
        <span className="text-sm font-medium leading-none">Materia / Profesor:</span>
        <Select
          value={seleccion ?? ''}
          onChange={(e) => setAulaMateriaId(Number(e.target.value))}
          className="w-[280px]"
        >
          {asignaciones?.map((am) => (
            <option key={am.id} value={am.id}>{am.materia?.nombre} — {am.profesor?.nombre_completo}</option>
          ))}
          {asignaciones && asignaciones.length === 0 && <option value="">Sin materias asignadas</option>}
        </Select>
      </div>

      {seleccion ? (
        <ListaActividades aulaMateriaId={seleccion} />
      ) : (
        <EmptyState
          icon={<Plus />}
          title="Sin materias asignadas"
          description='Primero asigna materias al aula en la pestaña "Materias y profesores".'
        />
      )}
    </div>
  );
};

const ListaActividades = ({ aulaMateriaId }: { aulaMateriaId: number }) => {
  const { data: actividades, isLoading } = useActividades(aulaMateriaId);
  const crear = useCrearActividad(aulaMateriaId);
  const eliminar = useEliminarActividad(aulaMateriaId);

  const [modalOpen, setModalOpen] = useState(false);
  const [error, setError] = useState('');
  const [aEliminar, setAEliminar] = useState<Actividad | null>(null);

  const form = useFormValidacion(
    { titulo: '', descripcion: '', fecha_cumplimiento: '', fecha_limite_notas: '', puntaje_maximo: '100' },
    {
      titulo: [requerido('El título es obligatorio'), longitud(3, 200, 'El título debe tener entre 3 y 200 caracteres')],
      descripcion: [longitud(0, 1000, 'La descripción no puede superar los 1000 caracteres')],
      fecha_cumplimiento: [requerido('La fecha de cumplimiento es obligatoria')],
      puntaje_maximo: [requerido('El puntaje máximo es obligatorio'), numeroMin(0.01, 'El puntaje debe ser mayor a 0')],
    }
  );

  const errorLimite = (() => {
    const { fecha_cumplimiento, fecha_limite_notas } = form.valores;
    if (fecha_cumplimiento && fecha_limite_notas && fecha_limite_notas < fecha_cumplimiento) {
      return 'El límite de notas no puede ser anterior al cumplimiento';
    }
    return form.errorDe('fecha_limite_notas');
  })();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    const { fecha_cumplimiento, fecha_limite_notas } = form.valores;
    if (fecha_cumplimiento && fecha_limite_notas && fecha_limite_notas < fecha_cumplimiento) {
      form.setErrorServidor('fecha_limite_notas', 'El límite de notas no puede ser anterior al cumplimiento');
      return;
    }
    if (!form.validarTodo()) return;
    try {
      await crear.mutateAsync({
        titulo: form.valores.titulo,
        descripcion: form.valores.descripcion || null,
        fecha_cumplimiento: form.valores.fecha_cumplimiento,
        fecha_limite_notas: form.valores.fecha_limite_notas || null,
        puntaje_maximo: Number(form.valores.puntaje_maximo),
      });
      form.reiniciar({ titulo: '', descripcion: '', fecha_cumplimiento: '', fecha_limite_notas: '', puntaje_maximo: '100' });
      setModalOpen(false);
      toast.success('Actividad creada');
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo crear la actividad.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const columns: DataTableColumn<Actividad>[] = [
    {
      key: 'titulo',
      header: 'Título',
      render: (a) => (
        <div>
          <div className="font-medium">{a.titulo}</div>
          {a.descripcion && <div className="text-xs text-muted-foreground">{a.descripcion}</div>}
        </div>
      ),
    },
    { key: 'cumplimiento', header: 'Cumplimiento', render: (a) => a.fecha_cumplimiento || '-' },
    { key: 'limite', header: 'Límite notas', render: (a) => a.fecha_limite_notas || '-' },
    { key: 'puntaje', header: 'Puntaje', render: (a) => a.puntaje_maximo },
    { key: 'notas', header: 'Notas', render: (a) => a.notas_count ?? 0 },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (a) => (
        <Button variant="ghost" size="icon" onClick={() => setAEliminar(a)} title="Eliminar actividad">
          <Trash2 />
        </Button>
      ),
    },
  ];

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <Button onClick={() => { form.reiniciar({ titulo: '', descripcion: '', fecha_cumplimiento: '', fecha_limite_notas: '', puntaje_maximo: '100' }); setModalOpen(true); }}>
          <Plus /> Nueva actividad
        </Button>
      </div>

      <DataTable
        data={actividades ?? []}
        columns={columns}
        rowKey={(a) => a.id}
        loading={isLoading}
        empty={<EmptyState icon={<Plus />} title="Sin actividades creadas" description="Crea la primera actividad." />}
      />

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Nueva actividad">
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          {error && (
            <div role="alert" className="rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
          )}
          <CampoTexto
            label="Título"
            value={form.valores.titulo}
            onChange={(e) => form.setCampo('titulo', e.target.value)}
            onBlur={form.onBlur('titulo')}
            error={form.errorDe('titulo')}
            placeholder="Ej. Primer parcial"
            required
          />
          <CampoTexto
            label="Descripción (opcional)"
            multilinea
            rows={2}
            value={form.valores.descripcion}
            onChange={(e) => form.setCampo('descripcion', e.target.value)}
            onBlur={form.onBlur('descripcion')}
            error={form.errorDe('descripcion')}
          />
          <div className="grid grid-cols-2 gap-3">
            <CampoTexto
              label="Fecha de cumplimiento"
              type="date"
              value={form.valores.fecha_cumplimiento}
              onChange={(e) => form.setCampo('fecha_cumplimiento', e.target.value)}
              onBlur={form.onBlur('fecha_cumplimiento')}
              error={form.errorDe('fecha_cumplimiento')}
              required
            />
            <CampoTexto
              label="Límite de carga de notas"
              type="date"
              value={form.valores.fecha_limite_notas}
              onChange={(e) => form.setCampo('fecha_limite_notas', e.target.value)}
              onBlur={form.onBlur('fecha_limite_notas')}
              error={errorLimite}
            />
          </div>
          <CampoTexto
            label="Puntaje máximo"
            type="number"
            step="0.01"
            min={0.01}
            value={form.valores.puntaje_maximo}
            onChange={(e) => form.setCampo('puntaje_maximo', e.target.value)}
            onBlur={form.onBlur('puntaje_maximo')}
            error={form.errorDe('puntaje_maximo')}
            required
          />
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => setModalOpen(false)} disabled={crear.isPending}>Cancelar</Button>
            <Button type="submit" disabled={crear.isPending}>
              {crear.isPending && <Loader2 className="animate-spin" />} Crear
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={!!aEliminar}
        onClose={() => setAEliminar(null)}
        onConfirm={async () => {
          try {
            await eliminar.mutateAsync(aEliminar!.id);
            toast.success('Actividad eliminada');
          } catch {
            toast.error('No se pudo eliminar la actividad');
            throw new Error();
          }
        }}
        title="Eliminar actividad"
        description={`¿Eliminar la actividad "${aEliminar?.titulo}"?`}
        confirmLabel="Eliminar"
        destructive
      />
    </div>
  );
};
