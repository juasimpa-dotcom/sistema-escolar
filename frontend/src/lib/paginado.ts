import type { Paginado } from '../types/gestion';

/**
 * Aplica una transformación a un valor posiblemente paginado.
 * Devuelve el mismo valor si no tiene la forma { data: [...] }.
 */
export const mapPaginado = <T>(
  valor: unknown,
  fn: (items: T[]) => T[]
): Paginado<T> | unknown => {
  if (
    valor &&
    typeof valor === 'object' &&
    Array.isArray((valor as Paginado<T>).data)
  ) {
    const paginado = valor as Paginado<T>;
    return { ...paginado, data: fn(paginado.data) };
  }
  return valor;
};
