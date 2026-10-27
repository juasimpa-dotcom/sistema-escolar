import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/EmptyState';

export const AccessDeniedPage = () => (
  <div className="mx-auto flex min-h-[55vh] max-w-2xl items-center justify-center">
    <EmptyState
      kind="notFound"
      title="Acceso denegado"
      description="Tu cuenta no tiene permiso para ver esta página."
      action={<Button render={<Link to="/" />}>Volver al inicio</Button>}
    />
  </div>
);
