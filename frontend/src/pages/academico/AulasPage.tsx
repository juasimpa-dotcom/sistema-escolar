import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useActualizarAula, useAulas, useCrearAula, useGestiones } from '../../api/academico';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
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
import { DoorOpen, Loader2, MoreVertical, Pencil, Plus, Settings2 } from 'lucide-react';
import type { Aula, Turno } from '../../types/academico';

const emptyForm = {
  gestion_id: '' as string | number,
  grado: '',
  paralelo: '',
  turno: 'manana' as Turno,
  estado: 'activo',
};

export const AulasPage = () => {
  const [buscar, setBuscar] = useState('');
  const [page, setPage] = useState(1);
  const [gestionFiltro, setGestionFiltro] = useState<string>('todas');
  const [modalOpen, setModalOpen] = useState(false);
  const [editando, setEditando] = useState<Aula | null>(null);
  const [error, setError] = useState('');

  const { data: gestionesData } = useGestiones({ por_pagina: 100 });
  const { data, isLoading } = useAulas({
    buscar,
    page,
    gestion_id: gestionFiltro === 'todas' ? undefined : Number(gestionFiltro),
  });
  const crear = useCrearAula();
  const actualizar = useActualizarAula();

  const form = useFormValidacion(emptyForm, {
    gestion_id: [requerido('Seleccioná una gestión')],
    grado: [requerido('El grado es obligatorio'), longitud(1, 50)],
    paralelo: [requerido('El paralelo es obligatorio'), longitud(1, 10)],
  });

  const abrirNuevo = () => {
    setEditando(null);
    form.reiniciar();
    setError('');
    setModalOpen(true);
  };

  const abrirEditar = (a: Aula) => {
    setEditando(a);
    form.reiniciar({ gestion_id: a.gestion_id, grado: a.grado, paralelo: a.paralelo, turno: a.turno, estado: a.estado });
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
    if (!form.validarTodo()) return;
    try {
      if (editando) {
        await actualizar.mutateAsync({
          id: editando.id,
          payload: {
            grado: form.valores.grado,
            paralelo: form.valores.paralelo,
            turno: form.valores.turno,
            estado: form.valores.estado,
          },
        });
        toast.success('Aula actualizada');
      } else {
        await crear.mutateAsync({ ...form.valores, gestion_id: Number(form.valores.gestion_id) });
        toast.success('Aula creada');
      }
      setModalOpen(false);
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo guardar el aula.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const columns: DataTableColumn<Aula>[] = [
    { key: 'nombre', header: 'Aula', render: (a) => <span className="font-medium">{a.nombre}</span> },
    {
      key: 'gestion',
      header: 'Gestión',
      render: (a) => a.gestion?.anio ?? <span className="text-muted-foreground">—</span>,
    },
    { key: 'turno', header: 'Turno', render: (a) => a.turno_label },
    { key: 'materias', header: 'Materias', render: (a) => a.materias_count ?? 0 },
    { key: 'inscritos', header: 'Inscritos', render: (a) => a.inscritos_count ?? 0 },
    { key: 'estado', header: 'Estado', render: (a) => <StatusBadge estado={a.estado} /> },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (a) => (
        <DropdownMenu>
          <DropdownMenuTrigger render={<Button variant="ghost" size="icon-sm" aria-label="Acciones" />}>
            <MoreVertical />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem render={<Link to={`/aulas/${a.id}`} />}>
              <Settings2 /> Gestionar
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => abrirEditar(a)}>
              <Pencil /> Editar
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
        title="Aulas"
        description="Organizá las aulas por gestión, turno y materias."
        action={
          <Button onClick={abrirNuevo}>
            <Plus /> Nueva aula
          </Button>
        }
      />

      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <Buscador
          value={buscar}
          onChange={(v) => {
            setBuscar(v);
            setPage(1);
          }}
          placeholder="Buscar por grado..."
        />
        <Select
          value={gestionFiltro}
          onValueChange={(v) => {
            setGestionFiltro(v ?? '');
            setPage(1);
          }}
        >
          <SelectTrigger className="w-full sm:w-[12rem]">
            <SelectValue placeholder="Todas las gestiones" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todas">Todas las gestiones</SelectItem>
            {gestionesData?.data.map((g) => (
              <SelectItem key={g.id} value={String(g.id)}>
                {g.anio}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(a) => a.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<DoorOpen />}
            title="No hay aulas"
            description="Creá un aula para comenzar a asignarle materias y estudiantes."
            actionLabel="Nueva aula"
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
        title={editando ? 'Editar aula' : 'Nueva aula'}
      >
        {error && (
          <div className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
        )}
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          {!editando && (
            <div className="space-y-1.5">
              <Label htmlFor="aula-gestion">Gestión</Label>
              <Select
                value={form.valores.gestion_id ? String(form.valores.gestion_id) : ''}
                onValueChange={(v) => form.setCampo('gestion_id', v ?? '')}
              >
                <SelectTrigger id="aula-gestion" className="w-full" aria-invalid={!!form.errorDe('gestion_id')}>
                  <SelectValue placeholder="Seleccione..." />
                </SelectTrigger>
                <SelectContent>
                  {gestionesData?.data.map((g) => (
                    <SelectItem key={g.id} value={String(g.id)}>
                      {g.anio}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errorDe('gestion_id') && (
                <p role="alert" className="text-xs font-medium text-destructive">{form.errorDe('gestion_id')}</p>
              )}
            </div>
          )}
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <CampoTexto
              label="Grado"
              required
              value={form.valores.grado}
              onChange={(e) => form.setCampo('grado', e.target.value)}
              onBlur={form.onBlur('grado')}
              error={form.errorDe('grado')}
              placeholder="Ej: 1ro"
            />
            <CampoTexto
              label="Paralelo"
              required
              value={form.valores.paralelo}
              onChange={(e) => form.setCampo('paralelo', e.target.value)}
              onBlur={form.onBlur('paralelo')}
              error={form.errorDe('paralelo')}
              placeholder="Ej: A"
            />
            <div className="space-y-1.5">
              <Label htmlFor="aula-turno">Turno</Label>
              <Select value={form.valores.turno} onValueChange={(v) => form.setCampo('turno', v as Turno)}>
                <SelectTrigger id="aula-turno" className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="manana">Mañana</SelectItem>
                  <SelectItem value="tarde">Tarde</SelectItem>
                  <SelectItem value="noche">Noche</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="aula-estado">Estado</Label>
              <Select value={form.valores.estado} onValueChange={(v) => form.setCampo('estado', v ?? '')}>
                <SelectTrigger id="aula-estado" className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="activo">Activo</SelectItem>
                  <SelectItem value="inactivo">Inactivo</SelectItem>
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
    </div>
  );
};
