import { useState } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/FormField';
import { Input } from '@/components/ui/input';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractMessage } from '@/lib/errors';
import { minLongitud, requerido, soloNumeros } from '@/lib/validacion';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import { AlertCircle, Eye, EyeOff, KeyRound, Loader2, School, ShieldCheck } from 'lucide-react';

export const CambiarContrasenaObligatorio = () => {
  const { cambiarContrasena, logout, user } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [verPassword, setVerPassword] = useState(false);

  const form = useFormValidacion(
    { password_actual: '', password: '', password_confirmation: '' },
    {
      password_actual: [requerido('Ingresá tu contraseña actual'), soloNumeros('La contraseña solo debe contener números')],
      password: [requerido('Ingresá la nueva contraseña'), soloNumeros('La contraseña solo debe contener números'), minLongitud(8, 'La nueva contraseña debe tener al menos 8 caracteres')],
      password_confirmation: [requerido('Confirmá la nueva contraseña'), soloNumeros('La contraseña solo debe contener números')],
    }
  );

  const errorConfirmacion = (() => {
    const { password, password_confirmation } = form.valores;
    if (!form.tocados['password_confirmation'] && !form.errores['password_confirmation']) return undefined;
    if (password && password_confirmation && password !== password_confirmation) {
      return 'Las contraseñas no coinciden';
    }
    return form.errorDe('password_confirmation');
  })();

  const errorNueva = (() => {
    const { password_actual, password } = form.valores;
    if (password && password_actual && password === password_actual) {
      return 'La nueva contraseña debe ser distinta a la actual';
    }
    return form.errorDe('password');
  })();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (form.valores.password !== form.valores.password_confirmation) {
      form.setErrorServidor('password_confirmation', 'Las contraseñas no coinciden');
      return;
    }
    if (form.valores.password === form.valores.password_actual) {
      form.setErrorServidor('password', 'La nueva contraseña debe ser distinta a la actual');
      return;
    }
    if (!form.validarTodo()) return;
    setLoading(true);
    try {
      await cambiarContrasena(form.valores.password_actual, form.valores.password, form.valores.password_confirmation);
      toast.success('Contraseña actualizada');
      navigate('/');
    } catch (err: unknown) {
      const msg = extractMessage(err, 'Error al cambiar contraseña');
      setError(msg);
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary/5 via-background to-background px-4 py-12">
      <div className="w-full max-w-md space-y-6">
        <div className="flex flex-col items-center gap-3 text-center">
          <span className="flex size-12 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
            <School className="size-6" />
          </span>
          <div className="flex size-11 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300">
            <ShieldCheck className="size-6" />
          </div>
        </div>

        <Card className="shadow-sm">
          <CardHeader>
            <CardTitle className="text-xl">Cambio de contraseña obligatorio</CardTitle>
            <CardDescription>
              Hola {user?.nombre_completo}, por seguridad debes cambiar tu contraseña en el primer ingreso.
            </CardDescription>
          </CardHeader>
          <CardContent>
            {error && (
              <div className="mb-4 flex items-start gap-2 rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                <AlertCircle className="mt-0.5 size-4 shrink-0" />
                <span>{error}</span>
              </div>
            )}
            <form onSubmit={handleSubmit} className="space-y-4" noValidate>
              <FormField label="Contraseña actual" htmlFor="actual" required error={form.errorDe('password_actual')}>
                <Input
                  id="actual"
                  type="password"
                  value={form.valores.password_actual}
                  onChange={(e) => form.setCampo('password_actual', e.target.value.replace(/\D/g, ''))}
                  onBlur={form.onBlur('password_actual')}
                  autoComplete="current-password"
                  aria-invalid={!!form.errorDe('password_actual')}
                />
              </FormField>
              <FormField label="Nueva contraseña" htmlFor="nueva" required error={errorNueva} hint="Mínimo 8 caracteres">
                <div className="relative">
                  <Input
                    id="nueva"
                    type={verPassword ? 'text' : 'password'}
                    value={form.valores.password}
                    onChange={(e) => form.setCampo('password', e.target.value.replace(/\D/g, ''))}
                    onBlur={form.onBlur('password')}
                    autoComplete="new-password"
                    aria-invalid={!!errorNueva}
                    className="pr-9"
                  />
                  <button
                    type="button"
                    onClick={() => setVerPassword((v) => !v)}
                    aria-label={verPassword ? 'Ocultar contraseñas' : 'Mostrar contraseñas'}
                    className="absolute right-2 top-2 rounded-md p-0.5 text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                  >
                    {verPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </FormField>
              <FormField label="Confirmar nueva contraseña" htmlFor="confirmar" required error={errorConfirmacion}>
                <Input
                  id="confirmar"
                  type={verPassword ? 'text' : 'password'}
                  value={form.valores.password_confirmation}
                  onChange={(e) => form.setCampo('password_confirmation', e.target.value.replace(/\D/g, ''))}
                  onBlur={form.onBlur('password_confirmation')}
                  autoComplete="new-password"
                  aria-invalid={!!errorConfirmacion}
                />
              </FormField>
              <Button type="submit" className="w-full" size="lg" disabled={loading}>
                {loading ? <Loader2 className="animate-spin" /> : <KeyRound />}
                Cambiar contraseña
              </Button>
              <Button type="button" variant="ghost" className="w-full" onClick={handleLogout} disabled={loading}>
                Cerrar sesión
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
