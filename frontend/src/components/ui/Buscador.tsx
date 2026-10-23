import { useEffect, useRef, useState } from 'react';
import { Loader2, Search } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Input } from './input';

interface Props {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  className?: string;
  loading?: boolean;
  /** Milisegundos de espera antes de propagar el cambio. 400 por defecto. */
  debounceMs?: number;
}

export const Buscador = ({
  value,
  onChange,
  placeholder = 'Buscar...',
  className,
  loading,
  debounceMs = 400,
}: Props) => {
  const [interno, setInterno] = useState(value);
  const onChangeRef = useRef(onChange);
  onChangeRef.current = onChange;

  useEffect(() => {
    setInterno(value);
  }, [value]);

  useEffect(() => {
    if (interno === value) return;
    const id = setTimeout(() => onChangeRef.current(interno), debounceMs);
    return () => clearTimeout(id);
  }, [interno, value, debounceMs]);

  return (
    <div className={cn('relative w-full max-w-xs', className)}>
      <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
      <Input
        value={interno}
        onChange={(e) => setInterno(e.target.value)}
        placeholder={placeholder}
        className="h-9 pl-9"
      />
      {loading && <Loader2 size={14} className="absolute right-3 top-1/2 -translate-y-1/2 animate-spin text-muted-foreground" />}
    </div>
  );
};
