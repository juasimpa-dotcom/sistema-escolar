import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { Eye, EyeOff, Loader2, Lock, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractMessage } from '@/lib/errors';
import { minLongitud, requerido, soloNumeros } from '@/lib/validacion';
import { LogoMarca } from '@/components/layout/LogoMarca';
import { IMAGES } from '@/config/images';

export const LoginPage = () => {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [verPassword, setVerPassword] = useState(false);

  const form = useFormValidacion(
    { ci: '', password: '' },
    {
      ci: [requerido('Ingresá tu CI'), soloNumeros('El CI solo debe contener números')],
      password: [requerido('Ingresá tu contraseña'), minLongitud(4, 'La contraseña debe tener al menos 4 caracteres')],
    }
  );

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;
    setLoading(true);
    try {
      await login(form.valores.ci, form.valores.password);
      navigate('/');
    } catch (err: unknown) {
      setError(extractMessage(err, 'Error al iniciar sesión'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="grid min-h-screen bg-background lg:grid-cols-2">
      <section className="flex min-h-screen items-center justify-center px-5 py-10 sm:px-10">
        <div className="w-full max-w-md space-y-9">
          <div className="flex items-center gap-3">
            <LogoMarca loading="eager" />
            <div>
              <p className="font-semibold tracking-tight">Sistema Escolar</p>
              <p className="text-sm text-muted-foreground">Gestión académica integral</p>
            </div>
          </div>
          <div className="space-y-2">
            <p className="text-sm font-medium text-primary">Bienvenido de nuevo</p>
            <h1 className="text-3xl font-semibold tracking-tight">Inicia sesión</h1>
            <p className="text-muted-foreground">Ingresa con tu carnet y contraseña para acceder al sistema.</p>
          </div>
          <form onSubmit={handleSubmit} className="space-y-5" noValidate>
            {error && <div role="alert" className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">{error}</div>}
            <div className="space-y-2">
              <Label htmlFor="ci">Carnet de identidad</Label>
              <div className="relative">
                <User className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input id="ci" inputMode="numeric" autoComplete="username" value={form.valores.ci}
                  onChange={(e) => form.setCampo('ci', e.target.value.replace(/\D/g, ''))} onBlur={form.onBlur('ci')}
                  placeholder="Ingresa tu número de carnet" aria-invalid={!!form.errorDe('ci')} className="pl-9" />
              </div>
              {form.errorDe('ci') && <p role="alert" className="text-xs font-medium text-destructive">{form.errorDe('ci')}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="password">Contraseña</Label>
              <div className="relative">
                <Lock className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input id="password" type={verPassword ? 'text' : 'password'} inputMode="numeric" autoComplete="current-password"
                  value={form.valores.password} onChange={(e) => form.setCampo('password', e.target.value.replace(/\D/g, ''))}
                  onBlur={form.onBlur('password')} placeholder="Ingresa tu contraseña" aria-invalid={!!form.errorDe('password')} className="pl-9 pr-9" />
                <button type="button" onClick={() => setVerPassword((v) => !v)} aria-label={verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                  className="absolute right-2 top-2 rounded-md p-0.5 text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                  {verPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              {form.errorDe('password') && <p role="alert" className="text-xs font-medium text-destructive">{form.errorDe('password')}</p>}
            </div>
            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Ingresando...</> : 'Ingresar'}
            </Button>
          </form>
          <p className="text-xs text-muted-foreground">© {new Date().getFullYear()} Sistema Escolar</p>
        </div>
      </section>
      <aside className="relative hidden min-h-screen overflow-hidden bg-indigo-950 lg:block">
        <img src={IMAGES.login} alt="Estudiantes ingresando al edificio de una institución educativa" width={1920} height={1280} loading="eager"
          className="absolute inset-0 size-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/35 to-indigo-950/25" />
        <div className="absolute inset-x-0 bottom-0 p-10 text-white xl:p-16">
          <p className="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-indigo-100">Sistema Escolar</p>
          <h2 className="max-w-xl text-4xl font-semibold leading-tight tracking-tight xl:text-5xl">Cada comunidad educativa, conectada.</h2>
        </div>
      </aside>
    </main>
  );
};
