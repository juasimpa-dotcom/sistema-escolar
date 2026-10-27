import { Trees } from 'lucide-react';
import { Badge } from './badge';
import { cn } from '@/lib/utils';
import type { TipoMacrodistrito } from '@/types/gestion';

/** Paleta fija por macrodistrito para que cada uno tenga siempre el mismo color. */
const COLORES: Record<string, string> = {
  MD1: 'border-sky-200 bg-sky-100 text-sky-700 dark:border-sky-500/30 dark:bg-sky-500/15 dark:text-sky-300',
  MD2: 'border-violet-200 bg-violet-100 text-violet-700 dark:border-violet-500/30 dark:bg-violet-500/15 dark:text-violet-300',
  MD3: 'border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-300',
  MD4: 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-300',
  MD5: 'border-rose-200 bg-rose-100 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-300',
  MD6: 'border-cyan-200 bg-cyan-100 text-cyan-700 dark:border-cyan-500/30 dark:bg-cyan-500/15 dark:text-cyan-300',
  MD7: 'border-indigo-200 bg-indigo-100 text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/15 dark:text-indigo-300',
  MD8: 'border-lime-200 bg-lime-100 text-lime-700 dark:border-lime-500/30 dark:bg-lime-500/15 dark:text-lime-300',
  MD9: 'border-fuchsia-200 bg-fuchsia-100 text-fuchsia-700 dark:border-fuchsia-500/30 dark:bg-fuchsia-500/15 dark:text-fuchsia-300',
};

const RESERVA = 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-500/30 dark:bg-slate-500/15 dark:text-slate-300';

const colorMacrodistrito = (codigo?: string | null): string =>
  (codigo && COLORES[codigo.toUpperCase()]) || RESERVA;

interface Props {
  codigo?: string | null;
  nombre?: string | null;
  tipo?: TipoMacrodistrito | null;
  /** Sin nombre: solo se muestra el código. */
  compacto?: boolean;
  className?: string;
}

export const MacroBadge = ({ codigo, nombre, tipo, compacto = false, className }: Props) => {
  if (!codigo && !nombre) {
    return (
      <Badge variant="outline" className={cn('text-muted-foreground', className)}>
        Sin asignar
      </Badge>
    );
  }

  const etiqueta = compacto || !nombre ? codigo : `${codigo} · ${nombre}`;

  return (
    <Badge
      variant="outline"
      title={tipo ? (tipo === 'rural' ? 'Macrodistrito rural' : 'Macrodistrito urbano') : undefined}
      className={cn('font-medium', colorMacrodistrito(codigo), className)}
    >
      {etiqueta}
      {tipo === 'rural' && <Trees aria-hidden />}
    </Badge>
  );
};
