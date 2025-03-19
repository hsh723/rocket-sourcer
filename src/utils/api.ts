import axios, { AxiosInstance, AxiosResponse } from 'axios';

const axiosInstance: AxiosInstance = axios.create({
  baseURL: process.env.REACT_APP_API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
});

export const apiUtils = {
  async fetchData<T>(endpoint: string, params?: any): Promise<T> {
    try {
      const response: AxiosResponse<T> = await axiosInstance.get(endpoint, { params });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  handleError(error: any): Error {
    if (axios.isAxiosError(error)) {
      return new Error(error.response?.data?.message || '네트워크 오류가 발생했습니다');
    }
    return error;
  }
};
