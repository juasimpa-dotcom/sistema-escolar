import { useEffect, useMemo, useState } from 'react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { CalendarDays, Check, ChevronsUpDown, Search } from 'lucide-react';
import type { DateRange } from 'react-day-picker';
import { EmptyState } from '@/components/ui/EmptyState';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import {
  useCatalogoAulasReporte, useCatalogoDistritosReporte, useCatalogoEstudiantesReporte,
  useCatalogoGestionesReporte, useCatalogoMacrodistritosReporte, useCatalogoPeriodosReporte,
  useCatalogoUnidadesReporte,
} from '@/api/catalogosReporte';

export interface FiltrosReporteProps {
  filtros: string[];
  value: Record<string,string>;
  onChange: (value: Record<string,string>) => void;
  rol?: string;
}

const nombres:Record<string,string>={macrodistrito_id:'Macrodistrito',distrito_id:'Distrito municipal',unidad_id:'Unidad educativa',tipo:'Tipo de unidad',nivel:'Nivel',gestion:'Gestión',sexo:'Género',desde:'Rango de fechas',aula_id:'Aula',estudiante_id:'Estudiante',periodo_id:'Periodo',agrupar:'Agrupar',umbral:'Umbral'};
const vacio='__todos__';

function Campo({label,children}:{label:string;children:React.ReactNode}) { return <label className="grid gap-1.5 text-sm"><span className="font-medium">{label}</span>{children}</label> }

function BuscadorOpciones({label,value,placeholder,options,onChange,disabled=false}:{label:string;value:string;placeholder:string;options:{id:number;label:string}[];onChange:(v:string)=>void;disabled?:boolean}){
 const [open,setOpen]=useState(false); const [buscar,setBuscar]=useState('');
 const elegido=options.find(o=>String(o.id)===value);
 const filtradas=useMemo(()=>options.filter(o=>o.label.toLocaleLowerCase().includes(buscar.toLocaleLowerCase())),[options,buscar]);
 return <Campo label={label}><Popover open={open} onOpenChange={(v)=>{setOpen(v);if(!v)setBuscar('')}}><PopoverTrigger render={<Button type="button" variant="outline" role="combobox" aria-expanded={open} disabled={disabled} className="w-full justify-between font-normal"/>}>{elegido?.label||placeholder}<ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50"/></PopoverTrigger><PopoverContent className="w-[var(--anchor-width)] min-w-64 p-2" align="start"><div className="relative mb-2"><Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"/><Input autoFocus value={buscar} onChange={e=>setBuscar(e.target.value)} placeholder={`Buscar ${label.toLocaleLowerCase()}…`} className="pl-9"/></div><div className="max-h-60 overflow-y-auto">{filtradas.map(o=><button type="button" key={o.id} onClick={()=>{onChange(String(o.id));setOpen(false)}} className="flex w-full items-center rounded-md px-2 py-2 text-left text-sm hover:bg-accent"><Check className={cn('mr-2 h-4 w-4',value===String(o.id)?'opacity-100':'opacity-0')}/>{o.label}</button>)}{!filtradas.length&&<div className="px-1 py-2"><EmptyState title="Sin resultados" compact className="border-0 bg-transparent px-1" /></div>}</div>{value&&<Button type="button" variant="ghost" size="sm" className="mt-1 w-full" onClick={()=>{onChange('');setOpen(false)}}>Limpiar selección</Button>}</PopoverContent></Popover></Campo>
}

