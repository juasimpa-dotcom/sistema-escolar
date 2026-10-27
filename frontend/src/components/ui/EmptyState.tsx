import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { Button } from './button';
import {
  NoCitationsIllustration,
  NoDataIllustration,
  NoLicensesIllustration,
  NoResultsIllustration,
  NotFoundIllustration,
} from '@/components/illustrations/EmptyIllustrations';

export type EmptyKind = 'empty' | 'search' | 'citations' | 'licenses' | 'notFound';

interface EmptyStateProps {
  icon?: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
  actionLabel?: string;
  onAction?: () => void;
  className?: string;
  kind?: EmptyKind;
  compact?: boolean;
}

export const EmptyState = ({
  icon,
  title,
  description,
  action,
  actionLabel,
  onAction,
  className,
  kind,
  compact = false,
}: EmptyStateProps) => (
  <div
    className={cn(
      'flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-card/50 px-5 text-center',
      compact ? 'py-4' : 'py-8 sm:py-10',
      className
    )}
  >
    <EmptyIllustration kind={kind ?? inferKind(`${title} ${description ?? ''}`)} compact={compact} />
    {icon && <span className="sr-only">{icon}</span>}
    <div className="space-y-1">
      <p className="text-sm font-medium text-foreground">{title}</p>
      {description && <p className="mx-auto max-w-sm text-sm text-muted-foreground">{description}</p>}
    </div>
    {action}
    {actionLabel && onAction && <Button onClick={onAction}>{actionLabel}</Button>}
  </div>
);

const inferKind = (text: string): EmptyKind => {
  const value = text.toLocaleLowerCase('es');
  if (value.includes('citaci')) return 'citations';
  if (value.includes('licencia')) return 'licenses';
  if (value.includes('resultad') || value.includes('búsqueda') || value.includes('filtro')) return 'search';
  if (value.includes('404') || value.includes('no existe') || value.includes('no encontrado')) return 'notFound';
  return 'empty';
};

const EmptyIllustration = ({ kind, compact }: { kind: EmptyKind; compact: boolean }) => {
  const illustrationClass = compact ? 'h-16 w-24' : 'h-28 w-40';
  const Illustration = {
    empty: NoDataIllustration,
    search: NoResultsIllustration,
    citations: NoCitationsIllustration,
    licenses: NoLicensesIllustration,
    notFound: NotFoundIllustration,
  }[kind];
  return <Illustration className={illustrationClass} />;
};
