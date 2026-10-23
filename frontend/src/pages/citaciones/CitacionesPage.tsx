import { useMemo, useState } from 'react';
import { Loader2, Plus, Send, X } from 'lucide-react';
import {
  useAnularCitacion,
  useCatalogoCitaciones,
  useCitaciones,
  useCrearCitacion,
  useDestinatarios,
} from '../../api/citaciones';
import { PageHeader } from '@/components/ui/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Paginacion } from '@/components/ui/Paginacion';
import { Button } from '@/components/ui/button';
import { CampoSelect, CampoTexto } from '@/components/ui/CampoTexto';
import { Label } from '@/components/ui/label';
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
import type { Citacion, Destinatario, TipoCitacion } from '../../types/citaciones';

export const CitacionesPage = () => {
  const [estado, setEstado] = useState('');
  const [tipo, setTipo] = useState('');
  const [page, setPage] = useState(1);
  const [creando, setCreando] = useState(false);
  const [anulando, setAnulando] = useState<Citacion | null>(null);

  const { data, isLoading } = useCitaciones({
    bandeja: 'enviadas',
    estado: estado || undefined,
    tipo: tipo || undefined,
    page,
    por_pagina: 15,
  });
  const { data: catalogo } = useCatalogoCitaciones();
  const anular = useAnularCitacion();

  const handleAnular = async () => {
    if (!anulando) return;
    try {
      await anular.mutateAsync(anulando.id);
      toast.success('Citación anulada');
      setAnulando(null);
    } catch {
      toast.error('No se pudo anular la citación');
    }
  };

  const columns: DataTableColumn<Citacion>[] = [
    {
      key: 'asunto',
      header: 'Asunto',
      render: (c) => (
        <div>
          <div className="font-medium">{c.asunto}</div>
          {c.estudiante && (
            <div className="text-xs text-muted-foreground">Estudiante: {c.estudiante.nombre_completo}</div>
          )}
        </div>
      ),
    },
    { key: 'tipo', header: 'Tipo', render: (c) => <span className="text-xs">{c.tipo_label}</span> },
    { key: 'destinatario', header: 'Destinatario', render: (c) => c.destinatario || '-' },
    {
      key: 'fecha',
      header: 'Fecha y hora',
      render: (c) => (
        <div className="text-xs">
          {c.fecha_hora}
          {c.lugar && <div className="text-muted-foreground">{c.lugar}</div>}
        </div>
      ),
    },
    { key: 'estado', header: 'Estado', render: (c) => <StatusBadge estado={c.estado} label={c.estado_label} /> },
    {
      key: 'acciones',
      header: '',
      align: 'right',
      render: (c) =>
        c.estado === 'enviada' ? (
          <Button variant="outline" size="sm" onClick={() => setAnulando(c)}>
            <X /> Anular
          </Button>
        ) : null,
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Citaciones y comunicados"
        description="Envía citaciones a padres y comunicados a profesores."
        action={
          <Button onClick={() => setCreando(true)}>
            <Plus /> Nueva citación
          </Button>
        }
      />

      <div className="flex flex-wrap gap-3">
        <Select value={tipo || 'todos'} onValueChange={(v) => { setTipo(!v || v === 'todos' ? '' : v); setPage(1); }}>
          <SelectTrigger className="w-[200px]">
            <SelectValue placeholder="Todos los tipos" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todos">Todos los tipos</SelectItem>
            {catalogo?.data.map((t) => (
              <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={estado || 'todos'} onValueChange={(v) => { setEstado(!v || v === 'todos' ? '' : v); setPage(1); }}>
          <SelectTrigger className="w-[200px]">
            <SelectValue placeholder="Todos los estados" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todos">Todos los estados</SelectItem>
            {catalogo?.estados.map((e) => (
              <SelectItem key={e.value} value={e.value}>{e.label}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        rowKey={(c) => c.id}
        loading={isLoading}
        empty={
          <EmptyState
            icon={<Send />}
            title="No hay citaciones enviadas"
            description="Cuando envíes una citación aparecerá en esta lista."
            actionLabel="Nueva citación"
            onAction={() => setCreando(true)}
          />
        }
      />

      {data && <Paginacion page={data.current_page} lastPage={data.last_page} total={data.total} onChange={setPage} />}

      {creando && <CrearCitacionModal onClose={() => setCreando(false)} />}

      <ConfirmDialog
        open={!!anulando}
        onClose={() => setAnulando(null)}
        onConfirm={handleAnular}
        title="Anular citación"
        description={`¿Anular la citación "${anulando?.asunto}"?`}
        confirmLabel="Anular"
        destructive
      />
    </div>
  );
};

const CrearCitacionModal = ({ onClose }: { onClose: () => void }) => {
  const { data: destinatarios } = useDestinatarios();
  const { data: catalogo } = useCatalogoCitaciones();
  const crear = useCrearCitacion();

  const [tipo, setTipo] = useState<TipoCitacion>('padre');
  const [error, setError] = useState('');

  const form = useFormValidacion(
    { destinatario_id: '', estudiante_id: '', asunto: '', mensaje: '', fecha_hora: '', lugar: '' },
    {
      destinatario_id: [requerido('Seleccioná un destinatario')],
      asunto: [requerido('El asunto es obligatorio'), longitud(3, 200, 'El asunto debe tener entre 3 y 200 caracteres')],
      mensaje: [requerido('El mensaje es obligatorio'), longitud(5, 2000, 'El mensaje debe tener entre 5 y 2000 caracteres')],
      fecha_hora: [requerido('La fecha y hora son obligatorias')],
      lugar: [longitud(0, 200, 'El lugar no puede superar los 200 caracteres')],
    }
  );

  const seleccionado: Destinatario | undefined = useMemo(
    () => destinatarios?.find((d) => String(d.id) === form.valores.destinatario_id),
    [destinatarios, form.valores.destinatario_id]
  );

  const esPadre = seleccionado?.rol === 'padre';

  const errorEstudiante = esPadre && !form.valores.estudiante_id ? 'Seleccioná el estudiante' : form.errorDe('estudiante_id');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (esPadre && !form.valores.estudiante_id) {
      form.setErrorServidor('estudiante_id', 'Seleccioná el estudiante');
      return;
    }
    if (!form.validarTodo()) return;
    try {
      await crear.mutateAsync({
        destinatario_id: Number(form.valores.destinatario_id),
        estudiante_id: esPadre && form.valores.estudiante_id ? Number(form.valores.estudiante_id) : null,
        tipo,
        asunto: form.valores.asunto,
        mensaje: form.valores.mensaje,
        fecha_hora: form.valores.fecha_hora.replace('T', ' ') + ':00',
        lugar: form.valores.lugar || null,
      });
      toast.success('Citación enviada');
      onClose();
    } catch (err: unknown) {
      const errores = extractErrors(err);
      const msg = extractMessage(err, 'No se pudo enviar la citación.');
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje);
      setError(msg);
      toast.error(msg);
    }
  };

  return (
    <Modal open onClose={onClose} title="Nueva citación">
      {error && (
        <div role="alert" className="mb-3 rounded-lg bg-destructive/10 p-2.5 text-sm text-destructive">{error}</div>
      )}
      <form onSubmit={handleSubmit} className="space-y-3" noValidate>
        <CampoSelect
          label="Destinatario"
          value={form.valores.destinatario_id}
          onChange={(e) => {
            form.setCampo('destinatario_id', e.target.value);
            form.setCampo('estudiante_id', '');
          }}
          onBlur={form.onBlur('destinatario_id')}
          error={form.errorDe('destinatario_id')}
          required
        >
          <option value="">Seleccione...</option>
          {destinatarios?.map((d) => (
            <option key={d.id} value={d.id}>{d.nombre_completo} — {d.rol_label}</option>
          ))}
        </CampoSelect>

        {esPadre && (
          <CampoSelect
            label="Estudiante"
            value={form.valores.estudiante_id}
            onChange={(e) => form.setCampo('estudiante_id', e.target.value)}
            onBlur={form.onBlur('estudiante_id')}
            error={errorEstudiante}
            required
          >
            <option value="">Seleccione...</option>
            {seleccionado?.hijos.map((h) => (
              <option key={h.id} value={h.id}>{h.nombre_completo}</option>
            ))}
          </CampoSelect>
        )}

        <div className="space-y-1.5">
          <Label>Tipo</Label>
          <Select value={tipo} onValueChange={(v) => setTipo((v ?? 'padre') as TipoCitacion)}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {catalogo?.data
                .filter((t) => (esPadre ? t.value === 'padre' : t.value !== 'padre'))
                .map((t) => (
                  <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                ))}
            </SelectContent>
          </Select>
        </div>

        <CampoTexto
          label="Asunto"
          value={form.valores.asunto}
          onChange={(e) => form.setCampo('asunto', e.target.value)}
          onBlur={form.onBlur('asunto')}
          error={form.errorDe('asunto')}
          placeholder="Motivo de la citación"
          required
        />

        <CampoTexto
          label="Mensaje"
          multilinea
          rows={3}
          value={form.valores.mensaje}
          onChange={(e) => form.setCampo('mensaje', e.target.value)}
          onBlur={form.onBlur('mensaje')}
          error={form.errorDe('mensaje')}
          placeholder="Detalle de la comunicación"
          required
        />

        <div className="grid grid-cols-2 gap-3">
          <CampoTexto
            label="Fecha y hora"
            type="datetime-local"
            value={form.valores.fecha_hora}
            onChange={(e) => form.setCampo('fecha_hora', e.target.value)}
            onBlur={form.onBlur('fecha_hora')}
            error={form.errorDe('fecha_hora')}
            required
          />
          <CampoTexto
            label="Lugar"
            value={form.valores.lugar}
            onChange={(e) => form.setCampo('lugar', e.target.value)}
            onBlur={form.onBlur('lugar')}
            error={form.errorDe('lugar')}
            placeholder="Opcional"
          />
        </div>

        <div className="flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onClose} disabled={crear.isPending}>
            Cancelar
          </Button>
          <Button type="submit" disabled={crear.isPending}>
            {crear.isPending ? <Loader2 className="animate-spin" /> : <Send />} Enviar
          </Button>
        </div>
      </form>
    </Modal>
  );
};
