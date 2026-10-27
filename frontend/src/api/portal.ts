import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../api/axios';
import type { NotificacionesRespuesta, PortalResumen } from '../types/portal';
import { useAuth } from '@/contexts/AuthContext';

// Portal de familia / estudiante
export const usePortalHijo = (estudianteId: number | undefined) =>
  useQuery({
    queryKey: ['portal-hijo', estudianteId],
    queryFn: async () => {
      const { data } = await api.get<{ data: PortalResumen }>(`/portal/hijo/${estudianteId}`);
      return data.data;
    },
    enabled: !!estudianteId,
  });

export const usePortalMiPerfil = () => {
  const { user } = useAuth();

  return useQuery({
    queryKey: ['portal-mi-perfil', user?.id],
    queryFn: async () => {
      const { data } = await api.get<{ data: PortalResumen }>('/portal/mi-perfil');
      return data.data;
    },
    enabled: !!user,
  });
};

// Notificaciones en el sistema
export const useNotificaciones = () => {
  const { user } = useAuth();

  return useQuery({
    queryKey: ['notificaciones', user?.id],
    queryFn: async () => {
      const { data } = await api.get<NotificacionesRespuesta>('/notificaciones');
      return data;
    },
    enabled: !!user,
    refetchInterval: 30000,
  });
};

export const useMarcarNotificacionLeida = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => api.post(`/notificaciones/${id}/marcar-leida`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['notificaciones'] }),
  });
};

export const useMarcarTodasLeidas = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => api.post('/notificaciones/marcar-todas-leidas'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['notificaciones'] }),
  });
};
