import React from 'react';
import {
  Box,
  TextField,
  Button,
  Grid,
  FormControl,
  InputLabel,
  Select,
  MenuItem
} from '@mui/material';
import { Search as SearchIcon } from '@mui/icons-material';

interface ProductSearchFormProps {
  onSearch: (params: SearchParams) => void;
}

interface SearchParams {
  search: string;
  category?: string;
  minPrice?: number;
  maxPrice?: number;
  sortBy: string;
  sortOrder: 'asc' | 'desc';
}

const ProductSearchForm: React.FC<ProductSearchFormProps> = ({ onSearch }) => {
  const [formData, setFormData] = React.useState<SearchParams>({
    search: '',
    category: '',
    minPrice: undefined,
    maxPrice: undefined,
    sortBy: 'created_at',
    sortOrder: 'desc'
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSearch(formData);
  };

  const handleReset = () => {
    setFormData({
      search: '',
      category: '',
      minPrice: undefined,
      maxPrice: undefined,
      sortBy: 'created_at',
      sortOrder: 'desc'
    });
    onSearch({
      search: '',
      sortBy: 'created_at',
      sortOrder: 'desc'
    });
  };

  return (
    <Box component="form" onSubmit={handleSubmit}>
      <Grid container spacing={2}>
        <Grid item xs={12} md={4}>
          <TextField
            fullWidth
            name="search"
            label="검색어"
            value={formData.search}
            onChange={handleChange}
            placeholder="제품명, 키워드 등"
          />
        </Grid>
        <Grid item xs={12} md={2}>
          <FormControl fullWidth>
            <InputLabel>카테고리</InputLabel>
            <Select
              name="category"
              value={formData.category}
              label="카테고리"
              onChange={handleChange}
            >
              <MenuItem value="">전체</MenuItem>
              <MenuItem value="electronics">전자제품</MenuItem>
              <MenuItem value="fashion">패션</MenuItem>
              <MenuItem value="home">홈/리빙</MenuItem>
              <MenuItem value="beauty">뷰티</MenuItem>
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={6} md={2}>
          <TextField
            fullWidth
            name="minPrice"
            label="최소 가격"
            type="number"
            value={formData.minPrice || ''}
            onChange={handleChange}
          />
        </Grid>
        <Grid item xs={6} md={2}>
          <TextField
            fullWidth
            name="maxPrice"
            label="최대 가격"
            type="number"
            value={formData.maxPrice || ''}
            onChange={handleChange}
          />
        </Grid>
        <Grid item xs={12} md={2}>
          <Box sx={{ display: 'flex', gap: 1 }}>
            <Button
              fullWidth
              type="submit"
              variant="contained"
              startIcon={<SearchIcon />}
            >
              검색
            </Button>
            <Button
              fullWidth
              type="button"
              variant="outlined"
              onClick={handleReset}
            >
              초기화
            </Button>
          </Box>
        </Grid>
      </Grid>
    </Box>
  );
};

export default ProductSearchForm; 