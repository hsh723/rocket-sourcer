export interface AnalysisData {
  productName: string;
  profitAnalysis: {
    costs: {
      name: string;
      value: number;
      color: string;
    }[];
    revenues: {
      name: string;
      value: number;
      color: string;
    }[];
    comparison: {
      category: string;
      values: {
        name: string;
        value: number;
        color: string;
      }[];
    }[];
  };
  competitorAnalysis: {
    priceComparison: {
      category: string;
      values: {
        name: string;
        value: number;
        color: string;
      }[];
    }[];
    marketShare: {
      category: string;
      values: {
        name: string;
        value: number;
        color: string;
      }[];
    }[];
  };
  trendAnalysis: {
    salesTrend: {
      date: string;
      metrics: {
        name: string;
        value: number;
        color: string;
      }[];
    }[];
    priceTrend: {
      date: string;
      metrics: {
        name: string;
        value: number;
        color: string;
      }[];
    }[];
  };
} 