import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { Product } from '../../types/data';

interface ProductState {
  products: Product[];
  loading: boolean;
  error: string | null;
}

const initialState: ProductState = {
  products: [],
  loading: false,
  error: null
};

export const searchProducts = createAsyncThunk(
  'products/search',
  async (searchTerm: string) => {
    const response = await fetch(`/api/products/search?q=${searchTerm}`);
    return response.json();
  }
);

const productSlice = createSlice({
  name: 'products',
  initialState,
  reducers: {
    clearProducts: (state) => {
      state.products = [];
    }
  },
  extraReducers: (builder) => {
    builder
      .addCase(searchProducts.pending, (state) => {
        state.loading = true;
      })
      .addCase(searchProducts.fulfilled, (state, action) => {
        state.loading = false;
        state.products = action.payload;
      })
      .addCase(searchProducts.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message || '오류가 발생했습니다';
      });
  }
});

export const { clearProducts } = productSlice.actions;
export default productSlice.reducer;
