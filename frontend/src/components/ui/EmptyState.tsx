import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { Button } from './button';

interface EmptyStateProps {
  icon: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
  actionLabel?: string;
  onAction?: () => void;
  className?: string;
}

export const EmptyState = ({
  icon,
  title,
  description,
  action,
  actionLabel,
  onAction,
  className,
}: EmptyStateProps) => (
  <div
    className={cn(
      'flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-card/50 px-6 py-12 text-center',
      className
    )}
  >
    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
      {icon}
    </div>
    <div className="space-y-1">
      <p className="text-sm font-medium text-foreground">{title}</p>
      {description && <p className="mx-auto max-w-sm text-sm text-muted-foreground">{description}</p>}
    </div>
    {action}
    {actionLabel && onAction && <Button onClick={onAction}>{actionLabel}</Button>}
  </div>
);
