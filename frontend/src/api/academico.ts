import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../api/axios';
import { mapPaginado } from '../lib/paginado';
import type { Paginado } from '../types/gestion';
import type { Aula, AulaMateria, Gestion, Horario, Inscripcion, Materia } from '../types/academico';
import type { Actividad, NotaHistorial, Planilla } from '../types/academico';
import type { Asistencia, AsistenciaHistorial, Licencia, PlanillaAsistencia } from '../types/academico';

const limpiar = (params: object): Record<string, unknown> =>
  Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '' && v !== false)
  );

// Gestiones
export const useGestiones = (filtros: { buscar?: string; estado?: string; page?: number; por_pagina?: number }) =>
  useQuery({
    queryKey: ['gestiones', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Gestion>>('/gestiones', { params: limpiar(filtros) });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useCrearGestion = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post('/gestiones', payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['gestiones'] }),
  });
};

export const useActualizarGestion = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: unknown }) => api.put(`/gestiones/${id}`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['gestiones'] }),
  });
};

export const useEliminarGestion = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/gestiones/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['gestiones'] }),
  });
};

// Materias
export const useMaterias = (filtros: { buscar?: string; estado?: string; page?: number; por_pagina?: number }) =>
  useQuery({
    queryKey: ['materias', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Materia>>('/materias', { params: limpiar(filtros) });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useCrearMateria = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post('/materias', payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['materias'] }),
  });
};

export const useActualizarMateria = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: unknown }) => api.put(`/materias/${id}`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['materias'] }),
  });
};

export const useEliminarMateria = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/materias/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['materias'] }),
  });
};

// Aulas
export const useAulas = (filtros: { gestion_id?: number; turno?: string; estado?: string; buscar?: string; page?: number; por_pagina?: number }) =>
  useQuery({
    queryKey: ['aulas', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Aula>>('/aulas', { params: limpiar(filtros) });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useCrearAula = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post('/aulas', payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['aulas'] }),
  });
};

export const useActualizarAula = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: unknown }) => api.put(`/aulas/${id}`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['aulas'] }),
  });
};

export const useEliminarAula = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/aulas/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['aulas'] }),
  });
};

// Materias del aula (con profesor)
export const useAulaMaterias = (aulaId: number | undefined) =>
  useQuery({
    queryKey: ['aula-materias', aulaId],
    queryFn: async () => {
      const { data } = await api.get<{ data: AulaMateria[] }>(`/aulas/${aulaId}/materias`);
      return data.data;
    },
    enabled: !!aulaId,
  });

export const useAsignarMateria = (aulaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { materia_id: number; profesor_id: number }) =>
      api.post(`/aulas/${aulaId}/materias`, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['aula-materias', aulaId] });
      qc.invalidateQueries({ queryKey: ['aulas'] });
    },
  });
};

