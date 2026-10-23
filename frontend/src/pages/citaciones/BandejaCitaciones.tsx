import { useState } from 'react';
import { Calendar, Check, Mail, MapPin } from 'lucide-react';
import { useCitaciones, useMarcarVistaCitacion } from '../../api/citaciones';
import { PageHeader } from '@/components/ui/PageHeader';
import { Paginacion } from '@/components/ui/Paginacion';
import { Modal } from '@/components/ui/Modal';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import type { Citacion } from '../../types/citaciones';

export const BandejaCitaciones = () => {
  const [page, setPage] = useState(1);
  const [detalle, setDetalle] = useState<Citacion | null>(null);
  const { data, isLoading } = useCitaciones({ bandeja: 'recibidas', page, por_pagina: 15 });
  const marcar = useMarcarVistaCitacion();

  const abrir = async (c: Citacion) => {
    setDetalle(c);
    if (c.estado === 'enviada') {
      try {
        await marcar.mutateAsync(c.id);
      } catch {
        /* silencioso */
      }
    }
  };

  return (
    <div className="space-y-6">
      <PageHeader title="Mi bandeja de citaciones" description="Citaciones y comunicados recibidos." />

      <div className="space-y-3">
        {isLoading &&
          Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="rounded-xl border border-border bg-card p-4 shadow-sm">
              <div className="flex items-start gap-3">
                <Skeleton className="size-9 rounded-full" />
                <div className="flex-1 space-y-2">
                  <Skeleton className="h-4 w-1/3" />
                  <Skeleton className="h-3 w-2/3" />
                  <Skeleton className="h-3 w-1/4" />
                </div>
              </div>
            </div>
          ))}

        {!isLoading &&
          data?.data.map((c) => (
            <button
              key={c.id}
              onClick={() => abrir(c)}
              className="block w-full rounded-xl border border-border bg-card p-4 text-left shadow-sm transition hover:shadow-md"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex items-start gap-3">
                  <div
                    className={cn(
                      'mt-1 rounded-full p-2',
                      c.estado === 'vista' ? 'bg-muted text-muted-foreground' : 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300'
                    )}
                  >
                    <Mail size={16} />
                  </div>
                  <div>
                    <div className="font-medium">{c.asunto}</div>
                    <div className="text-xs text-muted-foreground">
                      De: {c.emisor} · {c.tipo_label}
                      {c.estudiante && ` · Estudiante: ${c.estudiante.nombre_completo}`}
                    </div>
                    <div className="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                      <span className="inline-flex items-center gap-1"><Calendar size={12} /> {c.fecha_hora}</span>
                      {c.lugar && <span className="inline-flex items-center gap-1"><MapPin size={12} /> {c.lugar}</span>}
                    </div>
                  </div>
                </div>
                <StatusBadge estado={c.estado} label={c.estado_label} className="shrink-0" />
              </div>
            </button>
          ))}

        {!isLoading && data && data.data.length === 0 && (
          <EmptyState
            icon={<Mail />}
            title="No tienes citaciones recibidas"
            description="Cuando recibas una citación o comunicado aparecerá aquí."
          />
        )}
      </div>

      {data && <Paginacion page={data.current_page} lastPage={data.last_page} total={data.total} onChange={setPage} />}

      {detalle && (
        <Modal open onClose={() => setDetalle(null)} title={detalle.asunto}>
          <div className="space-y-3 text-sm">
            <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
              <span className="rounded bg-muted px-2 py-0.5">{detalle.tipo_label}</span>
              <StatusBadge estado={detalle.estado} label={detalle.estado_label} />
              <span className="inline-flex items-center gap-1"><Calendar size={12} /> {detalle.fecha_hora}</span>
              {detalle.lugar && <span className="inline-flex items-center gap-1"><MapPin size={12} /> {detalle.lugar}</span>}
            </div>
            {detalle.estudiante && (
              <p className="text-muted-foreground">Estudiante: <strong className="text-foreground">{detalle.estudiante.nombre_completo}</strong></p>
            )}
            <p className="whitespace-pre-line text-foreground">{detalle.mensaje}</p>
            <p className="flex items-center gap-1 text-xs text-muted-foreground">
              <Check size={12} /> Emitida por {detalle.emisor} · {detalle.emisor_rol}
            </p>
          </div>
        </Modal>
      )}
    </div>
  );
};
