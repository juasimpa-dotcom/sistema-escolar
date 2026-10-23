import * as React from 'react';
import { Input } from './input';
import { Textarea } from './textarea';
import { FormField } from './FormField';
import { cn } from '@/lib/utils';

interface BaseCampoProps {
  label: string;
  error?: string;
  hint?: string;
  required?: boolean;
  valido?: boolean;
  className?: string;
}

export interface CampoTextoProps extends BaseCampoProps, React.ComponentProps<'input'> {
  /** Renderiza un textarea en lugar de un input. */
  multilinea?: boolean;
  /** Número de filas cuando `multilinea` es true. */
  rows?: number;
}

export const CampoTexto = React.forwardRef<HTMLInputElement, CampoTextoProps>(
  ({ label, error, hint, required, valido, className, id, multilinea, ...props }, ref) => {
    const controlId = id ?? `campo-${label.replace(/\s+/g, '-').toLowerCase()}`;
    const idError = `${controlId}-error`;
    const idHint = `${controlId}-hint`;
    const descrito = [error ? idError : null, hint && !error ? idHint : null].filter(Boolean).join(' ') || undefined;

    const comunes = {
      id: controlId,
      'aria-invalid': error ? (true as const) : undefined,
      'aria-describedby': descrito,
      className: cn(error && 'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/20'),
    };

    return (
      <FormField label={label} htmlFor={controlId} error={error} hint={hint} required={required} valido={valido} className={className}>
        {multilinea ? (
          <Textarea {...comunes} {...(props as React.ComponentProps<'textarea'>)} />
        ) : (
          <Input ref={ref} {...comunes} {...props} />
        )}
      </FormField>
    );
  }
);
CampoTexto.displayName = 'CampoTexto';

export interface CampoSelectProps extends BaseCampoProps, React.ComponentProps<'select'> {}

export const CampoSelect = React.forwardRef<HTMLSelectElement, CampoSelectProps>(
  ({ label, error, hint, required, valido, className, id, children, ...props }, ref) => {
    const controlId = id ?? `campo-${label.replace(/\s+/g, '-').toLowerCase()}`;
    const idError = `${controlId}-error`;
    const idHint = `${controlId}-hint`;
    const descrito = [error ? idError : null, hint && !error ? idHint : null].filter(Boolean).join(' ') || undefined;

    return (
      <FormField label={label} htmlFor={controlId} error={error} hint={hint} required={required} valido={valido} className={className}>
        <select
          ref={ref}
          id={controlId}
          aria-invalid={error ? true : undefined}
          aria-describedby={descrito}
          className={cn(
            'flex h-9 w-full min-w-0 appearance-none rounded-lg border border-input bg-transparent px-3 py-1 text-sm transition-colors outline-none',
            'focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50',
            'disabled:cursor-not-allowed disabled:opacity-50',
            'aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20',
            'dark:bg-input/30 dark:aria-invalid:border-destructive/50'
          )}
          {...props}
        >
          {children}
        </select>
      </FormField>
    );
  }
);
CampoSelect.displayName = 'CampoSelect';
