import { useState } from 'react';
import {
  useActualizarDistrito,
  useCatalogoMacrodistritos,
  useCrearDistrito,
  useDistritosMunicipales,
  useEliminarDistrito,
} from '../../api/catalogos';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
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
import { Loader2, MapPin, MoreVertical, Pencil, Plus, Trash2 } from 'lucide-react';
import type { DistritoMunicipal } from '../../types/gestion';

const emptyForm = { numero: '', nombre: '', macrodistrito_id: '' };

export const DistritosMunicipalesPage = () => {
  const [buscar, setBuscar] = useState('');
  const [macroFiltro, setMacroFiltro] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editando, setEditando] = useState<DistritoMunicipal | null>(null);
  const [error, setError] = useState('');
  const [aEliminar, setAEliminar] = useState<DistritoMunicipal | null>(null);

  const filtros = {
    buscar,
    macrodistrito_id: macroFiltro ? Number(macroFiltro) : '',
    page,
  };

  const { data, isLoading } = useDistritosMunicipales(filtros);
  const { data: macros } = useCatalogoMacrodistritos();
  const crear = useCrearDistrito();
  const actualizar = useActualizarDistrito();
  const eliminar = useEliminarDistrito();

  const form = useFormValidacion(emptyForm, {
    numero: [requerido('El número de distrito es obligatorio')],
  });

  const cerrar = () => {
    setModalOpen(false);
    setEditando(null);
    setError('');
    form.reiniciar(emptyForm);
  };

  const abrirNuevo = () => {
    setEditando(null);
    form.reiniciar({ ...emptyForm, macrodistrito_id: macroFiltro });
    setError('');
    setModalOpen(true);
  };

  const abrirEditar = (d: DistritoMunicipal) => {
    setEditando(d);
    form.reiniciar({
      numero: String(d.numero),
      nombre: d.nombre ?? '',
      macrodistrito_id: d.macrodistrito_id ? String(d.macrodistrito_id) : '',
    });
    setError('');
    setModalOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;

    const payload = {
      numero: Number(form.valores.numero),
      nombre: form.valores.nombre || null,
      macrodistrito_id: form.valores.macrodistrito_id
        ? Number(form.valores.macrodistrito_id)
        : null,
    };

    try {
      if (editando) {
        await actualizar.mutateAsync({ id: editando.id, payload });
        toast.success('Distrito municipal actualizado');
      } else {
        await crear.mutateAsync(payload);
        toast.success('Distrito municipal creado');
      }
      cerrar();
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo guardar el distrito municipal.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const handleEliminar = async () => {
    if (!aEliminar) return;
    try {
      await eliminar.mutateAsync(aEliminar.id);
      toast.success('Distrito municipal eliminado');
    } catch (err: unknown) {
      toast.error(extractMessage(err, 'No se pudo eliminar el distrito municipal'));
    } finally {
      setAEliminar(null);
    }
  };

  const columns: DataTableColumn<DistritoMunicipal>[] = [
    {
      key: 'numero',
      header: 'N°',
      render: (d) => <span className="font-mono font-semibold">{d.numero}</span>,
    },
    {
      key: 'nombre',
      header: 'Nombre',
      render: (d) => d.nombre || <span className="text-muted-foreground">Sin nombre</span>,
    },
    {
      key: 'macrodistrito',
      header: 'Macrodistrito',
      render: (d) => (
        <MacroBadge
          codigo={d.macrodistrito?.codigo}
          nombre={d.macrodistrito?.nombre}
          tipo={d.macrodistrito?.tipo}
        />
      ),
    },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (d) => (
        <DropdownMenu>
          <DropdownMenuTrigger render={<Button variant="ghost" size="icon-sm" aria-label="Acciones" />}>
            <MoreVertical />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => abrirEditar(d)}>
              <Pencil /> Editar
            </DropdownMenuItem>
            <DropdownMenuItem variant="destructive" onClick={() => setAEliminar(d)}>
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
        title="Distritos Municipales"
        description="Los 24 distritos del municipio y su macrodistrito asignado."
        action={
          <Button onClick={abrirNuevo}>
            <Plus /> Nuevo distrito
          </Button>
        }
      />

      <div className="flex flex-wrap items-center gap-3">
        <div className="flex-1">
          <Buscador
            value={buscar}
            onChange={(v) => {
              setBuscar(v);
              setPage(1);
            }}
            placeholder="Buscar por nombre o número..."
          />
        </div>

        <Select
          value={macroFiltro || 'all'}
          onValueChange={(v) => {
            setMacroFiltro(!v || v === 'all' ? '' : v);
            setPage(1);
          }}
        >
          <SelectTrigger className="w-[230px]">
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
      </div>

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(d) => d.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<MapPin />}
            title="No hay distritos"
            description="Registra los distritos municipales del municipio."
            actionLabel="Nuevo distrito"
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
        title={editando ? 'Editar distrito municipal' : 'Nuevo distrito municipal'}
      >
        {error && (
          <div className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
        )}
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          <div className="grid grid-cols-2 gap-3">
            <CampoTexto
              label="Número"
              required
              inputMode="numeric"
              value={form.valores.numero}
              onChange={(e) => form.setCampo('numero', e.target.value.replace(/\D/g, ''))}
              onBlur={form.onBlur('numero')}
              error={form.errorDe('numero')}
              hint="1 a 24"
            />
            <CampoTexto
              label="Nombre"
              value={form.valores.nombre}
              onChange={(e) => form.setCampo('nombre', e.target.value)}
              hint="Opcional"
            />

            <div className="space-y-1.5 col-span-2">
              <span className="text-sm font-medium leading-none">Macrodistrito</span>
              <Select
                value={form.valores.macrodistrito_id || 'none'}
                onValueChange={(v) => form.setCampo('macrodistrito_id', !v || v === 'none' ? '' : v)}
              >
                <SelectTrigger id="macrodistrito-distrito" className="w-full">
                  <SelectValue placeholder="Selecciona un macrodistrito" />
                </SelectTrigger>
                <SelectContent className="rounded-xl">
                  <SelectItem value="none">Sin asignar</SelectItem>
                  {macros?.map((m) => (
                    <SelectItem key={m.id} value={String(m.id)}>
                      {m.codigo} · {m.nombre} ({m.tipo})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <p className="text-xs text-muted-foreground">
                Puedes dejarlo sin asignar hasta contar con los datos oficiales.
              </p>
            </div>
          </div>

          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={cerrar} disabled={pending}>
              Cancelar
            </Button>
            <Button type="submit" disabled={pending}>
              {pending && <Loader2 className="animate-spin" />}
              {editando ? 'Guardar cambios' : 'Crear distrito'}
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={!!aEliminar}
        onClose={() => setAEliminar(null)}
        onConfirm={handleEliminar}
        title="Eliminar distrito municipal"
        description={`Se eliminará el distrito ${aEliminar?.numero}. Si unidades educativas lo tienen asignado no se podrá eliminar.`}
        confirmLabel="Eliminar"
        destructive
      />
    </div>
  );
};
