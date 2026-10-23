import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from './dialog';
import { Button } from './button';
import { FormField } from './FormField';
import { Input } from './input';
import { Textarea } from './textarea';
import { useFormValidacion } from '@/hooks/useFormValidacion';
import { extractMessage } from '@/lib/errors';
import { longitud, requerido } from '@/lib/validacion';

interface Props {
  open: boolean;
  onClose: () => void;
  titulo?: string;
  nombre: string;
  descripcion?: string;
  onConfirm: (motivo: string, fecha: string) => Promise<unknown> | void;
}

const hoy = () => new Date().toISOString().slice(0, 10);

export const BajaModal = ({
  open,
  onClose,
  titulo = 'Dar de baja',
  nombre,
  descripcion,
  onConfirm,
}: Props) => {
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const form = useFormValidacion(
    { motivo: '', fecha: hoy() },
    {
      motivo: [requerido('El motivo es obligatorio'), longitud(5, 500, 'El motivo debe tener entre 5 y 500 caracteres')],
      fecha: [requerido('La fecha de baja es obligatoria')],
    }
  );

  useEffect(() => {
    if (open) {
      form.reiniciar({ motivo: '', fecha: hoy() });
      setError('');
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!form.validarTodo()) return;
    setLoading(true);
    try {
      await onConfirm(form.valores.motivo, form.valores.fecha);
      onClose();
    } catch (err: unknown) {
      setError(extractMessage(err, 'No se pudo completar la baja.'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => !v && !loading && onClose()}>
      <DialogContent className="sm:max-w-md">
        <form onSubmit={handleSubmit} className="flex flex-col gap-4" noValidate>
          <DialogHeader>
            <DialogTitle>{titulo}</DialogTitle>
            <DialogDescription>
              {descripcion ?? (
                <>
                  Se dará de baja a <strong className="text-foreground">{nombre}</strong>. Esta acción aplica baja lógica
                  y conserva el historial.
                </>
              )}
            </DialogDescription>
          </DialogHeader>

          {error && (
            <div role="alert" className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
              {error}
            </div>
          )}

          <FormField label="Motivo" required error={form.errorDe('motivo')}>
            <Textarea
              value={form.valores.motivo}
              onChange={(e) => form.setCampo('motivo', e.target.value)}
              onBlur={form.onBlur('motivo')}
              rows={3}
              placeholder="Indica el motivo de la baja"
              aria-invalid={!!form.errorDe('motivo')}
            />
          </FormField>

          <FormField label="Fecha de baja" required error={form.errorDe('fecha')}>
            <Input
              type="date"
              value={form.valores.fecha}
              onChange={(e) => form.setCampo('fecha', e.target.value)}
              onBlur={form.onBlur('fecha')}
              aria-invalid={!!form.errorDe('fecha')}
            />
          </FormField>

          <DialogFooter className="-mx-0 -mb-0 rounded-b-xl">
            <Button type="button" variant="outline" onClick={onClose} disabled={loading}>
              Cancelar
            </Button>
            <Button type="submit" variant="destructive" disabled={loading}>
              {loading && <Loader2 className="animate-spin" />}
              Confirmar baja
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
