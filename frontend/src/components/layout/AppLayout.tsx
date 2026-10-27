import { useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { NotificacionesBell } from './NotificacionesBell';
import type { Rol } from '../../types/auth';
import {
  Building2,
  CalendarDays,
  DoorOpen,
  GraduationCap,
  LogOut,
  Menu,
  Users,
  BookOpen,
  X,
  ClipboardCheck,
  FileText,
  LayoutDashboard,
  Mail,
  ChevronLeft,
  ChevronRight,
  Map,
  MapPin,
  ChartNoAxesCombined,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AvatarNombre } from '@/components/ui/AvatarNombre';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { ThemeToggle } from '@/components/layout/ThemeToggle';
import { LogoMarca } from '@/components/layout/LogoMarca';
import { RoleBanner } from '@/components/layout/RoleBanner';
import { cn } from '@/lib/utils';

interface NavItem {
  label: string;
  to: string;
  roles: Rol[];
  icon: typeof Users;
}

const navItems: NavItem[] = [
  { label: 'Inicio', to: '/superadmin', roles: ['superadmin'], icon: Users },
  { label: 'Unidades Educativas', to: '/unidades', roles: ['superadmin'], icon: Building2 },
  { label: 'Macrodistritos', to: '/catalogo/macrodistritos', roles: ['superadmin'], icon: Map },
  { label: 'Distritos municipales', to: '/catalogo/distritos', roles: ['superadmin'], icon: MapPin },
  { label: 'Reportes', to: '/reportes', roles: ['superadmin', 'director'], icon: ChartNoAxesCombined },
  { label: 'Inicio', to: '/director', roles: ['director'], icon: Users },
  { label: 'Panel', to: '/director/dashboard', roles: ['director'], icon: LayoutDashboard },
  { label: 'Reportes', to: '/reportes', roles: ['director'], icon: ChartNoAxesCombined },
  { label: 'Personas', to: '/personas', roles: ['director', 'administrativo'], icon: Users },
  { label: 'Estudiantes', to: '/estudiantes', roles: ['director', 'administrativo'], icon: GraduationCap },
  { label: 'Gestiones', to: '/gestiones', roles: ['director', 'administrativo'], icon: CalendarDays },
  { label: 'Materias', to: '/materias', roles: ['director', 'administrativo'], icon: BookOpen },
  { label: 'Aulas', to: '/aulas', roles: ['director', 'administrativo'], icon: DoorOpen },
  { label: 'Licencias', to: '/licencias', roles: ['director', 'administrativo'], icon: FileText },
  { label: 'Citaciones', to: '/citaciones', roles: ['director', 'administrativo'], icon: Mail },
  { label: 'Inicio', to: '/administrativo', roles: ['administrativo'], icon: Users },
  { label: 'Inicio', to: '/profesor', roles: ['profesor'], icon: Users },
  { label: 'Actividades', to: '/profesor/actividades', roles: ['profesor'], icon: BookOpen },
  { label: 'Asistencia', to: '/profesor/asistencia', roles: ['profesor'], icon: ClipboardCheck },
  { label: 'Citaciones', to: '/profesor/citaciones', roles: ['profesor'], icon: Mail },
  { label: 'Inicio', to: '/familia', roles: ['padre'], icon: Users },
  { label: 'Portal', to: '/familia/portal', roles: ['padre'], icon: GraduationCap },
  { label: 'Licencias', to: '/familia/licencias', roles: ['padre'], icon: FileText },
  { label: 'Citaciones', to: '/familia/citaciones', roles: ['padre'], icon: Mail },
  { label: 'Inicio', to: '/estudiante', roles: ['estudiante'], icon: Users },
  { label: 'Mi información', to: '/estudiante/portal', roles: ['estudiante'], icon: GraduationCap },
];

const getHomePath = (rol?: Rol) => {
  switch (rol) {
    case 'superadmin': return '/superadmin';
    case 'director': return '/director';
    case 'administrativo': return '/administrativo';
    case 'profesor': return '/profesor';
    case 'padre': return '/familia';
    case 'estudiante': return '/estudiante';
    default: return '/';
  }
};

export const AppLayout = () => {
  const { user, logout } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(false);

  const rol = user?.rol as Rol | undefined;
  const mostrarBanner = rol && (location.pathname === getHomePath(rol) || (rol === 'director' && location.pathname === '/director/dashboard'));
  const items = navItems.filter((item) => rol && item.roles.includes(rol));

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const NavContent = ({ isMobile = false }: { isMobile?: boolean }) => (
    <div className="flex h-full flex-col">
      <div className={cn("flex h-16 items-center border-b px-4", collapsed && !isMobile ? "justify-center px-2" : "justify-between")}>
        <Link to={getHomePath(rol)} className={cn("flex items-center gap-2", collapsed && !isMobile && "justify-center")} aria-label="Inicio del Sistema Escolar">
          <LogoMarca compact={collapsed && !isMobile} />
          {(!collapsed || isMobile) && <span className="text-sm font-semibold tracking-tight">Sistema Escolar</span>}
        </Link>
        {isMobile && (
          <Button variant="ghost" size="icon" onClick={() => setSidebarOpen(false)}>
            <X className="h-4 w-4" />
          </Button>
        )}
      </div>
      <nav className="flex-1 space-y-1 overflow-y-auto px-2 py-4 scrollbar-thin">
        {items.map((item) => {
          const Icon = item.icon;
          const isActive = location.pathname === item.to || location.pathname.startsWith(item.to + '/');
          return (
            <Link
              key={item.to + item.label}
              to={item.to}
              className={cn(
                "group flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition-colors",
                isActive
                  ? "bg-primary/10 text-primary"
                  : "text-muted-foreground hover:bg-muted hover:text-foreground",
                collapsed && !isMobile && "justify-center px-2"
              )}
              title={collapsed && !isMobile ? item.label : undefined}
              onClick={() => isMobile && setSidebarOpen(false)}
            >
              <Icon className={cn("h-4 w-4", isActive && "text-primary")} />
              {(!collapsed || isMobile) && <span>{item.label}</span>}
            </Link>
          );
        })}
      </nav>
      <div className="border-t p-3">
        <div className={cn("mb-3 flex items-center gap-3", collapsed && !isMobile && "justify-center")}>
          <AvatarNombre name={user?.nombre_completo} className="h-9 w-9" />
          {(!collapsed || isMobile) && (
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium">{user?.nombre_completo}</p>
              <p className="truncate text-xs text-muted-foreground capitalize">{rol?.replace('_', ' ')}</p>
            </div>
          )}
        </div>
        <Button
          variant="outline"
          className={cn("w-full justify-start gap-2 text-destructive hover:text-destructive", collapsed && !isMobile && "justify-center px-2")}
          onClick={handleLogout}
          title={collapsed && !isMobile ? "Cerrar sesión" : undefined}
        >
          <LogOut className="h-4 w-4" />
          {(!collapsed || isMobile) && "Cerrar sesión"}
        </Button>
      </div>
    </div>
  );

  return (
    <div className="flex h-screen bg-background">
      <aside
        className={cn(
          "hidden border-r bg-card transition-all duration-300 lg:flex lg:flex-col",
          collapsed ? "w-[76px]" : "w-72"
        )}
      >
        <NavContent />
      </aside>

      <Sheet open={sidebarOpen} onOpenChange={setSidebarOpen}>
        <SheetContent side="left" className="w-72 p-0">
          <NavContent isMobile />
        </SheetContent>
      </Sheet>

      <div className="flex flex-1 flex-col overflow-hidden">
        <header className="flex h-16 items-center justify-between border-b bg-card px-4">
          <div className="flex items-center gap-2">
            <Button variant="ghost" size="icon" className="lg:hidden" onClick={() => setSidebarOpen(true)}>
              <Menu className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="icon" className="hidden lg:flex" onClick={() => setCollapsed(!collapsed)}>
              {collapsed ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />}
            </Button>
            <div className="hidden items-center gap-2 md:flex">
              <LogoMarca className="rounded-lg" />
              <span className="text-sm text-muted-foreground">{user?.unidad_educativa?.nombre || 'Sistema Escolar'}</span>
            </div>
          </div>
          <div className="flex items-center gap-1">
            <NotificacionesBell />
            <ThemeToggle />
          </div>
        </header>
        <main className="flex-1 overflow-y-auto p-4 sm:p-6">
          <div className="mx-auto max-w-7xl space-y-6">
            {mostrarBanner && rol && <RoleBanner role={rol} />}
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
};
