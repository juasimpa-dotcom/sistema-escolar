import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import type { Rol } from '../types/auth';

const getHome = (rol: Rol) => {
  switch (rol) {
    case 'superadmin': return '/superadmin';
    case 'director': return '/director';
    case 'administrativo': return '/administrativo';
    case 'profesor': return '/profesor';
    case 'padre': return '/familia';
    case 'estudiante': return '/estudiante';
    default: return '/login';
  }
};

export const HomeRedirect = () => {
  const { user, isLoading } = useAuth();

  if (isLoading) return <div className="flex h-screen items-center justify-center">Cargando...</div>;
  if (!user) return <Navigate to="/login" replace />;
  if (user.debe_cambiar_password) return <Navigate to="/cambiar-contrasena" replace />;

  return <Navigate to={getHome(user.rol as Rol)} replace />;
};
