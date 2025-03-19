import React from 'react';
import {
  Box,
  Card,
  CardContent,
  CardMedia,
  Grid,
  Typography,
  Chip,
  IconButton,
  Tooltip,
  Rating
} from '@mui/material';
import {
  Compare as CompareIcon,
  AddShoppingCart as CartIcon,
  Info as InfoIcon
} from '@mui/icons-material';
import { SourcingResult } from '@/types/sourcing';

interface SourcingResultsProps {
  results: SourcingResult[];
  onSupplierSelect: (supplierId: string) => void;
  onProductSelect: (productId: string) => void;
  selectedSuppliers: string[];
  selectedProduct: string | null;
}

const SourcingResults: React.FC<SourcingResultsProps> = ({
  results,
  onSupplierSelect,
  onProductSelect,
  selectedSuppliers,
  selectedProduct
}) => {
  const formatPrice = (price: number, currency: string) => {
    return new Intl.NumberFormat('ko-KR', {
      style: 'currency',
      currency
    }).format(price);
  };

  return (
    <Grid container spacing={2}>
      {results.map((result) => (
        <Grid item xs={12} sm={6} md={4} key={result.id}>
          <Card
            sx={{
              height: '100%',
              display: 'flex',
              flexDirection: 'column',
              position: 'relative'
            }}
          >
            <CardMedia
              component="img"
              height="200"
              image={result.imageUrl}
              alt={result.productName}
              sx={{ objectFit: 'contain' }}
            />
            <CardContent sx={{ flexGrow: 1 }}>
              <Typography gutterBottom variant="h6" component="div">
                {result.productName}
              </Typography>
              
              <Box sx={{ mb: 1 }}>
                <Typography variant="h6" color="primary">
                  {formatPrice(result.price, result.currency)}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  최소 주문 수량: {result.moq} 개
                </Typography>
              </Box>

              <Box sx={{ mb: 1 }}>
                <Typography variant="body2" color="text.secondary">
                  공급업체: {result.supplierName}
                </Typography>
                <Rating
                  value={result.supplierRating}
                  readOnly
                  size="small"
                  sx={{ mr: 1 }}
                />
                <Chip
                  label={result.supplierType}
                  size="small"
                  color={
                    result.supplierType === 'Verified' ? 'success' :
                    result.supplierType === 'Gold' ? 'warning' : 'default'
                  }
                />
              </Box>

              <Box sx={{ display: 'flex', gap: 1, mt: 2 }}>
                <Tooltip title="공급업체 비교에 추가">
                  <IconButton
                    size="small"
                    color={selectedSuppliers.includes(result.supplierId) ? 'primary' : 'default'}
                    onClick={() => onSupplierSelect(result.supplierId)}
                  >
                    <CompareIcon />
                  </IconButton>
                </Tooltip>
                <Tooltip title="제품 매칭 분석">
                  <IconButton
                    size="small"
                    color={selectedProduct === result.id ? 'primary' : 'default'}
                    onClick={() => onProductSelect(result.id)}
                  >
                    <InfoIcon />
                  </IconButton>
                </Tooltip>
                <Tooltip title="견적 요청">
                  <IconButton size="small">
                    <CartIcon />
                  </IconButton>
                </Tooltip>
              </Box>
            </CardContent>
          </Card>
        </Grid>
      ))}
    </Grid>
  );
};

export default SourcingResults; 