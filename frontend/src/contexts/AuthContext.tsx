import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import api from '../api/axios';
import type { Usuario } from '../types/auth';

interface AuthContextType {
  user: Usuario | null;
  token: string | null;
  isLoading: boolean;
  login: (ci: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  cambiarContrasena: (password_actual: string, password: string, password_confirmation: string) => Promise<void>;
  setUser: (user: Usuario | null) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const queryClient = useQueryClient();
  const [user, setUser] = useState<Usuario | null>(null);
  const [token, setToken] = useState<string | null>(localStorage.getItem('token'));
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const cargarUsuario = async () => {
      if (!token) {
        setIsLoading(false);
        return;
      }
      try {
        const { data } = await api.get('/auth/me');
        setUser(data.user);
      } catch {
        localStorage.removeItem('token');
        setToken(null);
        setUser(null);
      } finally {
        setIsLoading(false);
      }
    };

    cargarUsuario();
  }, [token]);

  const login = async (ci: string, password: string) => {
    const { data } = await api.post('/auth/login', { ci, password });
    queryClient.clear();
    localStorage.setItem('token', data.token);
    setToken(data.token);
    setUser(data.user);
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } finally {
      queryClient.clear();
      localStorage.removeItem('token');
      setToken(null);
      setUser(null);
    }
  };

  const cambiarContrasena = async (
    password_actual: string,
    password: string,
    password_confirmation: string
  ) => {
    await api.post('/auth/cambiar-contrasena', {
      password_actual,
      password,
      password_confirmation,
    });
    if (user) {
      setUser({ ...user, debe_cambiar_password: false });
    }
  };

  return (
    <AuthContext.Provider
      value={{ user, token, isLoading, login, logout, cambiarContrasena, setUser }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth debe usarse dentro de AuthProvider');
  }
  return context;
};
