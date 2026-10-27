import { useState } from 'react'
import { useBajaPersona, useCrearPersona, usePersonas } from '@/api/gestion'
import { Buscador } from '@/components/ui/Buscador'
import { Paginacion } from '@/components/ui/Paginacion'
import { Modal } from '@/components/ui/Modal'
import { BajaModal } from '@/components/ui/BajaModal'
import { StatusBadge } from '@/components/ui/StatusBadge'
import { PageHeader } from '@/components/ui/PageHeader'
import { EmptyState } from '@/components/ui/EmptyState'
import { Plus, Users } from 'lucide-react'
import type { Persona } from '@/types/gestion'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { CampoTexto } from '@/components/ui/CampoTexto'
import { useFormValidacion } from '@/hooks/useFormValidacion'
import { extractErrors, extractMessage } from '@/lib/errors'
import { ci, email, nombre, requerido, telefono } from '@/lib/validacion'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { MoreHorizontal } from 'lucide-react'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { toast } from 'sonner'

const ROLES = [
  { value: 'profesor', label: 'Profesor' },
  { value: 'administrativo', label: 'Administrativo' },
  { value: 'padre', label: 'Padre de Familia' },
]

const emptyForm = { rol: 'profesor', ci: '', nombres: '', apellidos: '', email: '', telefono: '' }

