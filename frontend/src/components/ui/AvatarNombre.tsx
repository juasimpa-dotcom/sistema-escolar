import { Avatar, AvatarFallback } from './avatar';
import { cn } from '@/lib/utils';

export const AvatarNombre = ({ name, className }: { name?: string | null; className?: string }) => {
  const nombre = name?.trim() || 'Usuario';
  const iniciales = nombre.split(/\s+/).slice(0, 2).map((parte) => parte[0]).join('').toUpperCase();
  const hash = Array.from(nombre).reduce((total, caracter) => (total * 31 + caracter.charCodeAt(0)) >>> 0, 7);
  const color = `hsl(${hash % 360} 62% 38%)`;

  return (
    <Avatar className={cn(className)} aria-label={nombre}>
      <AvatarFallback className="font-semibold text-white" style={{ backgroundColor: color }}>
        {iniciales || 'U'}
      </AvatarFallback>
    </Avatar>
  );
};
