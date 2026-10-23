import { EstadoBadge } from './StatusBadge';

interface Props {
  estado: string;
}

export const BadgeEstado = ({ estado }: Props) => <EstadoBadge estado={estado} />;
