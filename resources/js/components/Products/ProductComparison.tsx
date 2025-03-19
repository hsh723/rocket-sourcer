import React from 'react';
import {
  Box,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
  IconButton,
  Chip,
  CircularProgress,
  Button
} from '@mui/material';
import {
  Close as CloseIcon,
  FileDownload as DownloadIcon
} from '@mui/icons-material';
import { useQuery } from '@tanstack/react-query';
import { productService } from '@/services/productService';

interface ProductComparisonProps {
  productIds: number[];
  onRemoveProduct: (id: number) => void;
}

const ProductComparison: React.FC<ProductComparisonProps> = ({
  productIds,
  onRemoveProduct
}) => {
  const { data, isLoading, error } = useQuery({
    queryKey: ['productComparison', productIds],
    queryFn: () => productService.compareProducts(productIds),
    enabled: productIds.length > 0
  });

  const handleExport = async () => {
    try {
      const blob = await productService.exportComparison(productIds);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'product-comparison.xlsx';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error) {
    return (
      <Typography color="error">
        제품 비교 정보를 불러오는데 실패했습니다.
      </Typography>
    );
  }

  if (!data || productIds.length === 0) return null;

  return (
    <Paper sx={{ p: 2 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6" sx={{ flexGrow: 1 }}>
          제품 비교
        </Typography>
        <Button
          startIcon={<DownloadIcon />}
          onClick={handleExport}
          variant="outlined"
          size="small"
        >
          내보내기
        </Button>
      </Box>

      <TableContainer>
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>항목</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  <Box sx={{ position: 'relative', pr: 4 }}>
                    {product.name}
                    <IconButton
                      size="small"
                      sx={{ position: 'absolute', right: -8, top: -8 }}
                      onClick={() => onRemoveProduct(product.id)}
                    >
                      <CloseIcon fontSize="small" />
                    </IconButton>
                  </Box>
                </TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            <TableRow>
              <TableCell>이미지</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  <img
                    src={product.thumbnail}
                    alt={product.name}
                    style={{ width: 80, height: 80, objectFit: 'contain' }}
                  />
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>가격</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  {new Intl.NumberFormat('ko-KR', {
                    style: 'currency',
                    currency: 'KRW'
                  }).format(product.price)}
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>마진율</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  {product.marginRate}%
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>공급업체</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  {product.supplier}
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>최소 주문량</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  {product.moq} 개
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>배송비</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  {new Intl.NumberFormat('ko-KR', {
                    style: 'currency',
                    currency: 'KRW'
                  }).format(product.shippingCost)}
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>평균 배송 기간</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  {product.shippingDays}일
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>상태</TableCell>
              {data.products.map((product) => (
                <TableCell key={product.id} align="center">
                  <Chip
                    label={product.status}
                    size="small"
                    color={
                      product.status === 'active' ? 'success' :
                      product.status === 'draft' ? 'default' : 'error'
                    }
                  />
                </TableCell>
              ))}
            </TableRow>
            {data.specifications.map((spec) => (
              <TableRow key={spec.name}>
                <TableCell>{spec.name}</TableCell>
                {data.products.map((product) => (
                  <TableCell key={product.id} align="center">
                    {product.specifications[spec.name] || '-'}
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    </Paper>
  );
};

export default ProductComparison; 