export function FiltrosReporte({filtros,value,onChange,rol}:FiltrosReporteProps){
 const [rangoOpen,setRangoOpen]=useState(false);
 const {data:macros=[]}=useCatalogoMacrodistritosReporte(filtros.includes('macrodistrito_id')&&rol==='superadmin');
 const {data:distritos=[]}=useCatalogoDistritosReporte(value.macrodistrito_id||'');
 const {data:unidades=[]}=useCatalogoUnidadesReporte(value.macrodistrito_id||'',value.distrito_id||'');
 const {data:gestiones=[]}=useCatalogoGestionesReporte(filtros.includes('gestion'));
 const {data:aulas=[]}=useCatalogoAulasReporte(filtros.includes('aula_id'));
 const {data:estudiantes=[]}=useCatalogoEstudiantesReporte(filtros.includes('estudiante_id'));
 const {data:periodos=[]}=useCatalogoPeriodosReporte(filtros.includes('periodo_id'));

 useEffect(()=>{
   if(!filtros.includes('gestion')||value.gestion||!gestiones.length)return;
   const recientes=[...gestiones].sort((a,b)=>b.anio-a.anio||b.id-a.id);
   onChange({...value,gestion:String(recientes[0].id)});
 },[filtros,gestiones,value,onChange]);

 const set=(key:string,v:string)=>onChange({...value,[key]:v});
 const fechaDesde=value.desde?new Date(`${value.desde}T00:00:00`):undefined;
 const fechaHasta=value.hasta?new Date(`${value.hasta}T00:00:00`):undefined;
 const dateRange:DateRange|undefined=fechaDesde||fechaHasta?{from:fechaDesde,to:fechaHasta}:undefined;
 const cambiaRango=(range:DateRange|undefined)=>onChange({...value,desde:range?.from?format(range.from,'yyyy-MM-dd'):'' ,hasta:range?.to?format(range.to,'yyyy-MM-dd'):''});
 const keys=filtros.filter(f=>!['hasta'].includes(f));
 const mostrarTerritorio=rol==='superadmin';
 const gestionesUnicas=[...new Map([...gestiones].sort((a,b)=>b.id-a.id).map(g=>[g.anio,g])).values()].sort((a,b)=>b.anio-a.anio);

 return <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
  {keys.map(key=>{
   if(['macrodistrito_id','distrito_id','unidad_id'].includes(key)&&!mostrarTerritorio)return null;
   if(key==='macrodistrito_id')return <Campo key={key} label={nombres[key]}><Select value={value[key]||vacio} onValueChange={v=>{const id=v===vacio?'':v||'';onChange({...value,macrodistrito_id:id,distrito_id:'',unidad_id:''})}}><SelectTrigger className="w-full"><SelectValue placeholder="Todos"/></SelectTrigger><SelectContent><SelectItem value={vacio}>Todos</SelectItem>{macros.map(m=><SelectItem value={String(m.id)} key={m.id}>{m.codigo} - {m.nombre}</SelectItem>)}</SelectContent></Select></Campo>;
   if(key==='distrito_id')return <Campo key={key} label={nombres[key]}><Select disabled={!value.macrodistrito_id} value={value[key]||vacio} onValueChange={v=>{const id=v===vacio?'':v||'';onChange({...value,distrito_id:id,unidad_id:''})}}><SelectTrigger className="w-full"><SelectValue placeholder={value.macrodistrito_id?'Todos':'Elige macrodistrito primero'}/></SelectTrigger><SelectContent><SelectItem value={vacio}>Todos</SelectItem>{distritos.map(d=><SelectItem value={String(d.id)} key={d.id}>DM{d.numero}{d.nombre?` - ${d.nombre}`:''}</SelectItem>)}</SelectContent></Select></Campo>;
   if(key==='unidad_id')return <div key={key} className="sm:col-span-2 lg:col-span-1"><BuscadorOpciones label={nombres[key]} value={value[key]||''} placeholder={value.distrito_id||value.macrodistrito_id?'Todas las unidades':'Elige macrodistrito primero'} options={unidades.map(u=>({id:u.id,label:u.nombre}))} disabled={!value.macrodistrito_id&&!value.distrito_id} onChange={v=>set(key,v)}/></div>;
   if(key==='tipo')return <Campo key={key} label={nombres[key]}><Select value={value[key]||vacio} onValueChange={v=>set(key,v===vacio?'':v||'')}><SelectTrigger className="w-full"><SelectValue placeholder="Todos"/></SelectTrigger><SelectContent><SelectItem value={vacio}>Todos</SelectItem>{[['fiscal','Fiscal'],['convenio','Convenio'],['privada','Privada']].map(([v,l])=><SelectItem key={v} value={v}>{l}</SelectItem>)}</SelectContent></Select></Campo>;
   if(key==='nivel')return <Campo key={key} label={nombres[key]}><Select value={value[key]||vacio} onValueChange={v=>set(key,v===vacio?'':v||'')}><SelectTrigger className="w-full"><SelectValue placeholder="Todos"/></SelectTrigger><SelectContent><SelectItem value={vacio}>Todos</SelectItem>{[['inicial','Inicial'],['primaria','Primaria'],['secundaria','Secundaria']].map(([v,l])=><SelectItem key={v} value={v}>{l}</SelectItem>)}</SelectContent></Select></Campo>;
   if(key==='gestion')return <Campo key={key} label={nombres[key]}><Select value={value[key]||vacio} onValueChange={v=>set(key,v===vacio?'':v||'')}><SelectTrigger className="w-full"><SelectValue placeholder="Selecciona gestión"/></SelectTrigger><SelectContent><SelectItem value={vacio}>Todas</SelectItem>{gestionesUnicas.map(g=><SelectItem value={String(g.id)} key={g.id}>{g.anio}{g.es_actual?' · Actual':''}</SelectItem>)}</SelectContent></Select></Campo>;
   if(key==='sexo')return <Campo key={key} label={nombres[key]}><Select value={value[key]||vacio} onValueChange={v=>set(key,v===vacio?'':v||'')}><SelectTrigger className="w-full"><SelectValue placeholder="Todos"/></SelectTrigger><SelectContent><SelectItem value={vacio}>Todos</SelectItem><SelectItem value="F">Femenino</SelectItem><SelectItem value="M">Masculino</SelectItem></SelectContent></Select></Campo>;
   if(key==='desde')return <Campo key={key} label={nombres[key]}><Popover open={rangoOpen} onOpenChange={setRangoOpen}><PopoverTrigger render={<Button type="button" variant="outline" className="w-full justify-start font-normal"/>}><CalendarDays className="mr-2 h-4 w-4"/>{dateRange?.from?dateRange.to?`${format(dateRange.from,'dd/MM/yyyy')} – ${format(dateRange.to,'dd/MM/yyyy')}`:format(dateRange.from,'dd/MM/yyyy'):'Seleccionar fechas'}</PopoverTrigger><PopoverContent className="w-auto p-0" align="start"><Calendar mode="range" locale={es} selected={dateRange} onSelect={range=>{cambiaRango(range);if(range?.from&&range.to)setRangoOpen(false)}} numberOfMonths={2} disabled={{after:new Date()}}/></PopoverContent></Popover></Campo>;
   if(key==='agrupar')return <Campo key={key} label={nombres[key]}><Select value={value[key]||'dia'} onValueChange={v=>set(key,v||'dia')}><SelectTrigger className="w-full"><SelectValue/></SelectTrigger><SelectContent><SelectItem value="dia">Día</SelectItem><SelectItem value="semana">Semana</SelectItem><SelectItem value="mes">Mes</SelectItem></SelectContent></Select></Campo>;
   if(key==='umbral')return <Campo key={key} label={nombres[key]}><Input type="number" min="0" max="100" value={value[key]||'51'} onChange={e=>set(key,e.target.value)}/></Campo>;
   if(key==='aula_id')return <Campo key={key} label={nombres[key]}><Select value={value[key]||vacio} onValueChange={v=>set(key,v===vacio?'':v||'')}><SelectTrigger className="w-full"><SelectValue placeholder="Todas"/></SelectTrigger><SelectContent><SelectItem value={vacio}>Todas</SelectItem>{aulas.map(a=><SelectItem key={a.id} value={String(a.id)}>{a.grado} {a.paralelo} · {a.gestion?.anio||''}</SelectItem>)}</SelectContent></Select></Campo>;
   if(key==='estudiante_id')return <div key={key}><BuscadorOpciones label={nombres[key]} value={value[key]||''} placeholder="Buscar estudiante" options={estudiantes.map(e=>({id:e.id,label:`${e.user?.apellidos||''} ${e.user?.nombres||''}`.trim()}))} onChange={v=>set(key,v)}/></div>;
   if(key==='periodo_id')return <Campo key={key} label={nombres[key]}><Select value={value[key]||vacio} onValueChange={v=>set(key,v===vacio?'':v||'')}><SelectTrigger className="w-full"><SelectValue placeholder="Todos"/></SelectTrigger><SelectContent><SelectItem value={vacio}>Todos</SelectItem>{periodos.map(p=><SelectItem key={p.id} value={String(p.id)}>{p.nombre}</SelectItem>)}</SelectContent></Select></Campo>;
   return null;
  })}
 </div>;
}
