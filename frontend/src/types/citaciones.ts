export type TipoCitacion = 'padre' | 'profesor_informativa' | 'profesor_extraordinaria';
export type EstadoCitacion = 'enviada' | 'vista';
export type EstadoAsistencia = 'presente' | 'falta' | 'atraso' | 'licencia';

export interface Citacion {
  id: number;
  tipo: TipoCitacion;
  tipo_label: string;
  asunto: string;
  mensaje: string;
  fecha_hora: string;
  lugar: string | null;
  estado: EstadoCitacion;
  estado_label: string;
  leida_en: string | null;
  emisor?: string | null;
  emisor_rol?: string | null;
  destinatario?: string | null;
  estudiante?: { id: number; ci: string | null; nombre_completo: string | null } | null;
  created_at: string | null;
}

export interface Destinatario {
  id: number;
  ci: string;
  nombre_completo: string;
  rol: 'padre' | 'profesor';
  rol_label: string;
  hijos: { id: number; nombre_completo: string | null }[];
}

export interface OpcionCitacion {
  value: string;
  label: string;
}

export interface CitacionesCatalogo {
  data: OpcionCitacion[];
  estados: OpcionCitacion[];
}

export interface AsistenciaPorEstado {
  estado: EstadoAsistencia;
  label: string;
  total: number;
}

export interface DashboardResumen {
  rango: { desde: string; hasta: string; fecha: string | null };
  umbral_notas: number;
  total_estudiantes: number;
  porcentaje_presentes: number;
  total_registros_asistencia: number;
  con_faltas: number;
  con_licencia: number;
  con_notas_bajas: number;
  asistencia_por_estado: AsistenciaPorEstado[];
}