export const useQuitarMateria = (aulaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (aulaMateriaId: number) => api.delete(`/aulas/${aulaId}/materias/${aulaMateriaId}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['aula-materias', aulaId] });
      qc.invalidateQueries({ queryKey: ['aulas'] });
    },
  });
};

// Inscripciones
export const useInscripciones = (aulaId: number | undefined) =>
  useQuery({
    queryKey: ['inscripciones', aulaId],
    queryFn: async () => {
      const { data } = await api.get<{ data: Inscripcion[] }>(`/aulas/${aulaId}/inscripciones`);
      return data.data;
    },
    enabled: !!aulaId,
  });

export const useInscribir = (aulaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (estudianteId: number) => api.post(`/aulas/${aulaId}/inscripciones`, { estudiante_id: estudianteId }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['inscripciones', aulaId] });
      qc.invalidateQueries({ queryKey: ['aulas'] });
    },
  });
};

export const useRetirarInscripcion = (aulaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: { motivo: string; fecha: string } }) =>
      api.post(`/inscripciones/${id}/retirar`, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['inscripciones', aulaId] });
      qc.invalidateQueries({ queryKey: ['aulas'] });
    },
  });
};

// Horario
export const useHorario = (aulaId: number | undefined) =>
  useQuery({
    queryKey: ['horario', aulaId],
    queryFn: async () => {
      const { data } = await api.get<{ data: Horario[] }>(`/aulas/${aulaId}/horario`);
      return data.data;
    },
    enabled: !!aulaId,
  });

export const useCrearHorario = (aulaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { aula_materia_id: number; dia: string; hora_inicio: string; hora_fin: string }) =>
      api.post(`/aulas/${aulaId}/horario`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['horario', aulaId] }),
  });
};

export const useActualizarHorario = (aulaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: { dia: string; hora_inicio: string; hora_fin: string } }) =>
      api.put(`/horarios/${id}`, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['horario', aulaId] }),
  });
};

export const useEliminarHorario = (aulaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/horarios/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['horario', aulaId] }),
  });
};

// Actividades
export const useActividades = (aulaMateriaId: number | undefined) =>
  useQuery({
    queryKey: ['actividades', aulaMateriaId],
    queryFn: async () => {
      const { data } = await api.get<{ data: Actividad[] }>(`/materias-aulas/${aulaMateriaId}/actividades`);
      return data.data;
    },
    enabled: !!aulaMateriaId,
  });

export const useMisActividades = (filtros: { buscar?: string; page?: number; por_pagina?: number } = {}) =>
  useQuery({
    queryKey: ['mis-actividades', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Actividad>>('/profesor/actividades', { params: limpiar(filtros) });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useCrearActividad = (aulaMateriaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: unknown) => api.post(`/materias-aulas/${aulaMateriaId}/actividades`, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['actividades', aulaMateriaId] });
      qc.invalidateQueries({ queryKey: ['mis-actividades'] });
    },
  });
};

export const useActualizarActividad = (aulaMateriaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: unknown }) => api.put(`/actividades/${id}`, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['actividades', aulaMateriaId] });
      qc.invalidateQueries({ queryKey: ['mis-actividades'] });
    },
  });
};

export const useEliminarActividad = (aulaMateriaId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/actividades/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['actividades', aulaMateriaId] });
      qc.invalidateQueries({ queryKey: ['mis-actividades'] });
    },
  });
};

// Notas
export const usePlanilla = (actividadId: number | undefined) =>
  useQuery({
    queryKey: ['planilla', actividadId],
    queryFn: async () => {
      const { data } = await api.get<{ data: Planilla }>(`/actividades/${actividadId}/planilla`);
      return data.data;
    },
    enabled: !!actividadId,
  });

export const useGuardarPlanilla = (actividadId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (notas: { estudiante_id: number; nota: number }[]) =>
      api.post(`/actividades/${actividadId}/planilla`, { notas }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['planilla', actividadId] });
      qc.invalidateQueries({ queryKey: ['mis-actividades'] });
    },
  });
};

export const useModificarNota = (actividadId: number | undefined) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: { nota: number; observacion: string } }) =>
      api.put(`/notas/${id}`, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['planilla', actividadId] });
      qc.invalidateQueries({ queryKey: ['mis-actividades'] });
    },
  });
};

export const useHistorialNota = (notaId: number | undefined) =>
  useQuery({
    queryKey: ['historial-nota', notaId],
    queryFn: async () => {
      const { data } = await api.get<{ data: NotaHistorial[] }>(`/notas/${notaId}/historial`);
      return data.data;
    },
    enabled: !!notaId,
  });

// Asistencias
export const useMisMateriasAulas = () =>
  useQuery({
    queryKey: ['mis-materias-aulas'],
    queryFn: async () => {
      const { data } = await api.get<{ data: AulaMateria[] }>('/profesor/materias-aulas');
      return data.data;
    },
  });

export const usePlanillaAsistencia = (aulaMateriaId: number | undefined, fecha: string) =>
  useQuery({
    queryKey: ['planilla-asistencia', aulaMateriaId, fecha],
    queryFn: async () => {
      const { data } = await api.get<{ data: PlanillaAsistencia }>(
        `/materias-aulas/${aulaMateriaId}/asistencias/planilla`,
        { params: { fecha } }
      );
      return data.data;
    },
    enabled: !!aulaMateriaId && !!fecha,
  });

export const useRegistrarAsistencia = (aulaMateriaId: number | undefined, fecha: string) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (registros: { estudiante_id: number; estado: string; observacion?: string }[]) =>
      api.post(`/materias-aulas/${aulaMateriaId}/asistencias/toma`, { fecha, registros }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['planilla-asistencia', aulaMateriaId, fecha] }),
  });
};

export const useAsistencias = (aulaMateriaId: number | undefined, fecha?: string) =>
  useQuery({
    queryKey: ['asistencias', aulaMateriaId, fecha],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Asistencia>>(`/materias-aulas/${aulaMateriaId}/asistencias`, {
        params: limpiar({ fecha }),
      });
      return data;
    },
    enabled: !!aulaMateriaId,
  });

export const useModificarAsistencia = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: { estado: string; observacion?: string } }) =>
      api.put(`/asistencias/${id}`, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['asistencias'] });
      qc.invalidateQueries({ queryKey: ['planilla-asistencia'] });
    },
  });
};

export const useHistorialAsistencia = (asistenciaId: number | undefined) =>
  useQuery({
    queryKey: ['historial-asistencia', asistenciaId],
    queryFn: async () => {
      const { data } = await api.get<{ data: AsistenciaHistorial[] }>(`/asistencias/${asistenciaId}/historial`);
      return data.data;
    },
    enabled: !!asistenciaId,
  });

// Licencias
export const useMisHijos = () =>
  useQuery({
    queryKey: ['mis-hijos'],
    queryFn: async () => {
      const { data } = await api.get<{ data: { id: number; ci: string; nombre_completo: string }[] }>('/mis-hijos');
      return data.data;
    },
  });

export const useLicencias = (filtros: { estado?: string; estudiante_id?: number; page?: number; por_pagina?: number }) =>
  useQuery({
    queryKey: ['licencias', filtros],
    queryFn: async () => {
      const { data } = await api.get<Paginado<Licencia>>('/licencias', { params: limpiar(filtros) });
      return data;
    },
    placeholderData: keepPreviousData,
  });

export const useSolicitarLicencia = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { estudiante_id: number; fecha_inicio: string; fecha_fin: string; motivo: string }) =>
      api.post('/licencias', payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['licencias'] });
      qc.invalidateQueries({ queryKey: ['tablero'] });
      qc.invalidateQueries({ queryKey: ['portal-mi-perfil'] });
      qc.invalidateQueries({ queryKey: ['portal-hijo'] });
    },
  });
};

export const useAprobarLicencia = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.post(`/licencias/${id}/aprobar`),
    onMutate: async (id) => {
      await qc.cancelQueries({ queryKey: ['licencias'] });
      const previos = qc.getQueriesData({ queryKey: ['licencias'] });
      qc.setQueriesData({ queryKey: ['licencias'] }, (old) =>
        mapPaginado<Licencia>(old, (items) =>
          items.map((l) =>
            l.id === id ? { ...l, estado: 'aprobada', estado_label: 'Aprobada' } : l
          )
        )
      );
      return { previos };
    },
    onError: (_err, _id, ctx) => {
      ctx?.previos.forEach(([key, valor]) => qc.setQueryData(key, valor));
    },
    onSettled: () => {
      qc.invalidateQueries({ queryKey: ['licencias'] });
      qc.invalidateQueries({ queryKey: ['tablero'] });
      qc.invalidateQueries({ queryKey: ['portal-mi-perfil'] });
      qc.invalidateQueries({ queryKey: ['portal-hijo'] });
    },
  });
};

export const useRechazarLicencia = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, motivo_rechazo }: { id: number; motivo_rechazo: string }) =>
      api.post(`/licencias/${id}/rechazar`, { motivo_rechazo }),
    onMutate: async ({ id, motivo_rechazo }) => {
      await qc.cancelQueries({ queryKey: ['licencias'] });
      const previos = qc.getQueriesData({ queryKey: ['licencias'] });
      qc.setQueriesData({ queryKey: ['licencias'] }, (old) =>
        mapPaginado<Licencia>(old, (items) =>
          items.map((l) =>
            l.id === id
              ? { ...l, estado: 'rechazada', estado_label: 'Rechazada', motivo_rechazo }
              : l
          )
        )
      );
      return { previos };
    },
    onError: (_err, _vars, ctx) => {
      ctx?.previos.forEach(([key, valor]) => qc.setQueryData(key, valor));
    },
    onSettled: () => {
      qc.invalidateQueries({ queryKey: ['licencias'] });
      qc.invalidateQueries({ queryKey: ['tablero'] });
      qc.invalidateQueries({ queryKey: ['portal-mi-perfil'] });
      qc.invalidateQueries({ queryKey: ['portal-hijo'] });
    },
  });
};

export const useAnularLicencia = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.delete(`/licencias/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['licencias'] });
      qc.invalidateQueries({ queryKey: ['tablero'] });
      qc.invalidateQueries({ queryKey: ['portal-mi-perfil'] });
      qc.invalidateQueries({ queryKey: ['portal-hijo'] });
    },
  });
};
