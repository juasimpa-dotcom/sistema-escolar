import { useCallback, useMemo, useRef, useState } from 'react';
import { validarCampo, type Regla } from '@/lib/validacion';

export type RutaEsquema<T> = Partial<Record<keyof T | string, Regla[]>>;

const obtener = (obj: Record<string, unknown>, ruta: string): unknown =>
  ruta.split('.').reduce<unknown>((acc, parte) => {
    if (acc && typeof acc === 'object') return (acc as Record<string, unknown>)[parte];
    return undefined;
  }, obj);

const asignar = <T>(obj: T, ruta: string, valor: unknown): T => {
  const partes = ruta.split('.');
  const copia: Record<string, unknown> = Array.isArray(obj)
    ? ([...obj] as unknown as Record<string, unknown>)
    : { ...(obj as Record<string, unknown>) };
  let actual = copia;
  for (let i = 0; i < partes.length - 1; i++) {
    const parte = partes[i];
    const hijo = actual[parte];
    actual[parte] =
      hijo && typeof hijo === 'object' && !Array.isArray(hijo)
        ? { ...(hijo as Record<string, unknown>) }
        : Array.isArray(hijo)
          ? [...hijo]
          : {};
    actual = actual[parte] as Record<string, unknown>;
  }
  actual[partes[partes.length - 1]] = valor;
  return copia as T;
};

export interface UseFormValidacion<T extends Record<string, unknown>> {
  valores: T;
  errores: Record<string, string>;
  tocados: Record<string, boolean>;
  esValido: boolean;
  setCampo: (ruta: keyof T | string, valor: unknown) => void;
  setValores: (valores: T) => void;
  onBlur: (ruta: keyof T | string) => () => void;
  errorDe: (ruta: keyof T | string) => string | undefined;
  marcarTodoTocado: () => void;
  validarTodo: () => boolean;
  reiniciar: (nuevos?: T) => void;
  limpiarErroresServidor: () => void;
  setErrorServidor: (ruta: keyof T | string, mensaje: string) => void;
}

export const useFormValidacion = <T extends Record<string, unknown>>(
  inicial: T,
  esquema: RutaEsquema<T>
): UseFormValidacion<T> => {
  const [valores, setValores] = useState<T>(inicial);
  const [tocados, setTocados] = useState<Record<string, boolean>>({});
  const [erroresServidor, setErroresServidor] = useState<Record<string, string>>({});
  const esquemaRef = useRef(esquema);
  esquemaRef.current = esquema;

  const erroresCliente = useMemo(() => {
    const resultado: Record<string, string> = {};
    for (const ruta of Object.keys(esquemaRef.current)) {
      const reglas = esquemaRef.current[ruta];
      const valor = obtener(valores as Record<string, unknown>, ruta);
      const error = validarCampo(reglas, valor == null ? '' : String(valor));
      if (error) resultado[ruta] = error;
    }
    return resultado;
  }, [valores]);

  const errores = useMemo(
    () => ({ ...erroresCliente, ...erroresServidor }),
    [erroresCliente, erroresServidor]
  );

  const setErrorServidor = useCallback((ruta: keyof T | string, mensaje: string) => {
    setErroresServidor((prev) => ({ ...prev, [ruta]: mensaje }));
  }, []);

  const limpiarErroresServidor = useCallback(() => setErroresServidor({}), []);

  const setCampo = useCallback((ruta: keyof T | string, valor: unknown) => {
    setValores((prev) => asignar(prev, String(ruta), valor));
    setErroresServidor((prev) => {
      if (!(ruta in prev)) return prev;
      const copia = { ...prev };
      delete copia[String(ruta)];
      return copia;
    });
  }, []);

  const onBlur = useCallback(
    (ruta: keyof T | string) => () => setTocados((prev) => ({ ...prev, [String(ruta)]: true })),
    []
  );

  const errorDe = useCallback(
    (ruta: keyof T | string): string | undefined => {
      const rutaStr = String(ruta);
      if (!tocados[rutaStr] && !erroresServidor[rutaStr]) return undefined;
      return errores[rutaStr];
    },
    [errores, tocados, erroresServidor]
  );

  const marcarTodoTocado = useCallback(() => {
    const todos: Record<string, boolean> = {};
    for (const ruta of Object.keys(esquemaRef.current)) todos[ruta] = true;
    setTocados(todos);
  }, []);

  const validarTodo = useCallback((): boolean => {
    marcarTodoTocado();
    return Object.keys(erroresCliente).length === 0;
  }, [erroresCliente, marcarTodoTocado]);

  const reiniciar = useCallback((nuevos?: T) => {
    setValores(nuevos ?? inicial);
    setTocados({});
    setErroresServidor({});
  }, [inicial]);

  const esValido = Object.keys(erroresCliente).length === 0;

  return {
    valores,
    errores,
    tocados,
    esValido,
    setCampo,
    setValores,
    onBlur,
    errorDe,
    marcarTodoTocado,
    validarTodo,
    reiniciar,
    limpiarErroresServidor,
    setErrorServidor,
  };
};
