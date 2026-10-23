import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { Toaster } from './components/ui/sonner';
import { ProtectedRoute } from './components/ProtectedRoute';
import { RoleGuard } from './components/RoleGuard';
import { AppLayout } from './components/layout/AppLayout';
import { LoginPage } from './pages/auth/LoginPage';
import { CambiarContrasenaObligatorio } from './pages/auth/CambiarContrasenaObligatorio';
import { HomeRedirect } from './pages/HomeRedirect';
import { NotFoundPage } from './pages/NotFoundPage';
import { SuperadminInicio } from './pages/superadmin/Inicio';
import { UnidadesPage } from './pages/superadmin/UnidadesPage';
import { DirectorInicio } from './pages/director/Inicio';
import { DashboardPage } from './pages/director/DashboardPage';
import { CitacionesPage } from './pages/citaciones/CitacionesPage';
import { BandejaCitaciones } from './pages/citaciones/BandejaCitaciones';
import { AdministrativoInicio } from './pages/administrativo/Inicio';
import { PersonasPage } from './pages/personas/PersonasPage';
import { EstudiantesPage } from './pages/estudiantes/EstudiantesPage';
import { GestionesPage } from './pages/academico/GestionesPage';
import { MateriasPage } from './pages/academico/MateriasPage';
import { AulasPage } from './pages/academico/AulasPage';
import { AulaDetallePage } from './pages/academico/AulaDetallePage';
import { ProfesorInicio } from './pages/profesor/Inicio';
import { ProfesorActividadesPage } from './pages/profesor/ActividadesPage';
import { ProfesorAsistenciaPage } from './pages/profesor/AsistenciaPage';
import { LicenciasPage } from './pages/director/LicenciasPage';
import { FamiliaInicio } from './pages/familia/Inicio';
import { FamiliaLicenciasPage } from './pages/familia/LicenciasPage';
import { EstudianteInicio } from './pages/estudiante/Inicio';

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Toaster position="top-right" richColors closeButton />
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route element={<ProtectedRoute />}>
            <Route path="/cambiar-contrasena" element={<CambiarContrasenaObligatorio />} />
            <Route element={<AppLayout />}>
              <Route path="/" element={<HomeRedirect />} />
              <Route element={<RoleGuard allowedRoles={['superadmin']} />}>
                <Route path="/superadmin" element={<SuperadminInicio />} />
                <Route path="/unidades" element={<UnidadesPage />} />
              </Route>
              <Route element={<RoleGuard allowedRoles={['director']} />}>
                <Route path="/director" element={<DirectorInicio />} />
                <Route path="/director/dashboard" element={<DashboardPage />} />
              </Route>
              <Route element={<RoleGuard allowedRoles={['director', 'administrativo']} />}>
                <Route path="/personas" element={<PersonasPage />} />
                <Route path="/estudiantes" element={<EstudiantesPage />} />
                <Route path="/gestiones" element={<GestionesPage />} />
                <Route path="/materias" element={<MateriasPage />} />
                <Route path="/aulas" element={<AulasPage />} />
                <Route path="/aulas/:id" element={<AulaDetallePage />} />
                <Route path="/licencias" element={<LicenciasPage />} />
                <Route path="/citaciones" element={<CitacionesPage />} />
              </Route>
              <Route element={<RoleGuard allowedRoles={['administrativo']} />}>
                <Route path="/administrativo" element={<AdministrativoInicio />} />
              </Route>
              <Route element={<RoleGuard allowedRoles={['profesor']} />}>
                <Route path="/profesor" element={<ProfesorInicio />} />
                <Route path="/profesor/actividades" element={<ProfesorActividadesPage />} />
                <Route path="/profesor/asistencia" element={<ProfesorAsistenciaPage />} />
                <Route path="/profesor/citaciones" element={<BandejaCitaciones />} />
              </Route>
              <Route element={<RoleGuard allowedRoles={['padre']} />}>
                <Route path="/familia" element={<FamiliaInicio />} />
                <Route path="/familia/portal" element={<FamiliaInicio />} />
                <Route path="/familia/licencias" element={<FamiliaLicenciasPage />} />
                <Route path="/familia/citaciones" element={<BandejaCitaciones />} />
              </Route>
              <Route element={<RoleGuard allowedRoles={['estudiante']} />}>
                <Route path="/estudiante" element={<EstudianteInicio />} />
                <Route path="/estudiante/portal" element={<EstudianteInicio />} />
              </Route>
              <Route path="*" element={<NotFoundPage />} />
            </Route>
          </Route>
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
