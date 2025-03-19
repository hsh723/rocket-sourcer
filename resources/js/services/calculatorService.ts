import api from './api';
import { CalculationParams, CalculationResult } from '@/types/calculator';

export const calculatorService = {
  // 수익성 계산
  calculateProfit: async (params: CalculationParams) => {
    const response = await api.post('/calculator/profit', params);
    return response.data;
  },

  // ROI 시뮬레이션
  simulateROI: async (params: CalculationParams) => {
    const response = await api.post('/calculator/roi', params);
    return response.data;
  },

  // 손익분기점 계산
  calculateBreakeven: async (params: CalculationParams) => {
    const response = await api.post('/calculator/breakeven', params);
    return response.data;
  },

  // 마진 분석
  analyzeMargins: async (params: CalculationParams) => {
    const response = await api.post('/calculator/margins', params);
    return response.data;
  },

  // 계산 결과 저장
  saveCalculation: async (result: CalculationResult) => {
    const response = await api.post('/calculator/save', result);
    return response.data;
  },

  // 저장된 계산 결과 조회
  getSavedCalculations: async () => {
    const response = await api.get('/calculator/saved');
    return response.data;
  },

  // 계산 결과 내보내기
  exportCalculation: async (id: number, format: 'pdf' | 'excel') => {
    const response = await api.get(`/calculator/export/${id}`, {
      params: { format },
      responseType: 'blob'
    });
    return response.data;
  }
}; 