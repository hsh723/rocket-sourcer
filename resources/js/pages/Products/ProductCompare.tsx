import React from 'react';
import {
  Box,
  Container,
  Typography,
  Autocomplete,
  TextField,
  Button,
  Alert
} from '@mui/material';
import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import ProductComparison from '@/components/Products/ProductComparison';
import { productService } from '@/services/productService';

const ProductCompare: React.FC = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const productIds = searchParams.get('ids')?.split(',').map(Number) || [];
  
  const [selectedProducts, setSelectedProducts] = React.useState<number[]>(productIds);

  const { data: products } = useQuery({
    queryKey: ['products'],
    queryFn: () => productService.searchProducts({ limit: 100 })
  });

  const handleProductSelect = (productId: number) => {
    if (selectedProducts.length >= 4) {
      return; // 최대 4개까지만 비교 가능
    }
    
    const newSelectedProducts = [...selectedProducts, productId];
    setSelectedProducts(newSelectedProducts);
    setSearchParams({ ids: newSelectedProducts.join(',') });
  };

  const handleRemoveProduct = (productId: number) => {
    const newSelectedProducts = selectedProducts.filter(id => id !== productId);
    setSelectedProducts(newSelectedProducts);
    setSearchParams(
      newSelectedProducts.length > 0 ? { ids: newSelectedProducts.join(',') } : {}
    );
  };

  return (
    <Container maxWidth="xl">
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          제품 비교
        </Typography>

        <Box sx={{ mb: 3 }}>
          <Autocomplete
            options={products?.items || []}
            getOptionLabel={(option) => option.name}
            isOptionEqualToValue={(option, value) => option.id === value.id}
            onChange={(event, product) => {
              if (product && !selectedProducts.includes(product.id)) {
                handleProductSelect(product.id);
              }
            }}
            renderInput={(params) => (
              <TextField
                {...params}
                label="제품 추가"
                placeholder="비교할 제품을 선택하세요"
              />
            )}
            disabled={selectedProducts.length >= 4}
          />
          {selectedProducts.length >= 4 && (
            <Alert severity="info" sx={{ mt: 1 }}>
              최대 4개의 제품까지 비교할 수 있습니다.
            </Alert>
          )}
        </Box>

        {selectedProducts.length > 0 ? (
          <ProductComparison
            productIds={selectedProducts}
            onRemoveProduct={handleRemoveProduct}
          />
        ) : (
          <Alert severity="info">
            비교할 제품을 선택해주세요.
          </Alert>
        )}
      </Box>
    </Container>
  );
};

export default ProductCompare; 