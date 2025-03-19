export interface KeywordTrend {
  keyword: string;
  searchVolume: number;
  competition: number;
  timeline: {
    date: string;
    volume: number;
  }[];
}

export interface Product {
  id: string;
  name: string;
  category: string;
  price: number;
  rating: number;
  reviews: number;
  salesRank: number;
}

export interface ProfitAnalysis {
  estimatedSales: number;
  estimatedRevenue: number;
  estimatedProfit: number;
  competitorCount: number;
  marketSize: number;
}

export interface APIError {
  code: string;
  message: string;
  status: number;
}
