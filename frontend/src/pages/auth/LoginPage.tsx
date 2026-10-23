import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { Eye, EyeOff, GraduationCap, Loader2, Lock, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractMessage } from '@/lib/errors';
import { minLongitud, requerido, soloNumeros } from '@/lib/validacion';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

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
    <div className="relative min-h-screen overflow-hidden bg-background">
      <div className="absolute inset-0 bg-gradient-to-br from-primary/10 via-background to-background" />
      <div className="absolute -left-32 top-[-10%] h-72 w-72 rounded-full bg-primary/20 blur-3xl" />
      <div className="absolute -right-32 bottom-[-10%] h-80 w-80 rounded-full bg-indigo-400/10 blur-3xl" />
      <div className="relative mx-auto grid min-h-screen max-w-6xl grid-cols-1 lg:grid-cols-2">
        <div className="hidden flex-col justify-between p-10 lg:flex">
          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-lg shadow-primary/20">
              <GraduationCap className="h-5 w-5" />
            </div>
            <div>
              <p className="text-lg font-semibold tracking-tight">Sistema Escolar</p>
              <p className="text-sm text-muted-foreground">Gestión académica integral</p>
            </div>
          </div>
          <div className="space-y-4">
            <h1 className="text-4xl font-semibold tracking-tight xl:text-5xl">
              Todo el control escolar en un solo lugar
            </h1>
            <p className="max-w-md text-lg text-muted-foreground">
              Administra estudiantes, asistencia, calificaciones, citaciones y seguimiento familiar con una experiencia moderna y clara.
            </p>
          </div>
          <p className="text-sm text-muted-foreground">© {new Date().getFullYear()} Sistema Escolar</p>
        </div>
        <div className="flex items-center justify-center p-4 sm:p-6">
          <Card className="w-full max-w-md border-border/60 shadow-xl shadow-black/5">
            <CardHeader className="space-y-2 text-center">
              <CardTitle className="text-2xl font-semibold tracking-tight">Iniciar sesión</CardTitle>
              <CardDescription>Ingresa con tu CI y contraseña para acceder al sistema</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                {error ? (
                  <div
                    role="alert"
                    className="animate-in fade-in slide-in-from-top-1 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive duration-200"
                  >
                    {error}
                  </div>
                ) : null}
                <div className="space-y-2">
                  <Label htmlFor="ci">CI</Label>
                  <div className="relative">
                    <User className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                      id="ci"
                      inputMode="numeric"
                      autoComplete="username"
                      value={form.valores.ci}
                      onChange={(e) => form.setCampo('ci', e.target.value)}
                      onBlur={form.onBlur('ci')}
                      placeholder="Ingresa tu número de carnet"
                      aria-invalid={!!form.errorDe('ci')}
                      className="pl-9"
                    />
                  </div>
                  {form.errorDe('ci') && (
                    <p role="alert" className="text-xs font-medium text-destructive">{form.errorDe('ci')}</p>
                  )}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="password">Contraseña</Label>
                  <div className="relative">
                    <Lock className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                      id="password"
                      type={verPassword ? 'text' : 'password'}
                      autoComplete="current-password"
                      value={form.valores.password}
                      onChange={(e) => form.setCampo('password', e.target.value)}
                      onBlur={form.onBlur('password')}
                      placeholder="Ingresa tu contraseña"
                      aria-invalid={!!form.errorDe('password')}
                      className="pl-9 pr-9"
                    />
                    <button
                      type="button"
                      onClick={() => setVerPassword((v) => !v)}
                      aria-label={verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                      className="absolute right-2 top-2 rounded-md p-0.5 text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                    >
                      {verPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                  {form.errorDe('password') && (
                    <p role="alert" className="text-xs font-medium text-destructive">{form.errorDe('password')}</p>
                  )}
                </div>
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Ingresando...
                    </>
                  ) : (
                    "Ingresar"
                  )}
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
};
