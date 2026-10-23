import { useState } from 'react';
import { useActualizarGestion, useCrearGestion, useEliminarGestion, useGestiones } from '../../api/academico';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Paginacion } from '@/components/ui/Paginacion';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/button';
import { CampoTexto } from '@/components/ui/CampoTexto';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractErrors, extractMessage } from '@/lib/errors';
import { numeroMax, numeroMin, requerido } from '@/lib/validacion';import {
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
import { toast } from 'sonner';
import { CalendarDays, Loader2, MoreVertical, Pencil, Plus, Trash2 } from 'lucide-react';
import type { Gestion } from '../../types/academico';

const anioActual = new Date().getFullYear();

const emptyForm = {
  anio: anioActual,
  fecha_inicio: '',
  fecha_fin: '',
  es_actual: false,
  estado: 'activa',
};

export const GestionesPage = () => {
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editando, setEditando] = useState<Gestion | null>(null);
  const [error, setError] = useState('');
  const [aEliminar, setAEliminar] = useState<Gestion | null>(null);

  const { data, isLoading } = useGestiones({ page });
  const crear = useCrearGestion();
  const actualizar = useActualizarGestion();
  const eliminar = useEliminarGestion();

  const form = useFormValidacion(emptyForm, {
    anio: [requerido('El año es obligatorio'), numeroMin(2000, 'El año debe ser mayor o igual a 2000'), numeroMax(2100, 'El año debe ser menor o igual a 2100')],
    fecha_inicio: [requerido('La fecha de inicio es obligatoria')],
    fecha_fin: [requerido('La fecha de fin es obligatoria')],
  });

  const errorFechaFin = (() => {
    const { fecha_inicio, fecha_fin } = form.valores;
    if (!form.tocados['fecha_fin'] && !form.errores['fecha_fin']) return undefined;
    if (fecha_inicio && fecha_fin && new Date(fecha_fin) <= new Date(fecha_inicio)) {
      return 'La fecha de fin debe ser posterior a la fecha de inicio';
    }
    return form.errorDe('fecha_fin');
  })();

  const abrirNuevo = () => {
    setEditando(null);
    form.reiniciar({ ...emptyForm, anio: anioActual });
    setError('');
    setModalOpen(true);
  };

  const abrirEditar = (g: Gestion) => {
    setEditando(g);
    form.reiniciar({
      anio: g.anio,
      fecha_inicio: g.fecha_inicio || '',
      fecha_fin: g.fecha_fin || '',
      es_actual: g.es_actual,
      estado: g.estado,
    });
    setError('');
    setModalOpen(true);
  };

  const cerrar = () => {
    setModalOpen(false);
    setError('');
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    const { fecha_inicio, fecha_fin } = form.valores;
    if (fecha_inicio && fecha_fin && new Date(fecha_fin) <= new Date(fecha_inicio)) {
      form.setErrorServidor('fecha_fin', 'La fecha de fin debe ser posterior a la fecha de inicio');
      return;
    }
    if (!form.validarTodo()) return;
    const payload = {
      ...form.valores,
      fecha_inicio: fecha_inicio || null,
      fecha_fin: fecha_fin || null,
    };
    try {
      if (editando) {
        await actualizar.mutateAsync({ id: editando.id, payload });
        toast.success('Gestión actualizada');
      } else {
        await crear.mutateAsync(payload);
        toast.success('Gestión creada');
      }
      setModalOpen(false);
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo guardar la gestión.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const handleEliminar = async () => {
    if (!aEliminar) return;
    try {
      await eliminar.mutateAsync(aEliminar.id);
      toast.success('Gestión eliminada');
    } catch {
      toast.error('No se pudo eliminar la gestión');
    } finally {
      setAEliminar(null);
    }
  };

  const columns: DataTableColumn<Gestion>[] = [
    { key: 'anio', header: 'Año', render: (g) => <span className="font-semibold">{g.anio}</span> },
    { key: 'inicio', header: 'Inicio', render: (g) => g.fecha_inicio || <span className="text-muted-foreground">—</span> },
    { key: 'fin', header: 'Fin', render: (g) => g.fecha_fin || <span className="text-muted-foreground">—</span> },
    {
      key: 'actual',
      header: 'Actual',
      render: (g) =>
        g.es_actual ? (
          <StatusBadge estado="activo" label="Actual" />
        ) : (
          <span className="text-muted-foreground">—</span>
        ),
    },
    { key: 'aulas', header: 'Aulas', render: (g) => g.aulas_count ?? 0 },
    { key: 'estado', header: 'Estado', render: (g) => <StatusBadge estado={g.estado} /> },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (g) => (
        <DropdownMenu>
          <DropdownMenuTrigger render={<Button variant="ghost" size="icon-sm" aria-label="Acciones" />}>
            <MoreVertical />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => abrirEditar(g)}>
              <Pencil /> Editar
            </DropdownMenuItem>
            <DropdownMenuItem variant="destructive" onClick={() => setAEliminar(g)}>
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
        title="Gestiones"
        description="Administrá los años lectivos y su estado."
        action={
          <Button onClick={abrirNuevo}>
            <Plus /> Nueva gestión
          </Button>
        }
      />

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(g) => g.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<CalendarDays />}
            title="No hay gestiones"
            description="Creá una gestión para comenzar a organizar aulas y materias."
            actionLabel="Nueva gestión"
            onAction={abrirNuevo}
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

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editando ? 'Editar gestión' : 'Nueva gestión'}
      >
        {error && (
          <div className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
        )}
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          <CampoTexto
            label="Año"
            type="number"
            required
            inputMode="numeric"
            min={2000}
            max={2100}
            value={form.valores.anio}
            onChange={(e) => form.setCampo('anio', e.target.value === '' ? '' : Number(e.target.value))}
            onBlur={form.onBlur('anio')}
            error={form.errorDe('anio')}
          />
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <CampoTexto
              label="Fecha inicio"
              type="date"
              required
              value={form.valores.fecha_inicio}
              onChange={(e) => form.setCampo('fecha_inicio', e.target.value)}
              onBlur={form.onBlur('fecha_inicio')}
              error={form.errorDe('fecha_inicio')}
            />
            <CampoTexto
              label="Fecha fin"
              type="date"
              required
              value={form.valores.fecha_fin}
              onChange={(e) => form.setCampo('fecha_fin', e.target.value)}
              onBlur={form.onBlur('fecha_fin')}
              error={errorFechaFin}
            />
          </div>
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={form.valores.es_actual}
                onChange={(e) => form.setCampo('es_actual', e.target.checked)}
                className="size-4 rounded border-input accent-primary"
              />
              Gestión actual
            </label>
            <div className="flex-1">
              <Select value={form.valores.estado} onValueChange={(v) => form.setCampo('estado', v ?? '')}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="activa">Activa</SelectItem>
                  <SelectItem value="cerrada">Cerrada</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={cerrar} disabled={pending}>
              Cancelar
            </Button>
            <Button type="submit" disabled={pending}>
              {pending && <Loader2 className="animate-spin" />}
              {editando ? 'Guardar' : 'Crear'}
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={!!aEliminar}
        onClose={() => setAEliminar(null)}
        title={`¿Eliminar la gestión ${aEliminar?.anio ?? ''}?`}
        description="Esta acción no se puede deshacer."
        confirmLabel="Eliminar"
        destructive
        onConfirm={handleEliminar}
      />
    </div>
  );
};
