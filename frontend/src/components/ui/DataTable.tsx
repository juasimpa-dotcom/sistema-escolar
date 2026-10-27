import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { Skeleton } from './skeleton';
import { EmptyState } from './EmptyState';

export interface DataTableColumn<T> {
  key: string;
  header: ReactNode;
  cell?: (row: T) => ReactNode;
  render?: (row: T) => ReactNode;
  className?: string;
  headerClassName?: string;
  align?: 'left' | 'center' | 'right';
  hidden?: 'sm' | 'md' | 'lg';
}

interface DataTableProps<T> {
  columns: DataTableColumn<T>[];
  data: T[];
  rowKey: (row: T) => string | number;
  loading?: boolean;
  skeletonRows?: number;
  empty?: ReactNode;
  onRowClick?: (row: T) => void;
  className?: string;
}

const hiddenClasses = {
  sm: 'hidden sm:table-cell',
  md: 'hidden md:table-cell',
  lg: 'hidden lg:table-cell',
};

const alignClasses = {
  left: 'text-left',
  center: 'text-center',
  right: 'text-right',
};

export function DataTable<T>({
  columns,
  data,
  rowKey,
  loading,
  skeletonRows = 5,
  empty,
  onRowClick,
  className,
}: DataTableProps<T>) {
  const mostrarVacio = !loading && data.length === 0;

  return (
    <div className={cn('overflow-hidden rounded-xl border border-border bg-card shadow-sm', className)}>
      <div className="overflow-x-auto">
        <table className="w-full caption-bottom text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/40">
              {columns.map((col) => (
                <th
                  key={col.key}
                  className={cn(
                    'h-11 px-4 text-xs font-semibold uppercase tracking-wide text-muted-foreground',
                    alignClasses[col.align ?? 'left'],
                    col.hidden && hiddenClasses[col.hidden],
                    col.headerClassName
                  )}
                >
                  {col.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading &&
              Array.from({ length: skeletonRows }).map((_, i) => (
                <tr key={`skeleton-${i}`} className="border-b border-border last:border-0">
                  {columns.map((col) => (
                    <td key={col.key} className={cn('px-4 py-3', col.hidden && hiddenClasses[col.hidden])}>
                      <Skeleton className="h-4 w-full max-w-[140px]" />
                    </td>
                  ))}
                </tr>
              ))}

            {!loading &&
              data.map((row) => (
                <tr
                  key={rowKey(row)}
                  onClick={onRowClick ? () => onRowClick(row) : undefined}
                  className={cn(
                    'border-b border-border transition-colors last:border-0 hover:bg-muted/40',
                    onRowClick && 'cursor-pointer'
                  )}
                >
                  {columns.map((col) => (
                    <td
                      key={col.key}
                      className={cn(
                        'px-4 py-3 align-middle',
                        alignClasses[col.align ?? 'left'],
                        col.hidden && hiddenClasses[col.hidden],
                        col.className
                      )}
                    >
                      {col.cell ? col.cell(row) : col.render?.(row)}
                    </td>
                  ))}
                </tr>
              ))}

            {mostrarVacio && (
              <tr>
                <td colSpan={columns.length} className="px-4 py-2">
                  {empty ?? <EmptyState title="No hay registros" description="Cuando haya información disponible, aparecerá aquí." compact />}
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
