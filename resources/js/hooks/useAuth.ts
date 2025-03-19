import { create } from 'zustand';
import api from '@services/api';

interface AuthState {
  user: any | null;
  isAuthenticated: boolean;
  token: string | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  register: (data: RegisterData) => Promise<void>;
}

interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export const useAuth = create<AuthState>((set) => ({
  user: null,
  isAuthenticated: !!localStorage.getItem('token'),
  token: localStorage.getItem('token'),

  login: async (email: string, password: string) => {
    try {
      const response = await api.post('/auth/login', { email, password });
      const { token, user } = response.data;
      
      localStorage.setItem('token', token);
      set({ user, isAuthenticated: true, token });
    } catch (error) {
      throw error;
    }
  },

  logout: () => {
    localStorage.removeItem('token');
    set({ user: null, isAuthenticated: false, token: null });
  },

  register: async (data: RegisterData) => {
    try {
      const response = await api.post('/auth/register', data);
      const { token, user } = response.data;
      
      localStorage.setItem('token', token);
      set({ user, isAuthenticated: true, token });
    } catch (error) {
      throw error;
    }
  }
})); 