import { useId, type ReactNode } from 'react';
import { CheckCircle2, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Label } from './label';

interface FormFieldProps {
  label: string;
  htmlFor?: string;
  error?: string;
  hint?: string;
  required?: boolean;
  /** Muestra un check verde cuando el campo tiene valor y no hay error. */
  valido?: boolean;
  children: ReactNode;
  className?: string;
}

export const FormField = ({
  label,
  htmlFor,
  error,
  hint,
  required,
  valido,
  children,
  className,
}: FormFieldProps) => {
  const idGenerado = useId();
  const id = htmlFor ?? `campo-${idGenerado}`;
  const idError = `${id}-error`;
  const idHint = `${id}-hint`;
  const descrito = [error ? idError : null, hint && !error ? idHint : null].filter(Boolean).join(' ') || undefined;

  return (
    <div className={cn('space-y-1.5', className)}>
      <Label htmlFor={id} className="text-sm font-medium text-foreground">
        {label}
        {required && <span className="text-destructive" aria-hidden="true">*</span>}
      </Label>
      <div
        data-describedby={descrito}
        data-invalid={error ? 'true' : undefined}
        className="[&_[data-slot=input]]:aria-invalid:border-destructive"
      >
        {children}
      </div>
      {hint && !error && (
        <p id={idHint} className="text-xs text-muted-foreground">
          {hint}
        </p>
      )}
      {error ? (
        <p
          id={idError}
          role="alert"
          className="flex animate-in fade-in slide-in-from-top-1 items-center gap-1.5 text-xs font-medium text-destructive duration-200"
        >
          <AlertCircle className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          {error}
        </p>
      ) : valido ? (
        <p className="flex animate-in fade-in items-center gap-1.5 text-xs font-medium text-emerald-600 duration-200 dark:text-emerald-400">
          <CheckCircle2 className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          Correcto
        </p>
      ) : null}
    </div>
  );
};
