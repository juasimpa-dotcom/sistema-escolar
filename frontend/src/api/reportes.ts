import api from './axios';

export interface ReporteDef { slug: string; titulo: string; descripcion: string; filtros: string[] }
export async function obtenerReportes() { return (await api.get<{data:ReporteDef[]}>('/reportes')).data.data; }
export async function previsualizarReporte(slug:string, filtros:Record<string,string>, page=1) {
  return (await api.get(`/reportes/${slug}/preview`, {params:{...filtros,page}})).data as {data:Record<string,unknown>[];meta:{total:number;current_page:number;last_page:number};graficos:Record<string,unknown>[];filtros:Record<string,string>};
}
export async function exportarReporte(slug:string, formato:'pdf'|'excel', filtros:Record<string,string>) {
  const r=await api.get(`/reportes/${slug}/exportar/${formato}`,{params:filtros,responseType:'blob'});
  const url=URL.createObjectURL(r.data); const a=document.createElement('a');a.href=url;a.download=`${slug}.${formato==='excel'?'xlsx':'pdf'}`;a.click();URL.revokeObjectURL(url);
}
export async function registrarDato(tipo:string, payload:Record<string,unknown>) { return api.post(`/reportes/registros/${tipo}`,payload); }
