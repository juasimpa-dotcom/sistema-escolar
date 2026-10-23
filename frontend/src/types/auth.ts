export type Rol = 'superadmin' | 'director' | 'administrativo' | 'profesor' | 'padre' | 'estudiante';

export interface UnidadEducativa {
  id: number;
  nombre: string;
  codigo: string;
}

export interface Usuario {
  id: number;
  unidad_educativa_id: number | null;
  rol: Rol;
  ci: string;
  usuario: string;
  nombres: string;
  apellidos: string;
  nombre_completo: string;
  email: string | null;
  telefono: string | null;
  debe_cambiar_password: boolean;
  estado: string;
  unidad_educativa?: UnidadEducativa | null;
}
