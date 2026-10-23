import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Bell, CheckCheck } from 'lucide-react';
import { useMarcarNotificacionLeida, useMarcarTodasLeidas, useNotificaciones } from '../../api/portal';
import { Button } from '../ui/button';
import { EmptyState } from './../ui/EmptyState';
import { cn } from '@/lib/utils';

export const NotificacionesBell = () => {
  const { data } = useNotificaciones();
  const marcarLeida = useMarcarNotificacionLeida();
  const marcarTodas = useMarcarTodasLeidas();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  const noLeidas = data?.no_leidas ?? 0;
  const notificaciones = data?.data ?? [];

  const abrir = (id: string, url: string | null) => {
    marcarLeida.mutate(id);
    setOpen(false);
    if (url) navigate(url);
  };

  return (
    <div className="relative" ref={ref}>
      <Button
        variant="ghost"
        size="icon"
        onClick={() => setOpen((v) => !v)}
        className="relative"
        aria-label="Notificaciones"
      >
        <Bell />
        {noLeidas > 0 && (
          <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-semibold text-white">
            {noLeidas > 9 ? '9+' : noLeidas}
          </span>
        )}
      </Button>

      {open && (
        <div className="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-border bg-popover shadow-lg">
          <div className="flex items-center justify-between border-b border-border px-4 py-2.5">
            <span className="text-sm font-semibold">Notificaciones</span>
            {noLeidas > 0 && (
              <button
                onClick={() => marcarTodas.mutate()}
                className="flex items-center gap-1 text-xs font-medium text-primary hover:underline"
              >
                <CheckCheck size={14} /> Marcar todas
              </button>
            )}
          </div>
          <div className="max-h-80 overflow-y-auto">
            {notificaciones.length === 0 && (
              <EmptyState icon={<Bell size={18} />} title="Sin notificaciones" className="border-0 py-8" />
            )}
            {notificaciones.map((n) => (
              <button
                key={n.id}
                onClick={() => abrir(n.id, n.url)}
                className={cn(
                  'flex w-full flex-col gap-1 border-b border-border px-4 py-3 text-left transition-colors last:border-0 hover:bg-muted/50',
                  !n.leida && 'bg-primary/5'
                )}
              >
                <div className="flex items-center gap-2">
                  {!n.leida && <span className="h-2 w-2 shrink-0 rounded-full bg-primary" />}
                  <span className="text-sm font-medium">{n.titulo}</span>
                </div>
                <span className="line-clamp-2 text-xs text-muted-foreground">{n.mensaje}</span>
                <span className="text-[10px] text-muted-foreground/70">{n.created_at}</span>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};
