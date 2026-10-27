import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from './axios';
import type { DistritoMunicipal, FiltrosCatalogo, Macrodistrito, Paginado } from '../types/gestion';

const limpiar = (params: object): Record<string, unknown> =>
  Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '' && v !== false)
  );

// ---------------------------------------------------------------
// Catálogos planos (filtros de dashboard, reportes y formularios)
// ---------------------------------------------------------------

export const useCatalogoMacrodistritos = () =>
  useQuery({
    queryKey: ['catalogo', 'macrodistritos'],
    queryFn: async () => {
      const { data } = await api.get<Macrodistrito[]>('/catalogos/macrodistritos');
      return data;
    },
    staleTime: 5 * 60 * 1000,
  });

export const useCatalogoDistritos = (macrodistritoId?: number | string) =>
  useQuery({
    queryKey: ['catalogo', 'distritos', macrodistritoId || null],
    enabled: !!macrodistritoId,
    queryFn: async () => {
      const { data } = await api.get<DistritoMunicipal[]>('/catalogos/distritos', {
        params: limpiar({ macrodistrito_id: macrodistritoId }),
      });
      return data;
    },
    staleTime: 5 * 60 * 1000,
  });

// ---------------------------------------------------------------
// CRUD de macrodistritos (Superadmin)
// ---------------------------------------------------------------

export const useMacrodistritos = (filtros: FiltrosCatalogo) =>
  useQuery({
    queryKey: ['macrodistritos', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Macrodistrito>>('/macrodistritos', {
        params: limpiar(filtros),
      });
      return data;
    },
    placeholderData: keepPreviousData,
  });

const invalidarCatalogos = (qc: ReturnType<typeof useQueryClient>) => {
  qc.invalidateQueries({ queryKey: ['macrodistritos'] });
  qc.invalidateQueries({ queryKey: ['catalogo'] });
  qc.invalidateQueries({ queryKey: ['unidades'] });
};

export const useCrearMacrodistrito = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post('/macrodistritos', payload),
    onSuccess: () => invalidarCatalogos(qc),
  });
};

export const useActualizarMacrodistrito = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: unknown }) =>
      api.put(`/macrodistritos/${id}`, payload),
    onSuccess: () => invalidarCatalogos(qc),
  });
};

export const useEliminarMacrodistrito = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/macrodistritos/${id}`),
    onSuccess: () => invalidarCatalogos(qc),
  });
};

// ---------------------------------------------------------------
// CRUD de distritos municipales (Superadmin)
// ---------------------------------------------------------------

export const useDistritosMunicipales = (filtros: FiltrosCatalogo) =>
  useQuery({
    queryKey: ['distritos-municipales', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<DistritoMunicipal>>('/distritos', {
        params: limpiar(filtros),
      });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useCrearDistrito = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post('/distritos', payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['distritos-municipales'] });
      qc.invalidateQueries({ queryKey: ['catalogo'] });
      qc.invalidateQueries({ queryKey: ['macrodistritos'] });
      qc.invalidateQueries({ queryKey: ['unidades'] });
    },
  });
};

export const useActualizarDistrito = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: unknown }) =>
      api.put(`/distritos/${id}`, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['distritos-municipales'] });
      qc.invalidateQueries({ queryKey: ['catalogo'] });
      qc.invalidateQueries({ queryKey: ['unidades'] });
    },
  });
};

export const useEliminarDistrito = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/distritos/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['distritos-municipales'] });
      qc.invalidateQueries({ queryKey: ['catalogo'] });
      qc.invalidateQueries({ queryKey: ['unidades'] });
    },
  });
};
