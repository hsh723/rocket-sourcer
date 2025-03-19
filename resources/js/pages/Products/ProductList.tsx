import React from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  Box,
  Container,
  Typography,
  Paper,
  Button,
  CircularProgress
} from '@mui/material';
import { Add as AddIcon } from '@mui/icons-material';
import ProductSearchForm from '@components/Products/ProductSearchForm';
import ProductTable from '@components/Products/ProductTable';
import { productService } from '@services/productService';
import { useNavigate } from 'react-router-dom';

const ProductList: React.FC = () => {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = React.useState({
    page: 1,
    limit: 10,
    search: '',
    sortBy: 'created_at',
    sortOrder: 'desc'
  });

  const { data, isLoading, error } = useQuery({
    queryKey: ['products', searchParams],
    queryFn: () => productService.searchProducts(searchParams)
  });

  const handleSearch = (params: any) => {
    setSearchParams(prev => ({ ...prev, ...params, page: 1 }));
  };

  const handlePageChange = (page: number) => {
    setSearchParams(prev => ({ ...prev, page }));
  };

  const handleAddProduct = () => {
    navigate('/products/new');
  };

  if (error) {
    return (
      <Box sx={{ p: 3 }}>
        <Typography color="error">
          에러가 발생했습니다. 다시 시도해 주세요.
        </Typography>
      </Box>
    );
  }

  return (
    <Container maxWidth="lg">
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 3 }}>
          <Typography variant="h4" component="h1">
            제품 목록
          </Typography>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={handleAddProduct}
          >
            새 제품 추가
          </Button>
        </Box>

        <Paper sx={{ p: 3, mb: 3 }}>
          <ProductSearchForm onSearch={handleSearch} />
        </Paper>

        {isLoading ? (
          <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
            <CircularProgress />
          </Box>
        ) : (
          <ProductTable
            products={data?.items || []}
            total={data?.total || 0}
            page={searchParams.page}
            limit={searchParams.limit}
            onPageChange={handlePageChange}
          />
        )}
      </Box>
    </Container>
  );
};

export default ProductList; 