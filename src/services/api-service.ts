import { apiClient } from './api-client';
import { KeywordTrend, Product, ProfitAnalysis } from '../types/data';

export class APIService {
  async searchKeywords(query: string): Promise<KeywordTrend[]> {
    return apiClient.get<KeywordTrend[]>('/keywords/search', { query });
  }

  async getKeywordTrend(keyword: string): Promise<KeywordTrend> {
    return apiClient.get<KeywordTrend>(`/keywords/trend/${keyword}`);
  }

  async searchProducts(params: {
    keyword?: string;
    category?: string;
  }): Promise<Product[]> {
    return apiClient.get<Product[]>('/products/search', params);
  }

  async analyzeProfitability(productId: string): Promise<ProfitAnalysis> {
    return apiClient.get<ProfitAnalysis>(`/analysis/profit/${productId}`);
  }
}

export const apiService = new APIService();
