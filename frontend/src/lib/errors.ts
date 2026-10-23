export type ErrorMap = Record<string, string>;

export interface ApiError {
  response?: {
    status?: number;
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
}

export const extractMessage = (err: unknown, fallback = 'Ocurrió un error inesperado'): string => {
  const e = err as ApiError;
  return e?.response?.data?.message || fallback;
};

export const extractErrors = (err: unknown): ErrorMap => {
  const e = err as ApiError;
  const errors = e?.response?.data?.errors;
  if (!errors) return {};
  return Object.fromEntries(Object.entries(errors).map(([campo, msgs]) => [campo, msgs?.[0] ?? '']));
};
