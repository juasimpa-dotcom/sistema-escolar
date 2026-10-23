import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../api/axios';
import { mapPaginado } from '../lib/paginado';
import type { Paginado } from '../types/gestion';
import type {
  Citacion,
  CitacionesCatalogo,
  DashboardResumen,
  Destinatario,
  TipoCitacion,
} from '../types/citaciones';

const limpiar = (params: object): Record<string, unknown> =>
  Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '' && v !== false)
  );

// Citaciones
export const useCitaciones = (filtros: {
  bandeja?: 'enviadas' | 'recibidas';
  estado?: string;
  tipo?: string;
  page?: number;
  por_pagina?: number;
}) =>
  useQuery({
    queryKey: ['citaciones', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Citacion>>('/citaciones', { params: limpiar(filtros) });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useDestinatarios = () =>
  useQuery({
    queryKey: ['citaciones-destinatarios'],
    queryFn: async () => {
      const { data } = await api.get<{ data: Destinatario[] }>('/citaciones/destinatarios');
      return data.data;
    },
  });

export const useCatalogoCitaciones = () =>
  useQuery({
    queryKey: ['citaciones-catalogo'],
    queryFn: async () => {
      const { data } = await api.get<CitacionesCatalogo>('/citaciones/tipos');
      return data;
    },
  });

export const useCrearCitacion = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: {
      destinatario_id: number;
      estudiante_id?: number | null;
      tipo: TipoCitacion;
      asunto: string;
      mensaje: string;
      fecha_hora: string;
      lugar?: string | null;
    }) => api.post('/citaciones', payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['citaciones'] }),
  });
};

export const useMarcarVistaCitacion = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.post(`/citaciones/${id}/marcar-vista`),
    onMutate: async (id) => {
      await qc.cancelQueries({ queryKey: ['citaciones'] });
      const previos = qc.getQueriesData({ queryKey: ['citaciones'] });
      qc.setQueriesData({ queryKey: ['citaciones'] }, (old) =>
        mapPaginado<Citacion>(old, (items) =>
          items.map((c) =>
            c.id === id
              ? { ...c, estado: 'vista', estado_label: 'Vista', leida_en: new Date().toISOString() }
              : c
          )
        )
      );
      return { previos };
    },
    onError: (_err, _id, ctx) => {
      ctx?.previos.forEach(([key, valor]) => qc.setQueryData(key, valor));
    },
    onSettled: () => qc.invalidateQueries({ queryKey: ['citaciones'] }),
  });
};

export const useAnularCitacion = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/citaciones/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['citaciones'] }),
  });
};

// Dashboard
export const useDashboardResumen = (filtros: {
  fecha?: string;
  fecha_desde?: string;
  fecha_hasta?: string;
  aula_id?: number;
  umbral?: number;
}) =>
  useQuery({
    queryKey: ['dashboard-resumen', filtros],
    queryFn: async () => {
      const { data } = await api.get<{ data: DashboardResumen }>('/dashboard/resumen', {
        params: limpiar(filtros),
      });
      return data.data;
    },
    staleTime: 60_000,
    placeholderData: keepPreviousData,
  });
