import api from './api';
import { AxiosResponse } from 'axios';

interface LoginCredentials {
  email: string;
  password: string;
}

interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

interface ResetPasswordData {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

interface ProfileData {
  name: string;
  email: string;
  phone?: string;
  company?: string;
  position?: string;
}

export const authService = {
  login: async (credentials: LoginCredentials): Promise<AxiosResponse> => {
    const response = await api.post('/auth/login', credentials);
    if (response.data.token) {
      localStorage.setItem('token', response.data.token);
      api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
    }
    return response;
  },

  register: async (data: RegisterData): Promise<AxiosResponse> => {
    return await api.post('/auth/register', data);
  },

  logout: async (): Promise<void> => {
    await api.post('/auth/logout');
    localStorage.removeItem('token');
    delete api.defaults.headers.common['Authorization'];
  },

  forgotPassword: async (email: string): Promise<AxiosResponse> => {
    return await api.post('/auth/forgot-password', { email });
  },

  resetPassword: async (data: ResetPasswordData): Promise<AxiosResponse> => {
    return await api.post('/auth/reset-password', data);
  },

  getProfile: async (): Promise<AxiosResponse> => {
    return await api.get('/auth/profile');
  },

  updateProfile: async (data: ProfileData): Promise<AxiosResponse> => {
    return await api.put('/auth/profile', data);
  },

  changePassword: async (data: {
    current_password: string;
    password: string;
    password_confirmation: string;
  }): Promise<AxiosResponse> => {
    return await api.put('/auth/password', data);
  },

  refreshToken: async (): Promise<AxiosResponse> => {
    const response = await api.post('/auth/refresh');
    if (response.data.token) {
      localStorage.setItem('token', response.data.token);
      api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
    }
    return response;
  }
}; 