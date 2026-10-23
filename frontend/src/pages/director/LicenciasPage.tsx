import { useState } from 'react';
import { useAprobarLicencia, useLicencias, useRechazarLicencia } from '../../api/academico';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/FormField';
import { Textarea } from '@/components/ui/textarea';
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
import { toast } from 'sonner';
import { Check, FileText, Loader2, X } from 'lucide-react';
import type { Licencia } from '../../types/academico';

const ESTADOS = [
  { value: 'todas', label: 'Todas' },
  { value: 'pendiente', label: 'Pendientes' },
  { value: 'aprobada', label: 'Aprobadas' },
  { value: 'rechazada', label: 'Rechazadas' },
];

export const LicenciasPage = () => {
  const [estado, setEstado] = useState('pendiente');
  const { data, isLoading } = useLicencias({
    estado: estado === 'todas' ? undefined : estado,
    por_pagina: 20,
  });
  const [rechazando, setRechazando] = useState<Licencia | null>(null);
  const aprobar = useAprobarLicencia();

  const handleAprobar = async (licencia: Licencia) => {
    try {
      await aprobar.mutateAsync(licencia.id);
      toast.success('Licencia aprobada');
    } catch {
      toast.error('No se pudo aprobar la licencia');
    }
  };

  const columns: DataTableColumn<Licencia>[] = [
    {
      key: 'estudiante',
      header: 'Estudiante',
      render: (l) => (
        <div>
          <div className="font-medium">{l.estudiante?.nombre_completo}</div>
          <div className="font-mono text-xs text-muted-foreground">{l.estudiante?.ci}</div>
          <div className="text-xs text-muted-foreground">Solicitó: {l.padre}</div>
        </div>
      ),
    },
    {
      key: 'rango',
      header: 'Rango',
      render: (l) => `${l.fecha_inicio} → ${l.fecha_fin}`,
    },
    {
      key: 'motivo',
      header: 'Motivo',
      render: (l) => (
        <div className="max-w-xs">
          <div className="truncate" title={l.motivo}>{l.motivo}</div>
          {l.motivo_rechazo && <div className="text-xs text-destructive">Rechazo: {l.motivo_rechazo}</div>}
        </div>
      ),
    },
    {
      key: 'estado',
      header: 'Estado',
      render: (l) => <StatusBadge estado={l.estado} label={l.estado_label} />,
    },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (l) =>
        l.estado === 'pendiente' ? (
          <div className="flex justify-end gap-2">
            <Button size="sm" onClick={() => handleAprobar(l)} disabled={aprobar.isPending}>
              <Check /> Aprobar
            </Button>
            <Button size="sm" variant="destructive" onClick={() => setRechazando(l)}>
              <X /> Rechazar
            </Button>
          </div>
        ) : null,
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Licencias"
        description="Aprueba o rechaza las solicitudes de licencia."
        action={
          <Select value={estado} onValueChange={(v) => setEstado(v ?? 'todas')}>
            <SelectTrigger className="w-[180px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {ESTADOS.map((e) => (
                <SelectItem key={e.value} value={e.value}>{e.label}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        }
      />

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(l) => l.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<FileText />}
            title="No hay licencias"
            description="No se encontraron solicitudes con el filtro seleccionado."
          />
        }
      />

      {rechazando && (
        <RechazarModal licencia={rechazando} onClose={() => setRechazando(null)} />
      )}
    </div>
  );
};

const RechazarModal = ({ licencia, onClose }: { licencia: Licencia; onClose: () => void }) => {
  const rechazar = useRechazarLicencia();
  const [error, setError] = useState('');

  const form = useFormValidacion(
    { motivo_rechazo: '' },
    {
      motivo_rechazo: [
        requerido('El motivo del rechazo es obligatorio'),
        longitud(5, 500, 'El motivo debe tener entre 5 y 500 caracteres'),
      ],
    }
  );

  const handleRechazar = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;
    try {
      await rechazar.mutateAsync({ id: licencia.id, motivo_rechazo: form.valores.motivo_rechazo });
      toast.success('Licencia rechazada');
      onClose();
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo rechazar la licencia.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  return (
    <Modal open onClose={onClose} title={`Rechazar licencia — ${licencia.estudiante?.nombre_completo}`}>
      {error && (
        <div role="alert" className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
      )}
      <form onSubmit={handleRechazar} className="space-y-4" noValidate>
        <FormField label="Motivo del rechazo (obligatorio)" error={form.errorDe('motivo_rechazo')}>
          <Textarea
            value={form.valores.motivo_rechazo}
            onChange={(e) => form.setCampo('motivo_rechazo', e.target.value)}
            onBlur={form.onBlur('motivo_rechazo')}
            rows={3}
            placeholder="Explicá por qué se rechaza la licencia"
            aria-invalid={!!form.errorDe('motivo_rechazo')}
          />
        </FormField>
        <div className="flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onClose} disabled={rechazar.isPending}>
            Cancelar
          </Button>
          <Button type="submit" variant="destructive" disabled={rechazar.isPending}>
            {rechazar.isPending && <Loader2 className="animate-spin" />}
            Rechazar
          </Button>
        </div>
      </form>
    </Modal>
  );
};
