import { AxiosError } from 'axios';
import { APIError } from '../types/data';

export const handleAPIError = (error: AxiosError): never => {
  const apiError: APIError = {
    code: error.code || 'UNKNOWN_ERROR',
    message: error.message || '알 수 없는 오류가 발생했습니다.',
    status: error.response?.status || 500
  };

  console.error('[API Error]', {
    error: apiError,
    url: error.config?.url,
    method: error.config?.method
  });

  throw apiError;
};

export const isAPIError = (error: any): error is APIError => {
  return error && 'code' in error && 'message' in error && 'status' in error;
};
