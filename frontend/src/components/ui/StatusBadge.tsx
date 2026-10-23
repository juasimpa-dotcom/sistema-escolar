import type { ReactNode } from 'react';
import { Badge } from './badge';
import { cn } from '@/lib/utils';

export type StatusTone = 'success' | 'danger' | 'info' | 'warning' | 'neutral' | 'primary';

const toneClasses: Record<StatusTone, string> = {
  success:
    'border-green-200 bg-green-100 text-green-700 dark:border-green-500/30 dark:bg-green-500/15 dark:text-green-300',
  danger:
    'border-red-200 bg-red-100 text-red-700 dark:border-red-500/30 dark:bg-red-500/15 dark:text-red-300',
  info: 'border-blue-200 bg-blue-100 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300',
  warning:
    'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-300',
  neutral:
    'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-500/30 dark:bg-slate-500/15 dark:text-slate-300',
  primary:
    'border-indigo-200 bg-indigo-100 text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/15 dark:text-indigo-300',
};

interface StatusBadgeProps {
  label?: string;
  estado?: string;
  tone?: StatusTone;
  icon?: ReactNode;
  className?: string;
}

const MAPA_ESTADO: Record<string, { label: string; tone: StatusTone }> = {
  activo: { label: 'Activo', tone: 'success' },
  activa: { label: 'Activo', tone: 'success' },
  inactivo: { label: 'Inactivo', tone: 'neutral' },
  inactiva: { label: 'Inactivo', tone: 'neutral' },
  baja: { label: 'Baja', tone: 'danger' },
  cerrada: { label: 'Cerrada', tone: 'neutral' },
  presente: { label: 'Presente', tone: 'success' },
  falta: { label: 'Falta', tone: 'danger' },
  licencia: { label: 'Licencia', tone: 'info' },
  atraso: { label: 'Atraso', tone: 'warning' },
  pendiente: { label: 'Pendiente', tone: 'warning' },
  aprobada: { label: 'Aprobada', tone: 'success' },
  rechazada: { label: 'Rechazada', tone: 'danger' },
  enviada: { label: 'Enviada', tone: 'info' },
  vista: { label: 'Vista', tone: 'success' },
  anulada: { label: 'Anulada', tone: 'neutral' },
  vencida: { label: 'Vencida', tone: 'danger' },
};

export const statusMeta = (estado: string): { label: string; tone: StatusTone } =>
  MAPA_ESTADO[estado] ?? { label: estado, tone: 'neutral' };

export const StatusBadge = ({ label, estado, tone, icon, className }: StatusBadgeProps) => {
  const meta = estado ? statusMeta(estado) : undefined;
  return (
    <Badge variant="outline" className={cn('font-medium', toneClasses[tone ?? meta?.tone ?? 'neutral'], className)}>
      {icon}
      {label ?? meta?.label ?? ''}
    </Badge>
  );
};

interface EstadoBadgeProps {
  estado: string;
  label?: string;
  className?: string;
}

export const EstadoBadge = ({ estado, label, className }: EstadoBadgeProps) => (
  <StatusBadge estado={estado} label={label} className={className} />
);
