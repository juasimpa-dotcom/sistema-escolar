import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import type { Rol } from '../types/auth';

interface RoleGuardProps {
  allowedRoles: Rol[];
}

export const RoleGuard = ({ allowedRoles }: RoleGuardProps) => {
  const { user, isLoading } = useAuth();

  if (isLoading) return <div className="flex h-screen items-center justify-center">Cargando...</div>;

  const rol = user?.rol as Rol | undefined;

  if (!rol || !allowedRoles.includes(rol)) {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
};
