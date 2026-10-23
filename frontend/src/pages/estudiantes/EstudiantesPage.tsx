import { useMemo, useState } from 'react';
import { useBajaEstudiante, useCrearEstudiante, useEstudiantes, usePersonas } from '../../api/gestion';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { Buscador } from '@/components/ui/Buscador';
import { Paginacion } from '@/components/ui/Paginacion';
import { BajaModal } from '@/components/ui/BajaModal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CampoTexto } from '@/components/ui/CampoTexto';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { toast } from 'sonner';
import { GraduationCap, Loader2, MoreVertical, Plus, UserX } from 'lucide-react';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { ci, email, fechaNoFutura, nombre, requerido, telefono } from '@/lib/validacion';
import { extractErrors, extractMessage } from '@/lib/errors';
import type { Estudiante } from '../../types/gestion';

const PARENTESCOS = [
  { value: 'padre', label: 'Padre' },
  { value: 'madre', label: 'Madre' },
  { value: 'tutor', label: 'Tutor' },
  { value: 'otro', label: 'Otro' },
];

const emptyForm = {
  estudiante: { ci: '', nombres: '', apellidos: '', fecha_nacimiento: '', email: '', telefono: '' },
  padre_existente_id: '' as string | number,
  padre_nuevo: { ci: '', nombres: '', apellidos: '', email: '', telefono: '' },
  parentesco: 'padre',
};

