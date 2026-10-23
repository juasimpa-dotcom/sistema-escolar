import { useState } from 'react';
import { useCrearUnidad, useUnidades, useBajaUnidad } from '../../api/gestion';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { Buscador } from '@/components/ui/Buscador';
import { Paginacion } from '@/components/ui/Paginacion';
import { Modal } from '@/components/ui/Modal';
import { BajaModal } from '@/components/ui/BajaModal';
import { Button } from '@/components/ui/button';
import { CampoTexto } from '@/components/ui/CampoTexto';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractErrors, extractMessage } from '@/lib/errors';
import { ci, email, nombre, requerido, telefono } from '@/lib/validacion';
import { toast } from 'sonner';
import { Building2, Loader2, Plus } from 'lucide-react';
import type { Unidad } from '../../types/gestion';

const emptyForm = {
  nombre: '',
  codigo: '',
  direccion: '',
  telefono: '',
  director: { ci: '', nombres: '', apellidos: '', email: '', telefono: '' },
};

export const UnidadesPage = () => {
  const [buscar, setBuscar] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [error, setError] = useState('');
  const [aBaja, setABaja] = useState<Unidad | null>(null);

  const { data, isLoading } = useUnidades({ buscar, page });
  const crear = useCrearUnidad();
  const baja = useBajaUnidad();

  const form = useFormValidacion(emptyForm, {
    nombre: [requerido('El nombre es obligatorio')],
    codigo: [requerido('El código es obligatorio')],
    telefono: [telefono()],
    'director.ci': [requerido('El CI del director es obligatorio'), ci()],
    'director.nombres': [requerido('Los nombres del director son obligatorios'), nombre()],
    'director.apellidos': [requerido('Los apellidos del director son obligatorios'), nombre()],
    'director.email': [email()],
    'director.telefono': [telefono()],
  });

  const cerrar = () => {
    setModalOpen(false);
    setError('');
    form.reiniciar();
  };

  const handleCrear = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;
    try {
      await crear.mutateAsync(form.valores);
      cerrar();
      toast.success('Unidad educativa creada');
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo crear la unidad.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  const columns: DataTableColumn<Unidad>[] = [
    { key: 'codigo', header: 'Código', render: (u) => <span className="font-mono">{u.codigo}</span> },
    { key: 'nombre', header: 'Nombre', render: (u) => <span className="font-medium">{u.nombre}</span> },
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
    { key: 'telefono', header: 'Teléfono', render: (u) => u.telefono || '-' },
    { key: 'estado', header: 'Estado', render: (u) => <StatusBadge estado={u.estado} /> },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (u) =>
        u.estado === 'activa' ? (
          <Button variant="destructive" size="sm" onClick={() => setABaja(u)}>
            Dar de baja
          </Button>
        ) : null,
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Unidades Educativas"
        description="Administra las unidades educativas del sistema."
        action={
          <Button onClick={() => setModalOpen(true)}>
            <Plus /> Nueva unidad
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
        rowKey={(u) => u.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<Building2 />}
            title="No hay unidades"
            description="Registra la primera unidad educativa para comenzar."
            actionLabel="Nueva unidad"
            onAction={() => setModalOpen(true)}
          />
        }
      />

      {data && (
        <Paginacion page={data.current_page} lastPage={data.last_page} total={data.total} onChange={setPage} />
      )}

      <Modal open={modalOpen} onClose={cerrar} title="Nueva unidad educativa">
        {error && (
          <div className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
        )}
        <form onSubmit={handleCrear} className="space-y-4" noValidate>
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
          </div>

          <div className="space-y-3 rounded-lg border p-3">
            <h3 className="text-sm font-semibold">Director de la unidad</h3>
            <div className="grid grid-cols-2 gap-3">
              <CampoTexto
                label="CI"
                required
                inputMode="numeric"
                value={form.valores.director.ci}
                onChange={(e) => form.setCampo('director.ci', e.target.value)}
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

          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={cerrar} disabled={crear.isPending}>
              Cancelar
            </Button>
            <Button type="submit" disabled={crear.isPending}>
              {crear.isPending && <Loader2 className="animate-spin" />}
              Crear unidad
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
