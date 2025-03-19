import api from './api';
import { SourcingParams, SourcingResult, SupplierInfo, ProductMatch } from '@/types/sourcing';

export const sourcingService = {
  // 제품 소싱 검색
  searchProducts: async (params: SourcingParams) => {
    const response = await api.get('/sourcing/search', { params });
    return response.data;
  },

  // 공급업체 정보 조회
  getSupplierInfo: async (supplierId: string) => {
    const response = await api.get(`/sourcing/suppliers/${supplierId}`);
    return response.data;
  },

  // 공급업체 비교
  compareSuppliers: async (supplierIds: string[]) => {
    const response = await api.post('/sourcing/suppliers/compare', { supplierIds });
    return response.data;
  },

  // 제품 매칭 분석
  analyzeProductMatch: async (productId: number, sourcingId: string) => {
    const response = await api.post('/sourcing/match', {
      productId,
      sourcingId
    });
    return response.data;
  },

  // 견적 요청
  requestQuotation: async (data: {
    supplierId: string;
    productId: string;
    quantity: number;
    specifications?: any;
  }) => {
    const response = await api.post('/sourcing/quotation', data);
    return response.data;
  },

  // 샘플 요청
  requestSample: async (data: {
    supplierId: string;
    productId: string;
    specifications?: any;
  }) => {
    const response = await api.post('/sourcing/sample', data);
    return response.data;
  },

  // 트렌드 분석
  analyzeTrends: async (params: {
    category: string;
    timeRange: string;
    metrics: string[];
  }) => {
    const response = await api.get('/sourcing/trends', { params });
    return response.data;
  },

  // 이미지 갤러리 조회
  getProductImages: async (productId: string) => {
    const response = await api.get(`/sourcing/products/${productId}/images`);
    return response.data;
  },

  // 검색 결과 저장
  saveSearchResult: async (data: SourcingResult) => {
    const response = await api.post('/sourcing/save', data);
    return response.data;
  },

  // 저장된 검색 결과 조회
  getSavedSearches: async () => {
    const response = await api.get('/sourcing/saved');
    return response.data;
  },

  // 검색 결과 내보내기
  exportSearchResults: async (searchId: string, format: 'pdf' | 'excel') => {
    const response = await api.get(`/sourcing/export/${searchId}`, {
      params: { format },
      responseType: 'blob'
    });
    return response.data;
  }
}; 