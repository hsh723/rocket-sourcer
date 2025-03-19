export const API_CONFIG = {
  baseUrl: process.env.REACT_APP_API_URL,
  endpoints: {
    trends: '/api/trends',
    related: '/api/related',
    products: '/api/products',
    profitability: '/api/profitability',
    strategy: '/api/strategy'
  }
};
