import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/EmptyState';

export const NotFoundPage = () => (
  <div className="mx-auto flex min-h-[55vh] max-w-2xl items-center justify-center">
    <EmptyState
      kind="notFound"
      title="404 · Página no encontrada"
      description="La página que buscás no existe o fue movida."
      action={<Button render={<Link to="/" />}>Volver al inicio</Button>}
    />
  </div>
);
