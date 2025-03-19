import api from './api';
import { Product, ProductAnalysis, SearchParams } from '@/types/product';

export const productService = {
  // 제품 검색
  searchProducts: async (params: SearchParams) => {
    const response = await api.get('/products', { params });
    return response.data;
  },

  // 제품 상세 정보 조회
  getProduct: async (id: number) => {
    const response = await api.get(`/products/${id}`);
    return response.data;
  },

  // 제품 분석
  analyzeProduct: async (id: number) => {
    const response = await api.post(`/products/${id}/analyze`);
    return response.data;
  },

  // 경쟁사 분석
  getCompetitorAnalysis: async (id: number) => {
    const response = await api.get(`/products/${id}/competitors`);
    return response.data;
  },

  // 제품 트렌드 분석
  getProductTrends: async (id: number) => {
    const response = await api.get(`/products/${id}/trends`);
    return response.data;
  },

  // 제품 추가
  createProduct: async (product: Partial<Product>) => {
    const response = await api.post('/products', product);
    return response.data;
  },

  // 제품 수정
  updateProduct: async (id: number, product: Partial<Product>) => {
    const response = await api.put(`/products/${id}`, product);
    return response.data;
  },

  // 제품 삭제
  deleteProduct: async (id: number) => {
    const response = await api.delete(`/products/${id}`);
    return response.data;
  },

  // 제품 이미지 업로드
  uploadImages: async (id: number, images: FormData) => {
    const response = await api.post(`/products/${id}/images`, images, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  // 제품 비교
  compareProducts: async (productIds: number[]) => {
    const response = await api.post('/products/compare', { productIds });
    return response.data;
  }
}; 