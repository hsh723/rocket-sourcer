import React from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import {
  Box,
  Container,
  Grid,
  Paper,
  Typography,
  CircularProgress
} from '@mui/material';
import SourcingResults from '@/components/Sourcing/SourcingResults';
import SupplierComparison from '@/components/Sourcing/SupplierComparison';
import ProductMatch from '@/components/Sourcing/ProductMatch';
import SearchForm from '@/components/Sourcing/SearchForm';
import { sourcingService } from '@/services/sourcingService';
import { SourcingParams, SourcingResult } from '@/types/sourcing';

const SourcingSearch: React.FC = () => {
  const [searchParams, setSearchParams] = React.useState<SourcingParams>({
    keyword: '',
    category: '',
    minPrice: undefined,
    maxPrice: undefined,
    minQuantity: undefined,
    supplierType: 'all',
    sortBy: 'relevance'
  });

  const [selectedSuppliers, setSelectedSuppliers] = React.useState<string[]>([]);
  const [selectedProduct, setSelectedProduct] = React.useState<string | null>(null);

  const searchMutation = useMutation({
    mutationFn: sourcingService.searchProducts,
    onSuccess: (data) => {
      // 검색 결과 처리
    }
  });

  const handleSearch = (params: SourcingParams) => {
    setSearchParams(params);
    searchMutation.mutate(params);
  };

  const handleSupplierSelect = (supplierId: string) => {
    setSelectedSuppliers(prev => {
      if (prev.includes(supplierId)) {
        return prev.filter(id => id !== supplierId);
      }
      return [...prev, supplierId].slice(0, 3); // 최대 3개까지 선택 가능
    });
  };

  const handleProductSelect = (productId: string) => {
    setSelectedProduct(productId);
  };

  return (
    <Container maxWidth="xl">
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          해외 소싱 검색
        </Typography>

        <Grid container spacing={3}>
          {/* 검색 폼 */}
          <Grid item xs={12}>
            <Paper sx={{ p: 3, mb: 3 }}>
              <SearchForm
                initialValues={searchParams}
                onSubmit={handleSearch}
                isLoading={searchMutation.isPending}
              />
            </Paper>
          </Grid>

          {/* 검색 결과 */}
          <Grid item xs={12} md={8}>
            {searchMutation.isPending ? (
              <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
                <CircularProgress />
              </Box>
            ) : searchMutation.data ? (
              <SourcingResults
                results={searchMutation.data}
                onSupplierSelect={handleSupplierSelect}
                onProductSelect={handleProductSelect}
                selectedSuppliers={selectedSuppliers}
                selectedProduct={selectedProduct}
              />
            ) : null}
          </Grid>

          {/* 비교 및 분석 */}
          <Grid item xs={12} md={4}>
            {selectedSuppliers.length > 0 && (
              <Paper sx={{ p: 3, mb: 3 }}>
                <SupplierComparison supplierIds={selectedSuppliers} />
              </Paper>
            )}

            {selectedProduct && (
              <Paper sx={{ p: 3 }}>
                <ProductMatch
                  productId={selectedProduct}
                  suppliers={selectedSuppliers}
                />
              </Paper>
            )}
          </Grid>
        </Grid>
      </Box>
    </Container>
  );
};

export default SourcingSearch; 