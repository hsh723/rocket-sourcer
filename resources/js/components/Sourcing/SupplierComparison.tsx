import React from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  Box,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Rating,
  Chip,
  CircularProgress,
  Button
} from '@mui/material';
import { Email as EmailIcon, Download as DownloadIcon } from '@mui/icons-material';
import { sourcingService } from '@/services/sourcingService';

interface SupplierComparisonProps {
  supplierIds: string[];
}

const SupplierComparison: React.FC<SupplierComparisonProps> = ({ supplierIds }) => {
  const { data, isLoading, error } = useQuery({
    queryKey: ['supplierComparison', supplierIds],
    queryFn: () => sourcingService.compareSuppliers(supplierIds),
    enabled: supplierIds.length > 0
  });

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
        공급업체 정보를 불러오는데 실패했습니다.
      </Typography>
    );
  }

  if (!data) return null;

  return (
    <Box>
      <Typography variant="h6" gutterBottom>
        공급업체 비교
      </Typography>

      <TableContainer component={Paper} sx={{ mb: 2 }}>
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>항목</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  {supplier.name}
                </TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            <TableRow>
              <TableCell>평점</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  <Rating value={supplier.rating} readOnly size="small" />
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>거래 실적</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  {supplier.transactionCount.toLocaleString()}건
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>응답률</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  {supplier.responseRate}%
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>평균 응답 시간</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  {supplier.avgResponseTime}시간
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>인증 상태</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  <Chip
                    label={supplier.verificationType}
                    size="small"
                    color={
                      supplier.verificationType === 'Verified' ? 'success' :
                      supplier.verificationType === 'Gold' ? 'warning' : 'default'
                    }
                  />
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>주요 제품</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  {supplier.mainCategories.join(', ')}
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>최소 주문량</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  {supplier.moq} 개
                </TableCell>
              ))}
            </TableRow>
            <TableRow>
              <TableCell>샘플 제공</TableCell>
              {data.suppliers.map((supplier) => (
                <TableCell key={supplier.id} align="center">
                  {supplier.sampleAvailable ? '가능' : '불가능'}
                </TableCell>
              ))}
            </TableRow>
          </TableBody>
        </Table>
      </TableContainer>

      <Box sx={{ display: 'flex', gap: 1, justifyContent: 'flex-end' }}>
        <Button
          size="small"
          startIcon={<EmailIcon />}
          variant="outlined"
          onClick={() => {/* 일괄 견적 요청 처리 */}}
        >
          일괄 견적 요청
        </Button>
        <Button
          size="small"
          startIcon={<DownloadIcon />}
          variant="outlined"
          onClick={() => {/* 비교 결과 다운로드 처리 */}}
        >
          비교 결과 다운로드
        </Button>
      </Box>
    </Box>
  );
};

export default SupplierComparison; 