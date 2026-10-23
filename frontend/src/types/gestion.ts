import type { Rol, UnidadEducativa } from './auth';

export type EstadoUsuario = 'activo' | 'baja';
export type EstadoUnidad = 'activa' | 'inactiva';

export interface Paginado<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface Persona {
  id: number;
  unidad_educativa_id: number | null;
  rol: Rol;
  rol_label: string;
  ci: string;
  usuario: string;
  nombres: string;
  apellidos: string;
  nombre_completo: string;
  email: string | null;
  telefono: string | null;
  estado: EstadoUsuario;
  fecha_baja: string | null;
  motivo_baja: string | null;
  unidad_educativa?: UnidadEducativa | null;
}

export interface Padre {
  id: number;
  ci: string;
  nombre_completo: string;
  telefono: string | null;
  parentesco: string;
}

export interface Estudiante {
  id: number;
  user_id: number;
  unidad_educativa_id: number;
  ci: string;
  nombres: string;
  apellidos: string;
  nombre_completo: string;
  email: string | null;
  telefono: string | null;
  fecha_nacimiento: string | null;
  estado: EstadoUsuario;
  fecha_baja: string | null;
  motivo_baja: string | null;
  padres?: Padre[];
}

export interface Unidad {
  id: number;
  nombre: string;
  codigo: string;
  direccion: string | null;
  telefono: string | null;
  estado: EstadoUnidad;
  fecha_baja: string | null;
  motivo_baja: string | null;
  director: {
    id: number;
    ci: string;
    nombre_completo: string;
    email: string | null;
    telefono: string | null;
  } | null;
  created_at: string | null;
}

export interface FiltrosBase {
  buscar?: string;
  estado?: string;
  por_pagina?: number;
  page?: number;
  incluir_baja?: boolean;
}
