import { useAuth } from '@/contexts/AuthContext';
import type { Rol } from '@/types/auth';
import { IMAGES } from '@/config/images';

const roleCopy: Record<Rol, { title: string; institution?: string }> = {
  superadmin: { title: 'Panel municipal', institution: 'Administración Municipal' },
  director: { title: 'Panel de dirección' },
  administrativo: { title: 'Panel administrativo' },
  profesor: { title: 'Panel docente' },
  padre: { title: 'Panel de familia' },
  estudiante: { title: 'Panel del estudiante' },
};

const imageDescriptions: Record<Rol, string> = {
  superadmin: 'Vista nocturna de La Paz con el Illimani al fondo',
  director: 'Edificio educativo y patio arbolado',
  administrativo: 'Espacio de trabajo con biblioteca y escritorios',
  profesor: 'Docente dando clase frente a sus estudiantes',
  padre: 'Familia acompañando a una niña en sus tareas',
  estudiante: 'Estudiantes trabajando juntos con computadoras',
};

export const RoleBanner = ({ role }: { role: Rol }) => {
  const { user } = useAuth();
  const copy = roleCopy[role];
  const date = new Intl.DateTimeFormat('es-BO', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  }).format(new Date());
  const institution = copy.institution ?? user?.unidad_educativa?.nombre ?? 'Unidad educativa';

  return (
    <section className="relative isolate flex min-h-40 items-end overflow-hidden rounded-2xl bg-indigo-950 px-5 py-5 shadow-sm sm:min-h-48 sm:px-8 sm:py-7" aria-label={copy.title}>
      <img
        src={IMAGES.banners[role]}
        alt={imageDescriptions[role]}
        width={1600}
        height={400}
        loading="lazy"
        className="absolute inset-0 -z-20 size-full object-cover"
      />
      <div className="absolute inset-0 -z-10 bg-gradient-to-r from-slate-950/90 via-indigo-950/65 to-slate-950/20" />
      <div className="max-w-3xl space-y-1 text-white drop-shadow-sm">
        <p className="text-sm font-medium text-indigo-100">{copy.title}</p>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Hola, {user?.nombres || 'bienvenido'}.</h1>
        <p className="text-sm font-medium text-white/95 sm:text-base">{institution}</p>
        <p className="text-xs capitalize text-indigo-100 sm:text-sm">{date}</p>
      </div>
    </section>
  );
};
