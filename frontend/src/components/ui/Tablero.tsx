import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { AlertTriangle, ArrowRight, Inbox } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from './button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './card';
import { EmptyState } from './EmptyState';
import { Skeleton } from './skeleton';

/**
 * Esqueleto de carga del tablero: tarjetas de indicadores + paneles.
 */
export const TableroSkeleton = ({
  tarjetas = 4,
  paneles = 2,
}: {
  tarjetas?: number;
  paneles?: number;
}) => (
  <div className="space-y-6" aria-busy="true" aria-label="Cargando panel">
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {Array.from({ length: tarjetas }).map((_, i) => (
        <Card key={i} className="border-border/60">
          <CardContent className="flex items-center gap-4 p-6">
            <Skeleton className="size-11 rounded-xl" />
            <div className="flex-1 space-y-2">
              <Skeleton className="h-3 w-24" />
              <Skeleton className="h-7 w-16" />
            </div>
          </CardContent>
        </Card>
      ))}
    </div>

    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
      {Array.from({ length: paneles }).map((_, i) => (
        <Card key={i} className="border-border/60">
          <CardHeader>
            <Skeleton className="h-4 w-40" />
            <Skeleton className="h-3 w-56" />
          </CardHeader>
          <CardContent className="space-y-3">
            {Array.from({ length: 4 }).map((_, j) => (
              <Skeleton key={j} className="h-9 w-full" />
            ))}
          </CardContent>
        </Card>
      ))}
    </div>
  </div>
);

/**
 * Error amigable del tablero con reintento.
 */
export const TableroError = ({ onReintentar }: { onReintentar?: () => void }) => (
  <EmptyState
    icon={<AlertTriangle className="size-5" />}
    title="No pudimos cargar tu panel"
    description="Ocurrió un problema al consultar los indicadores. Intenta nuevamente."
    action={
      onReintentar ? (
        <Button onClick={onReintentar}>Reintentar</Button>
      ) : undefined
    }
  />
);

/**
 * Lista vacía amigable dentro de un panel del tablero.
 */
export const ListaVacia = ({
  icon = <Inbox className="size-5" />,
  mensaje,
  className,
}: {
  icon?: ReactNode;
  mensaje: string;
  className?: string;
}) => (
  <EmptyState icon={icon} title={mensaje} compact className={cn('rounded-lg border-border/70 bg-transparent', className)} />
);

interface PanelTableroProps {
  titulo: string;
  descripcion?: string;
  icono?: ReactNode;
  /** Muestra el enlace "Ver todo" hacia la sección completa. */
  verA?: string;
  /** Elemento propio a la derecha del título (botón, badge, etc.). */
  accion?: ReactNode;
  className?: string;
  children: ReactNode;
}

/**
 * Tarjeta estándar de los paneles del tablero (título, enlace a la sección y contenido).
 */
export const PanelTablero = ({
  titulo,
  descripcion,
  icono,
  verA,
  accion,
  className,
  children,
}: PanelTableroProps) => (
  <Card className={cn('border-border/60 shadow-sm', className)}>
    <CardHeader>
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          {icono && <span className="text-muted-foreground">{icono}</span>}
          <CardTitle>{titulo}</CardTitle>
        </div>
        {accion}
      </div>
      {descripcion && <CardDescription>{descripcion}</CardDescription>}
      {verA && (
        <Link
          to={verA}
          className="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
        >
          Ver todo <ArrowRight className="size-3" />
        </Link>
      )}
    </CardHeader>
    <CardContent>{children}</CardContent>
  </Card>
);
