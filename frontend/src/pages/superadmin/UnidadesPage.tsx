import { useState } from 'react';
import { useActualizarUnidad, useCrearUnidad, useUnidades, useBajaUnidad } from '../../api/gestion';
import { useCatalogoDistritos, useCatalogoMacrodistritos } from '../../api/catalogos';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { MacroBadge } from '@/components/ui/MacroBadge';
import { Buscador } from '@/components/ui/Buscador';
import { Paginacion } from '@/components/ui/Paginacion';
import { Modal } from '@/components/ui/Modal';
import { BajaModal } from '@/components/ui/BajaModal';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CampoTexto } from '@/components/ui/CampoTexto';
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
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractErrors, extractMessage } from '@/lib/errors';
import { ci, email, nombre, requerido, telefono } from '@/lib/validacion';
import { toast } from 'sonner';
import { Building2, Loader2, MoreVertical, Pencil, Plus } from 'lucide-react';
import type { TipoUnidad, Unidad } from '../../types/gestion';

const TIPOS = [
  { value: 'fiscal', label: 'Fiscal' },
  { value: 'convenio', label: 'Convenio' },
  { value: 'privada', label: 'Privada' },
];

const emptyForm = {
  nombre: '',
  codigo: '',
  direccion: '',
  zona: '',
  tipo: 'fiscal',
  telefono: '',
  macrodistrito_id: '',
  distrito_municipal_id: '',
  director: { ci: '', nombres: '', apellidos: '', email: '', telefono: '' },
};

