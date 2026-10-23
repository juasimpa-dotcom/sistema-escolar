export type Regla = (valor: string) => string | null;

const soloDigitos = /^\d+$/;
const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const regexNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü][A-Za-zÁÉÍÓÚáéíóúÑñÜü' .-]*$/;
const regexTelefono = /^[0-9+()\-\s]{6,20}$/;

export const emailValido = (valor: string) => regexEmail.test(valor.trim());

export const requerido = (mensaje = 'Este campo es obligatorio'): Regla => (valor) =>
  valor != null && String(valor).trim().length > 0 ? null : mensaje;

export const email = (mensaje = 'Ingresá un correo válido'): Regla => (valor) =>
  valor && valor.trim().length > 0 && !regexEmail.test(valor.trim()) ? mensaje : null;

export const soloNumeros = (mensaje = 'Solo se permiten números'): Regla => (valor) =>
  valor && !soloDigitos.test(valor) ? mensaje : null;

export const ci = (mensaje = 'El CI debe tener entre 5 y 12 dígitos'): Regla => (valor) => {
  if (!valor) return null;
  const limpio = valor.trim();
  if (!soloDigitos.test(limpio)) return 'El CI solo debe contener números';
  if (limpio.length < 5 || limpio.length > 12) return mensaje;
  return null;
};

export const telefono = (mensaje = 'Ingresá un teléfono válido (6 a 20 dígitos)'): Regla => (valor) =>
  valor && valor.trim().length > 0 && !regexTelefono.test(valor.trim()) ? mensaje : null;

export const nombre = (mensaje = 'Ingresá un nombre válido'): Regla => (valor) =>
  valor && valor.trim().length > 0 && !regexNombre.test(valor.trim()) ? mensaje : null;

export const longitud = (
  min: number,
  max: number,
  mensaje = `Debe tener entre ${min} y ${max} caracteres`
): Regla => (valor) => {
  if (!valor) return null;
  const len = valor.trim().length;
  return len >= min && len <= max ? null : mensaje;
};

export const minLongitud = (min: number, mensaje = `Debe tener al menos ${min} caracteres`): Regla => (valor) =>
  valor && valor.trim().length > 0 && valor.trim().length < min ? mensaje : null;

export const maxLongitud = (max: number, mensaje = `Debe tener como máximo ${max} caracteres`): Regla => (valor) =>
  valor && valor.trim().length > max ? mensaje : null;

export const patron = (regex: RegExp, mensaje = 'El formato no es válido'): Regla => (valor) =>
  valor && valor.length > 0 && !regex.test(valor) ? mensaje : null;

export const numeroMin = (min: number, mensaje = `El valor mínimo es ${min}`): Regla => (valor) => {
  if (!valor) return null;
  const n = Number(valor);
  if (Number.isNaN(n)) return 'Ingresá un número válido';
  return n >= min ? null : mensaje;
};

export const numeroMax = (max: number, mensaje = `El valor máximo es ${max}`): Regla => (valor) => {
  if (!valor) return null;
  const n = Number(valor);
  if (Number.isNaN(n)) return 'Ingresá un número válido';
  return n <= max ? null : mensaje;
};

export const fecha = (mensaje = 'Ingresá una fecha válida'): Regla => (valor) =>
  valor && valor.trim().length > 0 && Number.isNaN(new Date(valor).getTime()) ? mensaje : null;

export const fechaNoFutura = (mensaje = 'La fecha no puede ser futura'): Regla => (valor) => {
  if (!valor) return null;
  const d = new Date(valor);
  if (Number.isNaN(d.getTime())) return null;
  const hoy = new Date();
  hoy.setHours(23, 59, 59, 999);
  return d.getTime() <= hoy.getTime() ? null : mensaje;
};

export const fechaPosteriorA = (otra: string, mensaje = 'La fecha debe ser posterior a la fecha de inicio'): Regla => (valor) => {
  if (!valor || !otra) return null;
  const d = new Date(valor);
  const ref = new Date(otra);
  if (Number.isNaN(d.getTime()) || Number.isNaN(ref.getTime())) return null;
  return d.getTime() > ref.getTime() ? null : mensaje;
};

export type Esquema<T extends Record<string, unknown>> = {
  [K in keyof T]?: Regla[];
};

export type Errores<T extends Record<string, unknown>> = Partial<Record<keyof T, string>>;

export const validarCampo = (reglas: Regla[] | undefined, valor: string): string | null => {
  if (!reglas) return null;
  for (const regla of reglas) {
    const error = regla(valor);
    if (error) return error;
  }
  return null;
};

export const validarEsquema = <T extends Record<string, unknown>>(
  esquema: Esquema<T>,
  valores: T
): Errores<T> => {
  const errores: Errores<T> = {};
  for (const clave of Object.keys(esquema) as (keyof T)[]) {
    const valor = valores[clave];
    const error = validarCampo(esquema[clave], valor == null ? '' : String(valor));
    if (error) errores[clave] = error;
  }
  return errores;
};

export const esquemaValido = <T extends Record<string, unknown>>(
  esquema: Esquema<T>,
  valores: T
): boolean => Object.keys(validarEsquema(esquema, valores)).length === 0;
