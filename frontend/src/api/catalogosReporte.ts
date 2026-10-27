import { useQuery } from '@tanstack/react-query';
import api from './axios';

export interface OpcionTerritorial { id:number; codigo?:string; nombre:string; numero?:number|null; macrodistrito_id?:number|null }
export interface OpcionUnidad { id:number; nombre:string }
export interface OpcionGestion { id:number; anio:number; es_actual:boolean }
export interface OpcionAula { id:number; grado:string; paralelo:string; nivel:string|null; gestion?:{id:number;anio:number} }
export interface OpcionEstudiante { id:number; user?:{nombres:string;apellidos:string} }
export interface OpcionPeriodo { id:number; gestion_id:number; nombre:string }

const LARGO = 60 * 60 * 1000;

export const useCatalogoMacrodistritosReporte = (enabled=true) => useQuery({
  queryKey:['catalogo-reportes','macrodistritos'], enabled, queryFn:async()=> (await api.get<OpcionTerritorial[]>('/catalogos/macrodistritos')).data, staleTime:LARGO,
});
export const useCatalogoDistritosReporte = (macrodistritoId:string) => useQuery({
  queryKey:['catalogo-reportes','distritos',macrodistritoId], enabled:Boolean(macrodistritoId),
  queryFn:async()=> (await api.get<OpcionTerritorial[]>('/catalogos/distritos',{params:{macrodistrito_id:macrodistritoId}})).data, staleTime:LARGO,
});
export const useCatalogoUnidadesReporte = (macrodistritoId:string,distritoId:string) => useQuery({
  queryKey:['catalogo-reportes','unidades',macrodistritoId,distritoId], enabled:Boolean(macrodistritoId||distritoId),
  queryFn:async()=> (await api.get<OpcionUnidad[]>('/unidades-educativas',{params:{macrodistrito_id:macrodistritoId||undefined,distrito_id:distritoId||undefined}})).data, staleTime:LARGO,
});
export const useCatalogoGestionesReporte = (enabled=true) => useQuery({
  queryKey:['catalogo-reportes','gestiones'], enabled, queryFn:async()=> (await api.get<OpcionGestion[]>('/catalogos/gestiones')).data, staleTime:LARGO,
});
export const useCatalogoAulasReporte = (enabled=true) => useQuery({
  queryKey:['catalogo-reportes','aulas'], enabled, queryFn:async()=> (await api.get<OpcionAula[]>('/catalogos/aulas')).data, staleTime:LARGO,
});
export const useCatalogoEstudiantesReporte = (enabled=true) => useQuery({
  queryKey:['catalogo-reportes','estudiantes'], enabled, queryFn:async()=> (await api.get<OpcionEstudiante[]>('/catalogos/estudiantes')).data, staleTime:LARGO,
});
export const useCatalogoPeriodosReporte = (enabled=true) => useQuery({
  queryKey:['catalogo-reportes','periodos'], enabled, queryFn:async()=> (await api.get<OpcionPeriodo[]>('/catalogos/periodos')).data, staleTime:LARGO,
});
