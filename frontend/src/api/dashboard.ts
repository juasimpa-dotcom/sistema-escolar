import { useQuery } from '@tanstack/react-query';
import api from './axios';
import { useAuth } from '@/contexts/AuthContext';

// ---------------------------------------------------------------
// Tipos del tablero por rol (GET /api/v1/dashboard)
// ---------------------------------------------------------------

export interface ResumenAsistencia {
  total: number;
  presentes: number;
  faltas: number;
  licencias: number;
  licencias_aprobadas: number;
  atrasos: number;
  porcentaje: number;
}

export interface FiltrosTablero {
  macrodistrito_id?: number | '';
  distrito_municipal_id?: number | '';
  umbral?: number | '';
}

export interface TableroSuperadmin {
  filtros_territoriales: {
    macrodistrito_id: number | null;
    distrito_municipal_id: number | null;
  };
  totales: {
    unidades: number;
    estudiantes: number;
    docentes: number;
  };
  asistencia_hoy: {
    fecha: string;
    total: number;
    presentes: number;
    porcentaje: number;
    por_estado: { estado: string; label: string; total: number }[];
  };
  matricula_por_macrodistrito: { codigo: string; nombre: string; total: number }[];
  estudiantes_por_sexo: { clave: string; label: string; total: number }[];
  matricula_por_nivel: { nivel: string; label: string; total: number }[];
  unidades_alerta: {
    id: number;
    nombre: string;
    codigo: string;
    macrodistrito: string | null;
    faltas: number;
    abandono: number;
    alerta: number;
  }[];
  ultimas_unidades: {
    id: number;
    nombre: string;
    codigo: string;
    tipo: string | null;
    zona: string | null;
    macrodistrito: string | null;
    creada: string | null;
  }[];
}

export interface TableroDirector {
  totales: {
    estudiantes: number;
    presentes_hoy: number;
    porcentaje_presentes_hoy: number;
    registros_hoy: number;
    faltas_hoy: number;
    atrasos_hoy: number;
    licencias_hoy: number;
    notas_bajas: number;
  };
  umbral_notas: number;
  licencias_pendientes: {
    total: number;
    items: {
      id: number;
      estudiante: string;
      fecha_inicio: string | null;
      fecha_fin: string | null;
      motivo: string;
    }[];
  };
  tendencia: { fecha: string; faltas: number; atrasos: number }[];
  riesgo: {
    total: number;
    items: { estudiante_id: number; nombre: string; aula: string; promedio: number }[];
  };
  citaciones_recientes: {
    total: number;
    items: {
      id: number;
      asunto: string;
      tipo: string;
      fecha_hora: string | null;
      estado: string;
      destinatario: string;
    }[];
  };
  profesores_ausentes: {
    total: number;
    items: { nombre: string; estado: string; observacion: string | null }[];
  };
}

export interface TableroAdministrativo {
  gestion_actual: number | null;
  aulas_sin_profesor: {
    total: number;
    items: { id: number; nombre: string; nivel: string | null; turno: string | null }[];
  };
  materias_sin_horario: { total: number; items: { id: number; nombre: string }[] };
  actividades_semana: {
    total: number;
    items: {
      id: number;
      titulo: string;
      fecha: string | null;
      vencida: boolean;
      aula: string;
      materia: string | null;
    }[];
  };
  estudiantes_sin_inscribir: { total: number; items: { id: number; nombre: string }[] };
  citaciones: { enviadas: number; sin_leer: number; vistas: number };
}

export interface TableroProfesor {
  dia: string | null;
  fecha: string;
  horario_hoy: {
    total: number;
    items: { id: number; hora_inicio: string; hora_fin: string; aula: string; materia: string }[];
  };
  asistencias_pendientes: {
    total: number;
    items: { aula_materia_id: number; aula: string; materia: string }[];
  };
  actividades: {
    total: number;
    vencidas: number;
    notas_faltantes: number;
    items: {
      id: number;
      titulo: string;
      fecha: string | null;
      fecha_limite: string | null;
      vencida: boolean;
      aula: string;
      materia: string;
      notas_faltantes: number;
    }[];
  };
  mis_aulas: { total: number; items: { id: number; nombre: string; nivel: string | null }[] };
  mis_materias: { total: number; items: { id: number; nombre: string; aulas: number }[] };
}

export interface TarjetaHijo {
  estudiante_id: number;
  nombre: string;
  aula: string;
  nivel: string | null;
  asistencia_mes: ResumenAsistencia;
  ultimas_notas: {
    actividad: string;
    nota: number;
    puntaje_maximo: number | null;
    fecha: string | null;
  }[];
  actividades_pendientes: number;
  licencias_en_curso: number;
  citaciones_sin_leer: number;
}

export interface TableroPadre {
  totales: {
    hijos: number;
    citaciones_sin_leer: number;
    licencias_en_curso: number;
  };
  hijos: TarjetaHijo[];
}

export interface TableroEstudiante {
  dia: string | null;
  fecha: string;
  aula: string | null;
  horario_hoy: {
    total: number;
    items: {
      id: number;
      hora_inicio: string;
      hora_fin: string;
      materia: string;
      profesor: string;
    }[];
  };
  proximas_actividades: {
    total: number;
    items: { id: number; titulo: string; fecha: string | null; materia: string; sin_nota: boolean }[];
  };
  ultimas_notas: {
    total: number;
    items: {
      actividad: string;
      nota: number;
      puntaje_maximo: number | null;
      fecha: string | null;
    }[];
  };
  asistencia_mes: ResumenAsistencia;
}

export type DatosTablero =
  | TableroSuperadmin
  | TableroDirector
  | TableroAdministrativo
  | TableroProfesor
  | TableroPadre
  | TableroEstudiante;

export interface Tablero<T extends DatosTablero = DatosTablero> {
  rol: string;
  generado_en: string;
  filtros: Record<string, unknown>;
  datos: T;
}

const limpiar = (params: object): Record<string, unknown> =>
  Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '' && v !== false)
  );

// ---------------------------------------------------------------
// Tablero de inicio por rol
// ---------------------------------------------------------------

export const useTablero = <T extends DatosTablero>(filtros: FiltrosTablero = {}) => {
  const { user } = useAuth();

  return useQuery({
    queryKey: ['tablero', user?.id, filtros],
    queryFn: async () => {
      const { data } = await api.get<{ data: Tablero<T> }>('/dashboard', {
        params: limpiar(filtros),
      });
      return data.data;
    },
    enabled: !!user,
    staleTime: 30 * 1000,
  });
};

/** Atajo para leer los datos de un rol concreto sin casts en cada pantalla. */
export const useDatosTablero = <T extends DatosTablero>(filtros: FiltrosTablero = {}) => {
  const { data, isLoading, isError, error, refetch, isFetching } = useTablero<T>(filtros);

  return {
    datos: data?.datos as T | undefined,
    rol: data?.rol,
    generadoEn: data?.generado_en,
    isLoading,
    isFetching,
    isError,
    error,
    refetch,
  };
};