export const PersonasPage = () => {
  const [buscar, setBuscar] = useState('')
  const [rol, setRol] = useState('')
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [aBaja, setABaja] = useState<Persona | null>(null)

  const { data, isLoading } = usePersonas({ buscar, rol, page })
  const crear = useCrearPersona()
  const baja = useBajaPersona()

  const form = useFormValidacion(emptyForm, {
    rol: [requerido('Seleccioná un rol')],
    ci: [requerido('El CI es obligatorio'), ci()],
    nombres: [requerido('Los nombres son obligatorios'), nombre()],
    apellidos: [requerido('Los apellidos son obligatorios'), nombre()],
    email: [email()],
    telefono: [telefono()],
  })

  const cerrar = () => {
    setModalOpen(false)
    form.reiniciar()
  }

  const handleCrear = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.validarTodo()) return
    try {
      await crear.mutateAsync(form.valores)
      toast.success('Persona creada correctamente')
      cerrar()
    } catch (err: unknown) {
      const errores = extractErrors(err)
      const msg = extractMessage(err, 'No se pudo crear la persona.')
      for (const [campo, mensaje] of Object.entries(errores)) form.setErrorServidor(campo, mensaje)
      toast.error(msg)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Personas"
        description="Gestiona docentes, administrativos y padres de familia"
        action={
          <Button onClick={() => setModalOpen(true)} className="gap-2">
            <Plus className="h-4 w-4" />
            Nueva persona
          </Button>
        }
      />

      <Card className="rounded-xl border-border/60 p-4 shadow-sm">
        <div className="flex flex-wrap items-center gap-3">
          <div className="flex-1">
            <Buscador value={buscar} onChange={(v) => { setBuscar(v); setPage(1); }} placeholder="Buscar por nombre o CI..." />
          </div>
          <Select value={rol || 'all'} onValueChange={(v) => { setRol(!v || v === 'all' ? '' : v); setPage(1); }}>
            <SelectTrigger className="w-[200px]">
              <SelectValue placeholder="Todos los roles" />
            </SelectTrigger>
            <SelectContent className="rounded-xl">
              <SelectItem value="all">Todos los roles</SelectItem>
              {ROLES.map((r) => (
                <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </Card>

      <Card className="rounded-xl border-border/60 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>CI</TableHead>
                <TableHead>Nombre completo</TableHead>
                <TableHead>Rol</TableHead>
                <TableHead>Teléfono</TableHead>
                <TableHead>Estado</TableHead>
                <TableHead className="text-right">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                [...Array(5)].map((_, i) => (
                  <TableRow key={i}>
                    {[...Array(6)].map((_, j) => (
                      <TableCell key={j}><Skeleton className="h-5" /></TableCell>
                    ))}
                  </TableRow>
                ))
              ) : data?.data.length ? (
                data.data.map((p) => (
                  <TableRow key={p.id} className="hover:bg-muted/50">
                    <TableCell className="font-mono">{p.ci}</TableCell>
                    <TableCell className="font-medium">{p.nombre_completo}</TableCell>
                    <TableCell>{p.rol_label}</TableCell>
                    <TableCell>{p.telefono || '-'}</TableCell>
                    <TableCell><StatusBadge estado={p.estado} /></TableCell>
                    <TableCell className="text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger
                          render={<Button variant="ghost" size="icon" className="h-8 w-8" />}
                        >
                          <MoreHorizontal className="h-4 w-4" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="rounded-xl">
                          <DropdownMenuItem
                            onClick={() => setABaja(p)}
                            className="text-destructive focus:bg-destructive/10 focus:text-destructive"
                          >
                            Dar de baja
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={6} className="py-0">
                    <EmptyState
                      icon={<Users className="h-5 w-5" />}
                      title="Sin resultados"
                      description="No se encontraron personas con los filtros seleccionados"
                    />
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
        {data && <div className="border-t p-4"><Paginacion page={data.current_page} lastPage={data.last_page} total={data.total} onChange={setPage} /></div>}
      </Card>

      <Modal open={modalOpen} onClose={cerrar} title="Nueva persona">
        <form onSubmit={handleCrear} className="space-y-4" noValidate>
          <div className="space-y-1.5">
            <Label htmlFor="rol-persona">Rol</Label>
            <Select value={form.valores.rol} onValueChange={(v) => form.setCampo('rol', v ?? 'profesor')}>
              <SelectTrigger id="rol-persona">
                <SelectValue />
              </SelectTrigger>
              <SelectContent className="rounded-xl">
                {ROLES.map((r) => (
                  <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <CampoTexto
              label="CI"
              required
              inputMode="numeric"
              value={form.valores.ci}
              onChange={(e) => form.setCampo('ci', e.target.value.replace(/\D/g, ''))}
              onBlur={form.onBlur('ci')}
              error={form.errorDe('ci')}
            />
            <CampoTexto
              label="Teléfono"
              type="tel"
              inputMode="tel"
              value={form.valores.telefono}
              onChange={(e) => form.setCampo('telefono', e.target.value)}
              onBlur={form.onBlur('telefono')}
              error={form.errorDe('telefono')}
              hint="Opcional"
            />
            <CampoTexto
              label="Nombres"
              required
              autoComplete="given-name"
              value={form.valores.nombres}
              onChange={(e) => form.setCampo('nombres', e.target.value)}
              onBlur={form.onBlur('nombres')}
              error={form.errorDe('nombres')}
            />
            <CampoTexto
              label="Apellidos"
              required
              autoComplete="family-name"
              value={form.valores.apellidos}
              onChange={(e) => form.setCampo('apellidos', e.target.value)}
              onBlur={form.onBlur('apellidos')}
              error={form.errorDe('apellidos')}
            />
            <CampoTexto
              label="Correo electrónico"
              type="email"
              inputMode="email"
              autoComplete="email"
              className="sm:col-span-2"
              value={form.valores.email}
              onChange={(e) => form.setCampo('email', e.target.value)}
              onBlur={form.onBlur('email')}
              error={form.errorDe('email')}
              hint="Opcional"
            />
          </div>
          <p className="text-xs text-muted-foreground">Credenciales iniciales: usuario y contraseña = CI.</p>
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={cerrar}>Cancelar</Button>
            <Button type="submit" disabled={crear.isPending}>
              {crear.isPending ? 'Guardando...' : 'Crear persona'}
            </Button>
          </div>
        </form>
      </Modal>

      <BajaModal
        open={!!aBaja}
        onClose={() => setABaja(null)}
        nombre={aBaja?.nombre_completo || ''}
        onConfirm={(motivo, fecha) => baja.mutateAsync({ id: aBaja!.id, payload: { motivo, fecha } }).then(() => toast.success('Persona dada de baja correctamente'))}
      />
    </div>
  )
}