export const UnidadesPage = () => {
  const [buscar, setBuscar] = useState('');
  const [macroFiltro, setMacroFiltro] = useState('');
  const [distritoFiltro, setDistritoFiltro] = useState('');
  const [tipoFiltro, setTipoFiltro] = useState<TipoUnidad | ''>('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editando, setEditando] = useState<Unidad | null>(null);
  const [error, setError] = useState('');
  const [aBaja, setABaja] = useState<Unidad | null>(null);

  const filtros = {
    buscar,
    macrodistrito_id: macroFiltro ? Number(macroFiltro) : '',
    distrito_municipal_id: distritoFiltro ? Number(distritoFiltro) : '',
    tipo: tipoFiltro,
    page,
  };

  const { data, isLoading } = useUnidades(filtros);
  const { data: macros } = useCatalogoMacrodistritos();
  const { data: distritosFiltro } = useCatalogoDistritos(macroFiltro);
  const crear = useCrearUnidad();
  const actualizar = useActualizarUnidad();
  const baja = useBajaUnidad();

  const form = useFormValidacion(emptyForm, {
    nombre: [requerido('El nombre es obligatorio')],
    codigo: [requerido('El código es obligatorio')],
    telefono: [telefono()],
    tipo: [requerido('El tipo es obligatorio')],
    'director.ci': editando ? [] : [requerido('El CI del director es obligatorio'), ci()],
    'director.nombres': editando ? [] : [requerido('Los nombres del director son obligatorios'), nombre()],
    'director.apellidos': editando ? [] : [requerido('Los apellidos del director son obligatorios'), nombre()],
    'director.email': editando ? [] : [email()],
    'director.telefono': editando ? [] : [telefono()],
  });

  const cerrar = () => {
    setModalOpen(false);
    setEditando(null);
    setError('');
    form.reiniciar();
  };

  const abrirNuevo = () => {
    setEditando(null);
    form.reiniciar(emptyForm);
    setError('');
    setModalOpen(true);
  };

  const abrirEditar = (u: Unidad) => {
    setEditando(u);
    form.reiniciar({
      nombre: u.nombre,
      codigo: u.codigo,
      direccion: u.direccion ?? '',
      zona: u.zona ?? '',
      tipo: u.tipo ?? 'fiscal',
      telefono: u.telefono ?? '',
      macrodistrito_id: u.macrodistrito_id ? String(u.macrodistrito_id) : '',
      distrito_municipal_id: u.distrito_municipal_id ? String(u.distrito_municipal_id) : '',
      director: { ci: '', nombres: '', apellidos: '', email: '', telefono: '' },
    });
    setError('');
    setModalOpen(true);
  };

  const macroSeleccionado = form.valores.macrodistrito_id;
  const { data: distritos } = useCatalogoDistritos(macroSeleccionado);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;

    const v = form.valores;
    const payload = {
      nombre: v.nombre,
      codigo: v.codigo,
      direccion: v.direccion || null,
      zona: v.zona || null,
      tipo: v.tipo,
      telefono: v.telefono || null,
      macrodistrito_id: v.macrodistrito_id ? Number(v.macrodistrito_id) : null,
      distrito_municipal_id: v.distrito_municipal_id ? Number(v.distrito_municipal_id) : null,
      ...(editando ? {} : { director: v.director }),
    };

    try {
      if (editando) {
        await actualizar.mutateAsync({ id: editando.id, payload });
        toast.success('Unidad educativa actualizada');
      } else {
        await crear.mutateAsync(payload);
        toast.success('Unidad educativa creada');
      }
      cerrar();
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo guardar la unidad.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const columns: DataTableColumn<Unidad>[] = [
    { key: 'codigo', header: 'Código', render: (u) => <span className="font-mono">{u.codigo}</span> },
    { key: 'nombre', header: 'Nombre', render: (u) => <span className="font-medium">{u.nombre}</span> },
    {
      key: 'ubicacion',
      header: 'Ubicación',
      render: (u) => (
        <div className="space-y-1">
          <MacroBadge
            codigo={u.macrodistrito?.codigo}
            nombre={u.macrodistrito?.nombre}
            tipo={u.macrodistrito?.tipo}
          />
          <div className="text-xs text-muted-foreground">
            {u.distrito ? `Distrito ${u.distrito.numero}` : 'Sin distrito'}
            {u.zona ? ` · ${u.zona}` : ''}
            {u.direccion ? ` · ${u.direccion}` : ''}
          </div>
        </div>
      ),
    },
    {
      key: 'tipo',
      header: 'Tipo',
      render: (u) => (
        <StatusBadge
          label={TIPOS.find((t) => t.value === u.tipo)?.label ?? u.tipo}
          tone={u.tipo === 'fiscal' ? 'info' : u.tipo === 'convenio' ? 'warning' : 'primary'}
        />
      ),
    },
    {
      key: 'director',
      header: 'Director',
      render: (u) =>
        u.director ? (
          <div>
            <div>{u.director.nombre_completo}</div>
            <div className="text-xs text-muted-foreground">CI {u.director.ci}</div>
          </div>
        ) : (
          <span className="text-muted-foreground">Sin director</span>
        ),
    },
    { key: 'estado', header: 'Estado', render: (u) => <StatusBadge estado={u.estado} /> },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (u) => (
        <DropdownMenu>
          <DropdownMenuTrigger render={<Button variant="ghost" size="icon-sm" aria-label="Acciones" />}>
            <MoreVertical />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => abrirEditar(u)}>
              <Pencil /> Editar
            </DropdownMenuItem>
            {u.estado === 'activa' && (
              <DropdownMenuItem variant="destructive" onClick={() => setABaja(u)}>
                Dar de baja
              </DropdownMenuItem>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  const pending = crear.isPending || actualizar.isPending;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Unidades Educativas"
        description="Administra las unidades educativas del sistema."
        action={
          <Button onClick={abrirNuevo}>
            <Plus /> Nueva unidad
          </Button>
        }
      />

      <Card className="rounded-xl border-border/60 p-4 shadow-sm">
        <div className="flex flex-wrap items-center gap-3">
          <div className="flex-1">
            <Buscador
              value={buscar}
              onChange={(v) => {
                setBuscar(v);
                setPage(1);
              }}
              placeholder="Buscar por nombre o código..."
            />
          </div>

          <Select
            value={macroFiltro || 'all'}
            onValueChange={(v) => {
              const valor = !v || v === 'all' ? '' : v;
              setMacroFiltro(valor);
              setDistritoFiltro('');
              setPage(1);
            }}
          >
            <SelectTrigger className="w-[210px]">
              <SelectValue placeholder="Todos los macrodistritos" />
            </SelectTrigger>
            <SelectContent className="rounded-xl">
              <SelectItem value="all">Todos los macrodistritos</SelectItem>
              {macros?.map((m) => (
                <SelectItem key={m.id} value={String(m.id)}>
                  {m.codigo} · {m.nombre}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select
            value={distritoFiltro || 'all'}
            disabled={!macroFiltro}
            onValueChange={(v) => {
              setDistritoFiltro(!v || v === 'all' ? '' : v);
              setPage(1);
            }}
          >
            <SelectTrigger className="w-[190px]">
              <SelectValue placeholder="Todos los distritos" />
            </SelectTrigger>
            <SelectContent className="rounded-xl">
              <SelectItem value="all">Todos los distritos</SelectItem>
              {distritosFiltro?.map((d) => (
                <SelectItem key={d.id} value={String(d.id)}>
                  Distrito {d.numero}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select
            value={tipoFiltro || 'all'}
            onValueChange={(v) => {
              setTipoFiltro(!v || v === 'all' ? '' : (v as TipoUnidad));
              setPage(1);
            }}
          >
            <SelectTrigger className="w-[160px]">
              <SelectValue placeholder="Todos los tipos" />
            </SelectTrigger>
            <SelectContent className="rounded-xl">
              <SelectItem value="all">Todos los tipos</SelectItem>
              {TIPOS.map((t) => (
                <SelectItem key={t.value} value={t.value}>
                  {t.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </Card>

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(u) => u.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<Building2 />}
            title="No hay unidades"
            description="Registra la primera unidad educativa para comenzar."
            actionLabel="Nueva unidad"
            onAction={abrirNuevo}
          />
        }
      />

      {data && (
        <Paginacion page={data.current_page} lastPage={data.last_page} total={data.total} onChange={setPage} />
      )}

      <Modal
        open={modalOpen}
        onClose={cerrar}
        title={editando ? 'Editar unidad educativa' : 'Nueva unidad educativa'}
      >
        {error && (
          <div className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
        )}
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          <div className="grid grid-cols-2 gap-3">
            <CampoTexto
              label="Nombre"
              required
              className="col-span-2"
              value={form.valores.nombre}
              onChange={(e) => form.setCampo('nombre', e.target.value)}
              onBlur={form.onBlur('nombre')}
              error={form.errorDe('nombre')}
            />
            <CampoTexto
              label="Código"
              required
              value={form.valores.codigo}
              onChange={(e) => form.setCampo('codigo', e.target.value)}
              onBlur={form.onBlur('codigo')}
              error={form.errorDe('codigo')}
            />
            <CampoTexto
              label="Teléfono"
              type="tel"
              inputMode="tel"
              value={form.valores.telefono}
              onChange={(e) => form.setCampo('telefono', e.target.value)}
              onBlur={form.onBlur('telefono')}
              error={form.errorDe('telefono')}
              hint="Opcional"
            />
            <CampoTexto
              label="Dirección"
              className="col-span-2"
              value={form.valores.direccion}
              onChange={(e) => form.setCampo('direccion', e.target.value)}
            />
            <CampoTexto
              label="Zona"
              value={form.valores.zona}
              onChange={(e) => form.setCampo('zona', e.target.value)}
              hint="Opcional"
            />

            <div className="space-y-1.5">
              <span className="text-sm font-medium leading-none">
                Tipo <span className="text-destructive">*</span>
              </span>
              <Select
                value={form.valores.tipo}
                onValueChange={(v) => form.setCampo('tipo', v ?? 'fiscal')}
              >
                <SelectTrigger id="tipo-unidad" className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent className="rounded-xl">
                  {TIPOS.map((t) => (
                    <SelectItem key={t.value} value={t.value}>
                      {t.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5 col-span-2">
              <span className="text-sm font-medium leading-none">Macrodistrito</span>
              <Select
                value={form.valores.macrodistrito_id || 'none'}
                onValueChange={(v) => {
                  const valor = !v || v === 'none' ? '' : v;
                  form.setCampo('macrodistrito_id', valor);
                  form.setCampo('distrito_municipal_id', '');
                }}
              >
                <SelectTrigger id="macrodistrito-unidad" className="w-full">
                  <SelectValue placeholder="Selecciona un macrodistrito" />
                </SelectTrigger>
                <SelectContent className="rounded-xl">
                  <SelectItem value="none">Sin macrodistrito</SelectItem>
                  {macros?.map((m) => (
                    <SelectItem key={m.id} value={String(m.id)}>
                      {m.codigo} · {m.nombre} ({m.tipo})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5 col-span-2">
              <span className="text-sm font-medium leading-none">Distrito municipal</span>
              <Select
                value={form.valores.distrito_municipal_id || 'none'}
                disabled={!form.valores.macrodistrito_id}
                onValueChange={(v) =>
                  form.setCampo('distrito_municipal_id', !v || v === 'none' ? '' : v)
                }
              >
                <SelectTrigger id="distrito-unidad" className="w-full">
                  <SelectValue
                    placeholder={
                      form.valores.macrodistrito_id
                        ? 'Selecciona un distrito'
                        : 'Elige primero un macrodistrito'
                    }
                  />
                </SelectTrigger>
                <SelectContent className="rounded-xl">
                  <SelectItem value="none">Sin distrito</SelectItem>
                  {distritos?.map((d) => (
                    <SelectItem key={d.id} value={String(d.id)}>
                      Distrito {d.numero}
                      {d.nombre ? ` · ${d.nombre}` : ''}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {!editando && (
            <div className="space-y-3 rounded-lg border p-3">
              <h3 className="text-sm font-semibold">Director de la unidad</h3>
              <div className="grid grid-cols-2 gap-3">
                <CampoTexto
                  label="CI"
                  required
                  inputMode="numeric"
                  value={form.valores.director.ci}
                  onChange={(e) => form.setCampo('director.ci', e.target.value.replace(/\D/g, ''))}
                  onBlur={form.onBlur('director.ci')}
                  error={form.errorDe('director.ci')}
                />
                <CampoTexto
                  label="Teléfono"
                  type="tel"
                  inputMode="tel"
                  value={form.valores.director.telefono}
                  onChange={(e) => form.setCampo('director.telefono', e.target.value)}
                  onBlur={form.onBlur('director.telefono')}
                  error={form.errorDe('director.telefono')}
                  hint="Opcional"
                />
                <CampoTexto
                  label="Nombres"
                  required
                  autoComplete="given-name"
                  value={form.valores.director.nombres}
                  onChange={(e) => form.setCampo('director.nombres', e.target.value)}
                  onBlur={form.onBlur('director.nombres')}
                  error={form.errorDe('director.nombres')}
                />
                <CampoTexto
                  label="Apellidos"
                  required
                  autoComplete="family-name"
                  value={form.valores.director.apellidos}
                  onChange={(e) => form.setCampo('director.apellidos', e.target.value)}
                  onBlur={form.onBlur('director.apellidos')}
                  error={form.errorDe('director.apellidos')}
                />
                <CampoTexto
                  label="Correo electrónico"
                  type="email"
                  inputMode="email"
                  autoComplete="email"
                  className="col-span-2"
                  value={form.valores.director.email}
                  onChange={(e) => form.setCampo('director.email', e.target.value)}
                  onBlur={form.onBlur('director.email')}
                  error={form.errorDe('director.email')}
                  hint="Opcional"
                />
              </div>
              <p className="text-xs text-muted-foreground">
                Credenciales iniciales: usuario y contraseña = CI del director.
              </p>
            </div>
          )}

          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={cerrar} disabled={pending}>
              Cancelar
            </Button>
            <Button type="submit" disabled={pending}>
              {pending && <Loader2 className="animate-spin" />}
              {editando ? 'Guardar cambios' : 'Crear unidad'}
            </Button>
          </div>
        </form>
      </Modal>

      <BajaModal
        open={!!aBaja}
        onClose={() => setABaja(null)}
        nombre={aBaja?.nombre || ''}
        titulo="Dar de baja unidad educativa"
        onConfirm={async (motivo, fecha) => {
          await baja.mutateAsync({ id: aBaja!.id, payload: { motivo, fecha } });
          toast.success('Unidad dada de baja');
        }}
      />
    </div>
  );
};
