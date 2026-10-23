import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { Card, CardContent } from './card';
import { TrendingDown, TrendingUp } from 'lucide-react';

interface StatCardProps {
  title: string;
  value: ReactNode;
  icon: ReactNode;
  hint?: string;
  trend?: number;
  trendLabel?: string;
  accent?: 'primary' | 'success' | 'danger' | 'warning' | 'info';
  variant?: 'primary' | 'success' | 'danger' | 'warning' | 'info' | 'indigo' | 'verde' | 'rojo' | 'ambar' | 'azul';
  className?: string;
}

const accentClasses: Record<string, string> = {
  primary: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
  indigo: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
  success: 'bg-green-100 text-green-600 dark:bg-green-500/15 dark:text-green-300',
  verde: 'bg-green-100 text-green-600 dark:bg-green-500/15 dark:text-green-300',
  danger: 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300',
  rojo: 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300',
  warning: 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
  ambar: 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
  info: 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300',
  azul: 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300',
};

export const StatCard = ({
  title,
  value,
  icon,
  hint,
  trend,
  trendLabel,
  accent = 'primary',
  className,
}: StatCardProps) => {
  const positive = typeof trend === 'number' && trend >= 0;

  return (
    <Card className={cn('gap-0 shadow-sm ring-1 ring-border', className)}>
      <CardContent className="flex items-start gap-4">
        <div className={cn('flex size-11 shrink-0 items-center justify-center rounded-xl', accentClasses[accent])}>
          {icon}
        </div>
        <div className="min-w-0 flex-1">
          <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{title}</p>
          <p className="mt-1 text-2xl font-semibold leading-none text-foreground">{value}</p>
          {(hint || typeof trend === 'number') && (
            <div className="mt-2 flex items-center gap-2 text-xs">
              {typeof trend === 'number' && (
                <span
                  className={cn(
                    'inline-flex items-center gap-1 font-medium',
                    positive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                  )}
                >
                  {positive ? <TrendingUp size={13} /> : <TrendingDown size={13} />}
                  {positive ? '+' : ''}
                  {trend}%
                </span>
              )}
              {hint && <span className="truncate text-muted-foreground">{hint}</span>}
              {trendLabel && <span className="truncate text-muted-foreground">{trendLabel}</span>}
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};
