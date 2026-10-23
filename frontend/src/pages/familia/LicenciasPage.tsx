import { useState } from 'react';
import { useLicencias, useMisHijos, useSolicitarLicencia } from '../../api/academico';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/button';
import { CampoSelect, CampoTexto } from '@/components/ui/CampoTexto';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractErrors, extractMessage } from '@/lib/errors';
import { longitud, requerido } from '@/lib/validacion';
import { toast } from 'sonner';
import { FileText, Loader2, Plus } from 'lucide-react';
import type { Licencia } from '../../types/academico';

const hoy = () => new Date().toISOString().slice(0, 10);

export const FamiliaLicenciasPage = () => {
  const { data, isLoading } = useLicencias({ por_pagina: 20 });
  const [solicitando, setSolicitando] = useState(false);

  const columns: DataTableColumn<Licencia>[] = [
    {
      key: 'estudiante',
      header: 'Estudiante',
      render: (l) => <span className="font-medium">{l.estudiante?.nombre_completo}</span>,
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
        <div className="max-w-xs truncate" title={l.motivo}>{l.motivo}</div>
      ),
    },
    {
      key: 'estado',
      header: 'Estado',
      render: (l) => <StatusBadge estado={l.estado} label={l.estado_label} />,
    },
    {
      key: 'resolucion',
      header: 'Resolución',
      render: (l) =>
        l.motivo_rechazo ? (
          <span className="text-xs text-destructive">{l.motivo_rechazo}</span>
        ) : (
          <span className="text-xs text-muted-foreground">{l.resuelto_por || '-'}</span>
        ),
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Licencias"
        description="Solicita y da seguimiento a las licencias de tus hijos."
        action={
          <Button onClick={() => setSolicitando(true)}>
            <Plus /> Solicitar licencia
          </Button>
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
            title="No has solicitado licencias"
            description="Cuando solicites una licencia para tus hijos aparecerá aquí."
          />
        }
      />

      {solicitando && <SolicitarModal onClose={() => setSolicitando(false)} />}
    </div>
  );
};

const SolicitarModal = ({ onClose }: { onClose: () => void }) => {
  const { data: hijos } = useMisHijos();
  const solicitar = useSolicitarLicencia();
  const [error, setError] = useState('');

  const form = useFormValidacion(
    { estudiante_id: '', fecha_inicio: hoy(), fecha_fin: hoy(), motivo: '' },
    {
      estudiante_id: [requerido('Seleccioná un estudiante')],
      fecha_inicio: [requerido('La fecha de inicio es obligatoria')],
      fecha_fin: [requerido('La fecha de fin es obligatoria')],
      motivo: [requerido('El motivo es obligatorio'), longitud(5, 500, 'El motivo debe tener entre 5 y 500 caracteres')],
    }
  );

  const errorFechaFin = (() => {
    const { fecha_inicio, fecha_fin } = form.valores;
    if (fecha_inicio && fecha_fin && fecha_fin < fecha_inicio) {
      return 'La fecha de fin no puede ser anterior a la de inicio';
    }
    return form.errorDe('fecha_fin');
  })();

  const handleSolicitar = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (form.valores.fecha_fin < form.valores.fecha_inicio) {
      form.setErrorServidor('fecha_fin', 'La fecha de fin no puede ser anterior a la de inicio');
      return;
    }
    if (!form.validarTodo()) return;
    try {
      await solicitar.mutateAsync({
        estudiante_id: Number(form.valores.estudiante_id),
        fecha_inicio: form.valores.fecha_inicio,
        fecha_fin: form.valores.fecha_fin,
        motivo: form.valores.motivo,
      });
      toast.success('Solicitud enviada');
      onClose();
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo enviar la solicitud.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  return (
    <Modal open onClose={onClose} title="Solicitar licencia">
      {error && (
        <div role="alert" className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
      )}
      <form onSubmit={handleSolicitar} className="space-y-4" noValidate>
        <CampoSelect
          label="Estudiante"
          value={form.valores.estudiante_id}
          onChange={(e) => form.setCampo('estudiante_id', e.target.value)}
          onBlur={form.onBlur('estudiante_id')}
          error={form.errorDe('estudiante_id')}
          required
        >
          <option value="">Seleccione...</option>
          {hijos?.map((h) => (
            <option key={h.id} value={h.id}>{h.nombre_completo} — {h.ci}</option>
          ))}
        </CampoSelect>
        <div className="grid grid-cols-2 gap-3">
          <CampoTexto
            label="Desde"
            type="date"
            value={form.valores.fecha_inicio}
            onChange={(e) => form.setCampo('fecha_inicio', e.target.value)}
            onBlur={form.onBlur('fecha_inicio')}
            error={form.errorDe('fecha_inicio')}
            required
          />
          <CampoTexto
            label="Hasta"
            type="date"
            min={form.valores.fecha_inicio}
            value={form.valores.fecha_fin}
            onChange={(e) => form.setCampo('fecha_fin', e.target.value)}
            onBlur={form.onBlur('fecha_fin')}
            error={errorFechaFin}
            required
          />
        </div>
        <CampoTexto
          label="Motivo"
          multilinea
          rows={3}
          value={form.valores.motivo}
          onChange={(e) => form.setCampo('motivo', e.target.value)}
          onBlur={form.onBlur('motivo')}
          error={form.errorDe('motivo')}
          placeholder="Describe el motivo de la licencia"
          required
        />
        <div className="flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onClose} disabled={solicitar.isPending}>
            Cancelar
          </Button>
          <Button type="submit" disabled={solicitar.isPending}>
            {solicitar.isPending && <Loader2 className="animate-spin" />}
            Enviar solicitud
          </Button>
        </div>
      </form>
    </Modal>
  );
};
