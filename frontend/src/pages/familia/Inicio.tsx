import { useState } from 'react';
import { Users } from 'lucide-react';
import { useMisHijos } from '../../api/academico';
import { usePortalHijo } from '../../api/portal';
import { PortalResumenView } from '../portal/PortalResumenView';

export const FamiliaInicio = () => {
  const { data: hijos, isLoading } = useMisHijos();
  const [elegido, setElegido] = useState<number | null>(null);

  const seleccionado = elegido ?? hijos?.[0]?.id;

  const { data, isLoading: cargandoPortal } = usePortalHijo(seleccionado);

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-2xl font-semibold text-foreground">Portal de familia</h1>
        <p className="text-sm text-muted-foreground">Consulta la información académica de tus hijos.</p>
      </div>

      {isLoading && <p className="text-sm text-muted-foreground">Cargando...</p>}

      {hijos && hijos.length === 0 && (
        <div className="flex items-center gap-3 rounded-lg border border-border/60 bg-card p-6 text-sm text-muted-foreground shadow-sm">
          <Users size={18} /> No tienes estudiantes a tu cargo.
        </div>
      )}

      {hijos && hijos.length > 1 && (
        <div className="flex flex-wrap gap-2">
          {hijos.map((h) => (
            <button
              key={h.id}
              type="button"
              onClick={() => setElegido(h.id)}
              aria-pressed={seleccionado === h.id}
              className={`rounded-full border px-4 py-1.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50 ${seleccionado === h.id ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground hover:bg-accent hover:text-accent-foreground'}`}
            >
              {h.nombre_completo}
            </button>
          ))}
        </div>
      )}

      {cargandoPortal && <p className="text-sm text-muted-foreground">Cargando información del estudiante...</p>}

      {data && <PortalResumenView data={data} />}
    </div>
  );
};
