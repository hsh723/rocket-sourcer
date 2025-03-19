import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { KeywordState } from '../../types/store';
import { apiUtils } from '../../utils/api';
import { logger } from '../../utils/logger';
import { validator } from '../../utils/validator';
import { API_CONFIG } from '../../config/api';

export const fetchKeywordTrends = createAsyncThunk(
  'keywords/fetchTrends',
  async (keyword: string) => {
    if (!validator.isValidKeyword(keyword)) {
      throw new Error('유효하지 않은 키워드입니다');
    }
    logger.log('info', `키워드 트렌드 조회: ${keyword}`);
    return await apiUtils.fetchData(API_CONFIG.endpoints.trends, { keyword });
  }
);

export const fetchRelatedKeywords = createAsyncThunk(
  'keywords/fetchRelated',
  async (keyword: string) => {
    if (!validator.isValidKeyword(keyword)) {
      throw new Error('유효하지 않은 키워드입니다');
    }
    logger.log('info', `연관 키워드 조회: ${keyword}`);
    return await apiUtils.fetchData(API_CONFIG.endpoints.related, { keyword });
  }
);

export const fetchProductAnalytics = createAsyncThunk(
  'keywords/fetchProducts',
  async (filters: any) => {
    return [];
  }
);

export const calculateProfitability = createAsyncThunk(
  'keywords/calculateProfit',
  async (params: any) => {
    return {};
  }
);

export const generateStrategy = createAsyncThunk(
  'keywords/generateStrategy',
  async (data: any) => {
    return {};
  }
);

const initialState: KeywordState = {
  trends: [],
  relatedKeywords: [],
  products: [],
  profitAnalysis: null,
  strategy: null,
  loading: false,
  error: null
};

const keywordSlice = createSlice({
  name: 'keywords',
  initialState,
  reducers: {
    clearTrends: (state) => {
      state.trends = [];
      state.error = null;
    },
    clearAll: (state) => {
      state.trends = [];
      state.relatedKeywords = [];
      state.products = [];
      state.profitAnalysis = null;
      state.strategy = null;
      state.error = null;
    }
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchKeywordTrends.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchKeywordTrends.fulfilled, (state, action) => {
        state.trends = action.payload;
        state.loading = false;
      })
      .addCase(fetchKeywordTrends.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message || null;
      })
      .addCase(fetchRelatedKeywords.pending, (state) => {
        state.loading = true;
      })
      .addCase(fetchRelatedKeywords.fulfilled, (state, action) => {
        state.relatedKeywords = action.payload;
        state.loading = false;
      })
      .addCase(fetchProductAnalytics.fulfilled, (state, action) => {
        state.products = action.payload;
        state.loading = false;
      })
      .addCase(calculateProfitability.fulfilled, (state, action) => {
        state.profitAnalysis = action.payload;
        state.loading = false;
      })
      .addCase(generateStrategy.fulfilled, (state, action) => {
        state.strategy = action.payload;
        state.loading = false;
      });
  }
});

export const { clearTrends, clearAll } = keywordSlice.actions;
export default keywordSlice.reducer;
