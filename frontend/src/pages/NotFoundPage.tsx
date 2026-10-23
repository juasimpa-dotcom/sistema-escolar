import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Compass } from 'lucide-react';

export const NotFoundPage = () => (
  <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
    <div className="flex size-16 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
      <Compass className="size-8" />
    </div>
    <div className="space-y-1">
      <h1 className="text-3xl font-bold tracking-tight">404</h1>
      <p className="text-muted-foreground">La página que buscás no existe o fue movida.</p>
    </div>
    <Button render={<Link to="/" />}>Volver al inicio</Button>
  </div>
);
