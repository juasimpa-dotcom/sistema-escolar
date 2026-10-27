import { useState } from 'react';
import {
  useActualizarMacrodistrito,
  useCrearMacrodistrito,
  useEliminarMacrodistrito,
  useMacrodistritos,
} from '../../api/catalogos';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { MacroBadge } from '@/components/ui/MacroBadge';
import { Buscador } from '@/components/ui/Buscador';
import { Paginacion } from '@/components/ui/Paginacion';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Button } from '@/components/ui/button';
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
import { requerido } from '@/lib/validacion';
import { toast } from 'sonner';
import { Loader2, Map, MoreVertical, Pencil, Plus, Trash2 } from 'lucide-react';
import type { Macrodistrito } from '../../types/gestion';

const TIPOS = [
  { value: 'urbano', label: 'Urbano' },
  { value: 'rural', label: 'Rural' },
];

const emptyForm = { codigo: '', nombre: '', tipo: 'urbano' };

export const MacrodistritosPage = () => {
  const [buscar, setBuscar] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editando, setEditando] = useState<Macrodistrito | null>(null);
  const [error, setError] = useState('');
  const [aEliminar, setAEliminar] = useState<Macrodistrito | null>(null);

  const { data, isLoading } = useMacrodistritos({ buscar, page });
  const crear = useCrearMacrodistrito();
  const actualizar = useActualizarMacrodistrito();
  const eliminar = useEliminarMacrodistrito();

  const form = useFormValidacion(emptyForm, {
    codigo: [requerido('El código es obligatorio')],
    nombre: [requerido('El nombre es obligatorio')],
    tipo: [requerido('El tipo es obligatorio')],
  });

  const cerrar = () => {
    setModalOpen(false);
    setEditando(null);
    setError('');
    form.reiniciar(emptyForm);
  };

  const abrirNuevo = () => {
    setEditando(null);
    form.reiniciar(emptyForm);
    setError('');
    setModalOpen(true);
  };

  const abrirEditar = (m: Macrodistrito) => {
    setEditando(m);
    form.reiniciar({ codigo: m.codigo, nombre: m.nombre, tipo: m.tipo });
    setError('');
    setModalOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;

    try {
      if (editando) {
        await actualizar.mutateAsync({ id: editando.id, payload: form.valores });
        toast.success('Macrodistrito actualizado');
      } else {
        await crear.mutateAsync(form.valores);
        toast.success('Macrodistrito creado');
      }
      cerrar();
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo guardar el macrodistrito.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const handleEliminar = async () => {
    if (!aEliminar) return;
    try {
      await eliminar.mutateAsync(aEliminar.id);
      toast.success('Macrodistrito eliminado');
    } catch (err: unknown) {
      toast.error(extractMessage(err, 'No se pudo eliminar el macrodistrito'));
    } finally {
      setAEliminar(null);
    }
  };

  const columns: DataTableColumn<Macrodistrito>[] = [
    {
      key: 'codigo',
      header: 'Código',
      render: (m) => <MacroBadge codigo={m.codigo} nombre={m.nombre} tipo={m.tipo} />,
    },
    { key: 'nombre', header: 'Nombre', render: (m) => <span className="font-medium">{m.nombre}</span> },
    {
      key: 'tipo',
      header: 'Tipo',
      render: (m) => (
        <StatusBadge label={m.tipo === 'rural' ? 'Rural' : 'Urbano'} tone={m.tipo === 'rural' ? 'success' : 'info'} />
      ),
    },
    {
      key: 'distritos',
      header: 'Distritos',
      align: 'right',
      render: (m) => m.distritos_municipales_count ?? 0,
    },
    {
      key: 'unidades',
      header: 'Unidades',
      align: 'right',
      render: (m) => m.unidades_educativas_count ?? 0,
    },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (m) => (
        <DropdownMenu>
          <DropdownMenuTrigger render={<Button variant="ghost" size="icon-sm" aria-label="Acciones" />}>
            <MoreVertical />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => abrirEditar(m)}>
              <Pencil /> Editar
            </DropdownMenuItem>
            <DropdownMenuItem variant="destructive" onClick={() => setAEliminar(m)}>
              <Trash2 /> Eliminar
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  const pending = crear.isPending || actualizar.isPending;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Macrodistritos"
        description="Catálogo de macrodistritos del municipio (corrección de asignaciones)."
        action={
          <Button onClick={abrirNuevo}>
            <Plus /> Nuevo macrodistrito
          </Button>
        }
      />

      <Buscador
        value={buscar}
        onChange={(v) => {
          setBuscar(v);
          setPage(1);
        }}
        placeholder="Buscar por nombre o código..."
      />

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(m) => m.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<Map />}
            title="No hay macrodistritos"
            description="Registra el primer macrodistrito del municipio."
            actionLabel="Nuevo macrodistrito"
            onAction={abrirNuevo}
          />
        }
      />

      {data && (
        <Paginacion page={data.current_page} lastPage={data.last_page} total={data.total} onChange={setPage} />
      )}

      <Modal open={modalOpen} onClose={cerrar} title={editando ? 'Editar macrodistrito' : 'Nuevo macrodistrito'}>
        {error && (
          <div className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
        )}
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          <div className="grid grid-cols-2 gap-3">
            <CampoTexto
              label="Código"
              required
              value={form.valores.codigo}
              onChange={(e) => form.setCampo('codigo', e.target.value.toUpperCase())}
              onBlur={form.onBlur('codigo')}
              error={form.errorDe('codigo')}
              hint="Ej. MD1"
            />
            <div className="space-y-1.5">
              <span className="text-sm font-medium leading-none">
                Tipo <span className="text-destructive">*</span>
              </span>
              <Select value={form.valores.tipo} onValueChange={(v) => form.setCampo('tipo', v ?? 'urbano')}>
                <SelectTrigger id="tipo-macrodistrito" className="w-full">
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
            <CampoTexto
              label="Nombre"
              required
              className="col-span-2"
              value={form.valores.nombre}
              onChange={(e) => form.setCampo('nombre', e.target.value)}
              onBlur={form.onBlur('nombre')}
              error={form.errorDe('nombre')}
            />
          </div>

          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={cerrar} disabled={pending}>
              Cancelar
            </Button>
            <Button type="submit" disabled={pending}>
              {pending && <Loader2 className="animate-spin" />}
              {editando ? 'Guardar cambios' : 'Crear macrodistrito'}
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={!!aEliminar}
        onClose={() => setAEliminar(null)}
        onConfirm={handleEliminar}
        title="Eliminar macrodistrito"
        description={`Se eliminará "${aEliminar?.codigo} ${aEliminar?.nombre}". Si está en uso por distritos o unidades no se podrá eliminar.`}
        confirmLabel="Eliminar"
        destructive
      />
    </div>
  );
};
