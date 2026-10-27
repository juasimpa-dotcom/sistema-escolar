import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../api/axios';
import type { Estudiante, FiltrosBase, FiltrosUnidades, Paginado, Persona, Unidad } from '../types/gestion';
const limpiar = (params: object): Record<string, unknown> =>
  Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '' && v !== false)
  );

export const useUnidades = (filtros: FiltrosUnidades) =>
  useQuery({
    queryKey: ['unidades', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Unidad>>('/unidades', {
        params: limpiar(filtros),
      });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useCrearUnidad = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post('/unidades', payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['unidades'] }),
  });
};

export const useActualizarUnidad = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: unknown }) => api.put(`/unidades/${id}`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['unidades'] }),
  });
};

export const useBajaUnidad = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: { motivo: string; fecha: string } }) =>
      api.post(`/unidades/${id}/baja`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['unidades'] }),
  });
};

// Personas (profesores, administrativos, padres)
export const usePersonas = (filtros: FiltrosBase & { rol?: string }) =>
  useQuery({
    queryKey: ['personas', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Persona>>('/personas', { params: limpiar(filtros) });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useCrearPersona = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post('/personas', payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['personas'] }),
  });
};

export const useBajaPersona = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: { motivo: string; fecha: string } }) =>
      api.post(`/personas/${id}/baja`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['personas'] }),
  });
};

// Estudiantes
export const useEstudiantes = (filtros: FiltrosBase) =>
  useQuery({
    queryKey: ['estudiantes', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Estudiante>>('/estudiantes', { params: limpiar(filtros) });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useCrearEstudiante = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post('/estudiantes', payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['estudiantes'] });
      qc.invalidateQueries({ queryKey: ['personas'] });
    },
  });
};

export const useBajaEstudiante = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: { motivo: string; fecha: string } }) =>
      api.post(`/estudiantes/${id}/baja`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['estudiantes'] }),
  });
};
