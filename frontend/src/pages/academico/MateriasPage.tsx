import { useState } from 'react';
import { useActualizarMateria, useCrearMateria, useEliminarMateria, useMaterias } from '../../api/academico';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Buscador } from '@/components/ui/Buscador';
import { Paginacion } from '@/components/ui/Paginacion';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { CampoTexto } from '@/components/ui/CampoTexto';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractErrors, extractMessage } from '@/lib/errors';
import { longitud, requerido } from '@/lib/validacion';
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
import { toast } from 'sonner';
import { BookOpen, Loader2, MoreVertical, Pencil, Plus, Trash2 } from 'lucide-react';
import type { Materia } from '../../types/academico';

export const MateriasPage = () => {
  const [buscar, setBuscar] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editando, setEditando] = useState<Materia | null>(null);
  const [error, setError] = useState('');
  const [aEliminar, setAEliminar] = useState<Materia | null>(null);

  const { data, isLoading } = useMaterias({ buscar, page });
  const crear = useCrearMateria();
  const actualizar = useActualizarMateria();
  const eliminar = useEliminarMateria();

  const form = useFormValidacion(
    { nombre: '', estado: 'activo' },
    {
      nombre: [requerido('El nombre es obligatorio'), longitud(2, 100, 'El nombre debe tener entre 2 y 100 caracteres')],
    }
  );

  const abrirNuevo = () => {
    setEditando(null);
    form.reiniciar({ nombre: '', estado: 'activo' });
    setError('');
    setModalOpen(true);
  };

  const abrirEditar = (m: Materia) => {
    setEditando(m);
    form.reiniciar({ nombre: m.nombre, estado: m.estado });
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
        toast.success('Materia actualizada');
      } else {
        await crear.mutateAsync(form.valores);
        toast.success('Materia creada');
      }
      setModalOpen(false);
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo guardar la materia.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const handleEliminar = async () => {
    if (!aEliminar) return;
    try {
      await eliminar.mutateAsync(aEliminar.id);
      toast.success('Materia eliminada');
    } catch {
      toast.error('No se pudo eliminar la materia');
    } finally {
      setAEliminar(null);
    }
  };

  const columns: DataTableColumn<Materia>[] = [
    { key: 'nombre', header: 'Materia', render: (m) => <span className="font-medium">{m.nombre}</span> },
    { key: 'estado', header: 'Estado', render: (m) => <StatusBadge estado={m.estado} /> },
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
        title="Materias"
        description="Catálogo de materias disponibles para las aulas."
        action={
          <Button onClick={abrirNuevo}>
            <Plus /> Nueva materia
          </Button>
        }
      />

      <Buscador
        value={buscar}
        onChange={(v) => {
          setBuscar(v);
          setPage(1);
        }}
        placeholder="Buscar materia..."
      />

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(m) => m.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<BookOpen />}
            title="No hay materias"
            description="Agregá las materias que se dictan en la unidad educativa."
            actionLabel="Nueva materia"
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
        title={editando ? 'Editar materia' : 'Nueva materia'}
      >
        {error && (
          <div className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
        )}
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          <CampoTexto
            label="Nombre"
            required
            value={form.valores.nombre}
            onChange={(e) => form.setCampo('nombre', e.target.value)}
            onBlur={form.onBlur('nombre')}
            error={form.errorDe('nombre')}
            placeholder="Ej: Matemáticas"
          />
          <div className="space-y-1.5">
            <Label htmlFor="estado-materia">Estado</Label>
            <Select value={form.valores.estado} onValueChange={(v) => form.setCampo('estado', v ?? 'activo')}>
              <SelectTrigger id="estado-materia" className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="activo">Activo</SelectItem>
                <SelectItem value="inactivo">Inactivo</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => setModalOpen(false)} disabled={pending}>
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
        title={`¿Eliminar la materia ${aEliminar?.nombre ?? ''}?`}
        description="Esta acción no se puede deshacer."
        confirmLabel="Eliminar"
        destructive
        onConfirm={handleEliminar}
      />
    </div>
  );
};