export const EstudiantesPage = () => {
  const [buscar, setBuscar] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [modoPadre, setModoPadre] = useState<'nuevo' | 'existente'>('nuevo');
  const [busquedaPadre, setBusquedaPadre] = useState('');
  const [error, setError] = useState('');
  const [aBaja, setABaja] = useState<Estudiante | null>(null);

  const { data, isLoading } = useEstudiantes({ buscar, page });
  const { data: padresData } = usePersonas({ rol: 'padre', buscar: busquedaPadre, por_pagina: 20 });
  const crear = useCrearEstudiante();
  const baja = useBajaEstudiante();

  const padres = useMemo(() => padresData?.data ?? [], [padresData]);

  const esquema = useMemo(
    () => ({
      'estudiante.ci': [requerido('El CI es obligatorio'), ci()],
      'estudiante.nombres': [requerido('Los nombres son obligatorios'), nombre()],
      'estudiante.apellidos': [requerido('Los apellidos son obligatorios'), nombre()],
      'estudiante.fecha_nacimiento': [requerido('La fecha de nacimiento es obligatoria'), fechaNoFutura()],
      'estudiante.email': [email()],
      'estudiante.telefono': [telefono()],
      ...(modoPadre === 'nuevo'
        ? {
            'padre_nuevo.ci': [requerido('El CI del padre es obligatorio'), ci()],
            'padre_nuevo.nombres': [requerido('Los nombres del padre son obligatorios'), nombre()],
            'padre_nuevo.apellidos': [requerido('Los apellidos del padre son obligatorios'), nombre()],
            'padre_nuevo.email': [email()],
            'padre_nuevo.telefono': [telefono()],
          }
        : {
            padre_existente_id: [requerido('Seleccioná un padre existente')],
          }),
    }),
    [modoPadre]
  );

  const form = useFormValidacion(emptyForm, esquema);

  const esNuevoPadre = modoPadre === 'nuevo';

  const cerrar = () => {
    setModalOpen(false);
    setError('');
    form.reiniciar();
    setModoPadre('nuevo');
    setBusquedaPadre('');
  };

  const handleCrear = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;

    const payload: Record<string, unknown> = {
      estudiante: form.valores.estudiante,
      parentesco: form.valores.parentesco,
    };
    if (modoPadre === 'existente' && form.valores.padre_existente_id) {
      payload.padre_existente_id = Number(form.valores.padre_existente_id);
    } else {
      payload.padre_nuevo = form.valores.padre_nuevo;
    }

    try {
      await crear.mutateAsync(payload);
      toast.success('Estudiante creado correctamente');
      cerrar();
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo crear el estudiante.');
      const entradas = Object.entries(errores);
      if (entradas.length > 0) {
        for (const [campo, mensaje] of entradas) {
          form.setErrorServidor(campo, mensaje);
        }
        setError(msg);
      } else {
        setError(msg);
      }
      toast.error(msg);
    }
  };

  const columns: DataTableColumn<Estudiante>[] = [
    { key: 'ci', header: 'CI', render: (es) => <span className="font-mono text-sm">{es.ci}</span> },
    { key: 'nombre', header: 'Nombre', render: (es) => <span className="font-medium">{es.nombre_completo}</span> },
    {
      key: 'nacimiento',
      header: 'Nacimiento',
      render: (es) => es.fecha_nacimiento || <span className="text-muted-foreground">—</span>,
    },
    {
      key: 'padres',
      header: 'Padres/Tutores',
      render: (es) =>
        es.padres && es.padres.length > 0 ? (
          <div className="space-y-0.5">
            {es.padres.map((p) => (
              <div key={p.id} className="text-xs">
                {p.nombre_completo} <span className="text-muted-foreground">({p.parentesco})</span>
              </div>
            ))}
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">Sin registrar</span>
        ),
    },
    { key: 'estado', header: 'Estado', render: (es) => <StatusBadge estado={es.estado} /> },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (es) => (
        <DropdownMenu>
          <DropdownMenuTrigger render={<Button variant="ghost" size="icon-sm" aria-label="Acciones" />}>
            <MoreVertical />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              variant="destructive"
              disabled={es.estado !== 'activo'}
              onClick={() => setABaja(es)}
            >
              <UserX /> Dar de baja
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Estudiantes"
        description="Registro de estudiantes, su estado y sus padres o tutores."
        action={
          <Button onClick={() => setModalOpen(true)}>
            <Plus /> Nuevo estudiante
          </Button>
        }
      />

      <Buscador
        value={buscar}
        onChange={(v) => {
          setBuscar(v);
          setPage(1);
        }}
        placeholder="Buscar por nombre o CI..."
      />

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(es) => es.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<GraduationCap />}
            title="No hay estudiantes registrados"
            description="Registrá al primer estudiante e inscribí a sus padres o tutores."
            actionLabel="Nuevo estudiante"
            onAction={() => setModalOpen(true)}
          />
        }
      />

      {data && (
        <Paginacion
          page={data.current_page}
          lastPage={data.last_page}
          total={data.total}
          onChange={setPage}
        />
      )}

      <Dialog open={modalOpen} onOpenChange={(v) => !v && cerrar()}>
        <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>Nuevo estudiante</DialogTitle>
            <DialogDescription>Ingresá los datos del estudiante y su padre o tutor.</DialogDescription>
          </DialogHeader>
          {error && (
            <div className="rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
          )}
          <form onSubmit={handleCrear} className="space-y-4" noValidate>
            <div className="rounded-xl border border-border/60 p-3">
              <h3 className="mb-3 text-sm font-semibold">Datos del estudiante</h3>
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <CampoTexto
                  label="CI"
                  required
                  inputMode="numeric"
                  value={form.valores.estudiante.ci}
                  onChange={(e) => form.setCampo('estudiante.ci', e.target.value)}
                  onBlur={form.onBlur('estudiante.ci')}
                  error={form.errorDe('estudiante.ci')}
                  valido={!!form.valores.estudiante.ci && !form.errores['estudiante.ci']}
                />
                <CampoTexto
                  label="Fecha de nacimiento"
                  type="date"
                  required
                  value={form.valores.estudiante.fecha_nacimiento}
                  onChange={(e) => form.setCampo('estudiante.fecha_nacimiento', e.target.value)}
                  onBlur={form.onBlur('estudiante.fecha_nacimiento')}
                  error={form.errorDe('estudiante.fecha_nacimiento')}
                />
                <CampoTexto
                  label="Nombres"
                  required
                  autoComplete="given-name"
                  value={form.valores.estudiante.nombres}
                  onChange={(e) => form.setCampo('estudiante.nombres', e.target.value)}
                  onBlur={form.onBlur('estudiante.nombres')}
                  error={form.errorDe('estudiante.nombres')}
                />
                <CampoTexto
                  label="Apellidos"
                  required
                  autoComplete="family-name"
                  value={form.valores.estudiante.apellidos}
                  onChange={(e) => form.setCampo('estudiante.apellidos', e.target.value)}
                  onBlur={form.onBlur('estudiante.apellidos')}
                  error={form.errorDe('estudiante.apellidos')}
                />
                <CampoTexto
                  label="Correo electrónico"
                  type="email"
                  inputMode="email"
                  autoComplete="email"
                  value={form.valores.estudiante.email}
                  onChange={(e) => form.setCampo('estudiante.email', e.target.value)}
                  onBlur={form.onBlur('estudiante.email')}
                  error={form.errorDe('estudiante.email')}
                  hint="Opcional"
                />
                <CampoTexto
                  label="Teléfono"
                  type="tel"
                  inputMode="tel"
                  autoComplete="tel"
                  value={form.valores.estudiante.telefono}
                  onChange={(e) => form.setCampo('estudiante.telefono', e.target.value)}
                  onBlur={form.onBlur('estudiante.telefono')}
                  error={form.errorDe('estudiante.telefono')}
                  hint="Opcional"
                />
              </div>
            </div>

            <div className="rounded-xl border border-border/60 p-3">
              <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 className="text-sm font-semibold">Padre / Tutor</h3>
                <Tabs
                  value={modoPadre}
                  onValueChange={(v) => setModoPadre(v as 'nuevo' | 'existente')}
                >
                  <TabsList>
                    <TabsTrigger value="nuevo">Nuevo</TabsTrigger>
                    <TabsTrigger value="existente">Existente</TabsTrigger>
                  </TabsList>
                </Tabs>
              </div>

              <div className="mb-3 space-y-1.5">
                <Label htmlFor="parentesco">Parentesco</Label>
                <Select value={form.valores.parentesco} onValueChange={(v) => form.setCampo('parentesco', v ?? '')}>
                  <SelectTrigger id="parentesco" className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {PARENTESCOS.map((p) => (
                      <SelectItem key={p.value} value={p.value}>
                        {p.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {esNuevoPadre ? (
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <CampoTexto
                    label="CI"
                    required
                    inputMode="numeric"
                    value={form.valores.padre_nuevo.ci}
                    onChange={(e) => form.setCampo('padre_nuevo.ci', e.target.value)}
                    onBlur={form.onBlur('padre_nuevo.ci')}
                    error={form.errorDe('padre_nuevo.ci')}
                  />
                  <CampoTexto
                    label="Teléfono"
                    type="tel"
                    inputMode="tel"
                    value={form.valores.padre_nuevo.telefono}
                    onChange={(e) => form.setCampo('padre_nuevo.telefono', e.target.value)}
                    onBlur={form.onBlur('padre_nuevo.telefono')}
                    error={form.errorDe('padre_nuevo.telefono')}
                    hint="Opcional"
                  />
                  <CampoTexto
                    label="Nombres"
                    required
                    autoComplete="given-name"
                    value={form.valores.padre_nuevo.nombres}
                    onChange={(e) => form.setCampo('padre_nuevo.nombres', e.target.value)}
                    onBlur={form.onBlur('padre_nuevo.nombres')}
                    error={form.errorDe('padre_nuevo.nombres')}
                  />
                  <CampoTexto
                    label="Apellidos"
                    required
                    autoComplete="family-name"
                    value={form.valores.padre_nuevo.apellidos}
                    onChange={(e) => form.setCampo('padre_nuevo.apellidos', e.target.value)}
                    onBlur={form.onBlur('padre_nuevo.apellidos')}
                    error={form.errorDe('padre_nuevo.apellidos')}
                  />
                  <CampoTexto
                    label="Correo electrónico"
                    type="email"
                    inputMode="email"
                    autoComplete="email"
                    value={form.valores.padre_nuevo.email}
                    onChange={(e) => form.setCampo('padre_nuevo.email', e.target.value)}
                    onBlur={form.onBlur('padre_nuevo.email')}
                    error={form.errorDe('padre_nuevo.email')}
                    hint="Opcional"
                  />
                </div>
              ) : (
                <div className="space-y-3">
                  <div className="space-y-1.5">
                    <Label htmlFor="p-buscar">Buscar padre existente</Label>
                    <Input
                      id="p-buscar"
                      value={busquedaPadre}
                      onChange={(e) => setBusquedaPadre(e.target.value)}
                      placeholder="Nombre o CI..."
                    />
                  </div>
                  <div className="space-y-1.5">
                    <Label htmlFor="padre-existente">Padre existente</Label>
                    <Select
                      value={form.valores.padre_existente_id ? String(form.valores.padre_existente_id) : ''}
                      onValueChange={(v) => form.setCampo('padre_existente_id', v ?? '')}
                    >
                      <SelectTrigger id="padre-existente" className="w-full" aria-invalid={!!form.errorDe('padre_existente_id')}>
                        <SelectValue placeholder="Seleccione un padre..." />
                      </SelectTrigger>
                      <SelectContent>
                        {padres.map((p) => (
                          <SelectItem key={p.id} value={String(p.id)}>
                            {p.nombre_completo} (CI {p.ci})
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {form.errorDe('padre_existente_id') && (
                      <p role="alert" className="text-xs font-medium text-destructive">
                        {form.errorDe('padre_existente_id')}
                      </p>
                    )}
                  </div>
                </div>
              )}
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={cerrar} disabled={crear.isPending}>
                Cancelar
              </Button>
              <Button type="submit" disabled={crear.isPending}>
                {crear.isPending && <Loader2 className="animate-spin" />}
                Crear estudiante
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <BajaModal
        open={!!aBaja}
        onClose={() => setABaja(null)}
        nombre={aBaja?.nombre_completo || ''}
        onConfirm={(motivo, fecha) => baja.mutateAsync({ id: aBaja!.id, payload: { motivo, fecha } })}
      />
    </div>
  );
};
