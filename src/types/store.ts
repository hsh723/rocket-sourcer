import { KeywordTrend, Product, ProfitAnalysis } from './data';

export interface KeywordState {
  trends: KeywordTrend[];
  loading: boolean;
  error: string | null;
}

export interface ProductState {
  products: Product[];
  searchTerm: string;
  loading: boolean;
  error: string | null;
}

export interface AnalysisState {
  currentAnalysis: ProfitAnalysis | null;
  loading: boolean;
  error: string | null;
}
