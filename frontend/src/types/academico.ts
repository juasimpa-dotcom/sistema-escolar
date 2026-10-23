export type Turno = 'manana' | 'tarde' | 'noche';
export type EstadoGenerico = 'activo' | 'inactivo';
export type EstadoGestion = 'activa' | 'cerrada';
export type EstadoInscripcion = 'inscrito' | 'retirado';
export type DiaSemana = 'lunes' | 'martes' | 'miercoles' | 'jueves' | 'viernes' | 'sabado';

export interface Gestion {
  id: number;
  unidad_educativa_id: number;
  anio: number;
  fecha_inicio: string | null;
  fecha_fin: string | null;
  es_actual: boolean;
  estado: EstadoGestion;
  estado_label: string;
  aulas_count?: number;
}

export interface Materia {
  id: number;
  unidad_educativa_id: number;
  nombre: string;
  estado: EstadoGenerico;
  estado_label: string;
}

export interface Aula {
  id: number;
  unidad_educativa_id: number;
  gestion_id: number;
  gestion?: { id: number; anio: number };
  grado: string;
  paralelo: string;
  nombre: string;
  turno: Turno;
  turno_label: string;
  estado: EstadoGenerico;
  estado_label: string;
  materias_count?: number;
  inscritos_count?: number;
}

export interface Horario {
  id: number;
  aula_materia_id: number;
  dia: DiaSemana;
  dia_label: string;
  hora_inicio: string;
  hora_fin: string;
  materia?: string;
}

export interface AulaMateria {
  id: number;
  aula_id: number;
  materia_id: number;
  materia?: { id: number; nombre: string };
  profesor_id: number;
  profesor?: { id: number; nombre_completo: string };
  aula?: { id: number; nombre: string };
  horarios?: Horario[];
}

export interface Inscripcion {
  id: number;
  estudiante_id: number;
  estudiante?: { id: number; nombre_completo: string; ci: string };
  aula_id: number;
  gestion_id: number;
  fecha_inscripcion: string | null;
  estado: EstadoInscripcion;
  estado_label: string;
  fecha_baja: string | null;
  motivo_baja: string | null;
}

export interface Actividad {
  id: number;
  aula_materia_id: number;
  titulo: string;
  descripcion: string | null;
  fecha_cumplimiento: string | null;
  fecha_limite_notas: string | null;
  puntaje_maximo: number;
  aula_materia?: {
    id: number;
    aula_id: number;
    materia?: string;
    profesor?: string;
    aula?: string;
  };
  notas_count?: number;
}

export interface Nota {
  id: number;
  actividad_id: number;
  estudiante_id: number;
  estudiante?: { id: number; ci: string; nombre_completo: string };
  nota: number;
  observacion: string | null;
  fue_modificada: boolean;
  registrado_por?: string | null;
  modificado_por?: string | null;
  historial?: NotaHistorial[];
}

export interface NotaHistorial {
  id: number;
  nota_anterior: number;
  nota_nueva: number;
  observacion: string | null;
  usuario?: string | null;
  created_at: string | null;
}

export interface FilaPlanilla {
  estudiante_id: number;
  ci: string | null;
  nombre_completo: string | null;
  nota_id: number | null;
  nota: number | null;
  fue_modificada: boolean;
}

export interface Planilla {
  actividad: Actividad;
  filas: FilaPlanilla[];
}

export type EstadoAsistencia = 'presente' | 'falta' | 'atraso' | 'licencia';
export type EstadoLicencia = 'pendiente' | 'aprobada' | 'rechazada';

export interface FilaAsistencia {
  estudiante_id: number;
  ci: string | null;
  nombre_completo: string | null;
  asistencia_id: number | null;
  estado: EstadoAsistencia | null;
  observacion: string | null;
  tiene_licencia: boolean;
}

export interface PlanillaAsistencia {
  aula_materia: { id: number; materia: string | null; profesor: string | null; aula: string | null };
  fecha: string;
  filas: FilaAsistencia[];
}

export interface AsistenciaHistorial {
  id: number;
  estado_anterior: EstadoAsistencia;
  estado_anterior_label: string;
  estado_nuevo: EstadoAsistencia;
  estado_nuevo_label: string;
  observacion: string | null;
  usuario: string | null;
  created_at: string | null;
}

export interface Asistencia {
  id: number;
  aula_materia_id: number;
  estudiante_id: number;
  estudiante?: { id: number; ci: string; nombre_completo: string };
  fecha: string;
  estado: EstadoAsistencia;
  estado_label: string;
  observacion: string | null;
  registrado_por?: string | null;
  modificado_por?: string | null;
  historial?: AsistenciaHistorial[];
}

export interface Licencia {
  id: number;
  estudiante_id: number;
  estudiante?: { id: number; ci: string; nombre_completo: string };
  padre?: string | null;
  fecha_inicio: string;
  fecha_fin: string;
  motivo: string;
  estado: EstadoLicencia;
  estado_label: string;
  resuelto_por?: string | null;
  fecha_resolucion: string | null;
  motivo_rechazo: string | null;
  created_at: string | null;
}
