import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from './button';

interface Props {
  page: number;
  lastPage: number;
  total: number;
  onChange: (page: number) => void;
}

export const Paginacion = ({ page, lastPage, total, onChange }: Props) => {
  if (lastPage <= 1) return <p className="text-sm text-muted-foreground">{total} registro(s)</p>;

  return (
    <div className="flex flex-wrap items-center justify-between gap-3">
      <p className="text-sm text-muted-foreground">
        Página <span className="font-medium text-foreground">{page}</span> de {lastPage} · {total} registro(s)
      </p>
      <div className="flex items-center gap-2">
        <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => onChange(page - 1)}>
          <ChevronLeft /> Anterior
        </Button>
        <Button variant="outline" size="sm" disabled={page >= lastPage} onClick={() => onChange(page + 1)}>
          Siguiente <ChevronRight />
        </Button>
      </div>
    </div>
  );
};
