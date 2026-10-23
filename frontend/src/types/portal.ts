export interface PortalEstudiante {
  id: number;
  ci: string | null;
  nombre_completo: string | null;
  fecha_nacimiento: string | null;
}

export interface PortalAula {
  id: number;
  nombre: string;
  grado: string;
  paralelo: string;
  turno: string | null;
  turno_label: string | null;
  gestion: number | null;
}

export interface PortalActividad {
  actividad_id: number;
  titulo: string;
  descripcion: string | null;
  fecha_cumplimiento: string | null;
  puntaje_maximo: number;
  nota: number | null;
  fue_modificada: boolean;
  registrada: boolean;
}

export interface PortalMateria {
  aula_materia_id: number;
  materia: string | null;
  profesor: string | null;
  promedio: number | null;
  actividades: PortalActividad[];
}

export interface PortalPendiente {
  actividad_id: number;
  titulo: string;
  materia: string | null;
  fecha_cumplimiento: string | null;
  puntaje_maximo: number;
}

export interface PortalAsistenciaDetalle {
  id: number;
  fecha: string | null;
  estado: string;
  estado_label: string;
  materia: string | null;
  observacion: string | null;
}

export interface PortalAsistencia {
  total: number;
  presentes: number;
  faltas: number;
  atrasos: number;
  licencias: number;
  detalle: PortalAsistenciaDetalle[];
}

export interface PortalCitacion {
  id: number;
  tipo_label: string;
  asunto: string;
  mensaje: string;
  fecha_hora: string | null;
  lugar: string | null;
  estado: string;
  estado_label: string;
  emisor: string | null;
}

export interface PortalLicencia {
  id: number;
  fecha_inicio: string | null;
  fecha_fin: string | null;
  motivo: string;
  estado: string;
  estado_label: string;
  motivo_rechazo: string | null;
}

export interface PortalResumen {
  estudiante: PortalEstudiante;
  aula: PortalAula | null;
  materias: PortalMateria[];
  pendientes: PortalPendiente[];
  asistencia: PortalAsistencia;
  citaciones: PortalCitacion[];
  licencias: PortalLicencia[];
}

export interface Notificacion {
  id: string;
  tipo: string;
  titulo: string;
  mensaje: string;
  url: string | null;
  datos: Record<string, unknown>;
  leida: boolean;
  leida_en: string | null;
  created_at: string | null;
}

export interface NotificacionesRespuesta {
  data: Notificacion[];
  no_leidas: number;
}
