import { usePortalMiPerfil } from '../../api/portal';
import { PortalResumenView } from '../portal/PortalResumenView';

export const EstudiantePortal = () => {
  const { data, isLoading } = usePortalMiPerfil();

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-2xl font-semibold text-foreground">Mi información académica</h1>
        <p className="text-sm text-muted-foreground">Consulta tus materias, notas, asistencia, citaciones y licencias.</p>
      </div>

      {isLoading && <p className="text-sm text-muted-foreground">Cargando...</p>}

      {data && <PortalResumenView data={data} />}
    </div>
  );
};
