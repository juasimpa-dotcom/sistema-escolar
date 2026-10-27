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

export type TipoUnidad = 'fiscal' | 'convenio' | 'privada';
export type TipoMacrodistrito = 'urbano' | 'rural';

export interface Macrodistrito {
  id: number;
  municipio_id: number;
  codigo: string;
  nombre: string;
  tipo: TipoMacrodistrito;
  distritos_municipales_count?: number;
  unidades_educativas_count?: number;
}

export interface DistritoMunicipal {
  id: number;
  macrodistrito_id: number | null;
  numero: number;
  nombre: string | null;
  macrodistrito?: Pick<Macrodistrito, 'id' | 'codigo' | 'nombre' | 'tipo'> | null;
}

export interface Unidad {
  id: number;
  nombre: string;
  codigo: string;
  direccion: string | null;
  zona: string | null;
  macrodistrito_id: number | null;
  distrito_municipal_id: number | null;
  tipo: TipoUnidad;
  telefono: string | null;
  estado: EstadoUnidad;
  fecha_baja: string | null;
  motivo_baja: string | null;
  macrodistrito: Pick<Macrodistrito, 'id' | 'codigo' | 'nombre' | 'tipo'> | null;
  distrito: { id: number; numero: number; nombre: string | null; macrodistrito_id: number | null } | null;
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

export interface FiltrosUnidades extends FiltrosBase {
  macrodistrito_id?: number | string;
  distrito_municipal_id?: number | string;
  tipo?: TipoUnidad | '';
}

export interface FiltrosCatalogo extends FiltrosBase {
  macrodistrito_id?: number | string;
  tipo?: string;
}
