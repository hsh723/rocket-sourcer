import api from './api';

export interface KeywordParams {
  searchTerm?: string;
  competition?: string;
  trend?: string;
  page?: number;
  limit?: number;
}

export interface Keyword {
  id: number;
  keyword: string;
  searchVolume: number;
  competition: number;
  trend: 'up' | 'down' | 'stable';
  averagePrice: number;
  estimatedSales: number;
  updatedAt: string;
}

export const keywordService = {
  getKeywords: (params?: KeywordParams) =>
    api.get<Keyword[]>('/keywords', { params }),

  getKeyword: (id: number) =>
    api.get<Keyword>(`/keywords/${id}`),

  analyzeKeyword: (keyword: string) =>
    api.post<Keyword>('/keywords/analyze', { keyword }),

  getKeywordHistory: (id: number) =>
    api.get(`/keywords/${id}/history`),

  getKeywordCompetitors: (id: number) =>
    api.get(`/keywords/${id}/competitors`),

  getKeywordProducts: (id: number) =>
    api.get(`/keywords/${id}/products`),

  getKeywordTrends: (id: number) =>
    api.get(`/keywords/${id}/trends`),
}; 