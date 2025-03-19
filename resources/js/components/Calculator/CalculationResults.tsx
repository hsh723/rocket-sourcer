import React from 'react';
import {
  Box,
  Grid,
  Typography,
  Divider,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow
} from '@mui/material';
import { CalculationResult } from '@/types/calculator';

interface CalculationResultsProps {
  result: CalculationResult;
}

const CalculationResults: React.FC<CalculationResultsProps> = ({ result }) => {
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ko-KR', {
      style: 'currency',
      currency: 'KRW'
    }).format(amount);
  };

  const formatPercent = (value: number) => {
    return `${value.toFixed(1)}%`;
  };

  return (
    <Box>
      {/* 주요 지표 */}
      <Grid container spacing={2} sx={{ mb: 3 }}>
        <Grid item xs={12} md={4}>
          <Paper sx={{ p: 2, textAlign: 'center' }}>
            <Typography variant="subtitle2" color="text.secondary">
              예상 순이익 (월)
            </Typography>
            <Typography variant="h5" color="primary">
              {formatCurrency(result.monthlyProfit)}
            </Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} md={4}>
          <Paper sx={{ p: 2, textAlign: 'center' }}>
            <Typography variant="subtitle2" color="text.secondary">
              마진율
            </Typography>
            <Typography variant="h5" color="primary">
              {formatPercent(result.marginRate)}
            </Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} md={4}>
          <Paper sx={{ p: 2, textAlign: 'center' }}>
            <Typography variant="subtitle2" color="text.secondary">
              ROI
            </Typography>
            <Typography variant="h5" color="primary">
              {formatPercent(result.roi)}
            </Typography>
          </Paper>
        </Grid>
      </Grid>

      {/* 상세 분석 */}
      <TableContainer component={Paper} sx={{ mb: 3 }}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>항목</TableCell>
              <TableCell align="right">단위 금액</TableCell>
              <TableCell align="right">월 합계</TableCell>
              <TableCell align="right">비율</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            <TableRow>
              <TableCell>판매가</TableCell>
              <TableCell align="right">
                {formatCurrency(result.unitPrice)}
              </TableCell>
              <TableCell align="right">
                {formatCurrency(result.monthlyRevenue)}
              </TableCell>
              <TableCell align="right">100%</TableCell>
            </TableRow>
            <TableRow>
              <TableCell>원가</TableCell>
              <TableCell align="right">
                {formatCurrency(result.unitCost)}
              </TableCell>
              <TableCell align="right">
                {formatCurrency(result.monthlyCost)}
              </TableCell>
              <TableCell align="right">
                {formatPercent(result.costRate)}
              </TableCell>
            </TableRow>
            <TableRow>
              <TableCell>배송비</TableCell>
              <TableCell align="right">
                {formatCurrency(result.shippingCost)}
              </TableCell>
              <TableCell align="right">
                {formatCurrency(result.monthlyShippingCost)}
              </TableCell>
              <TableCell align="right">
                {formatPercent(result.shippingRate)}
              </TableCell>
            </TableRow>
            <TableRow>
              <TableCell>마켓플레이스 수수료</TableCell>
              <TableCell align="right">
                {formatCurrency(result.marketplaceFee)}
              </TableCell>
              <TableCell align="right">
                {formatCurrency(result.monthlyMarketplaceFee)}
              </TableCell>
              <TableCell align="right">
                {formatPercent(result.marketplaceFeeRate)}
              </TableCell>
            </TableRow>
            <TableRow>
              <TableCell>마케팅 비용</TableCell>
              <TableCell align="right">
                {formatCurrency(result.marketingCost)}
              </TableCell>
              <TableCell align="right">
                {formatCurrency(result.monthlyMarketingCost)}
              </TableCell>
              <TableCell align="right">
                {formatPercent(result.marketingRate)}
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </TableContainer>

      {/* 손익분기점 분석 */}
      <Paper sx={{ p: 2, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          손익분기점 분석
        </Typography>
        <Grid container spacing={2}>
          <Grid item xs={12} md={4}>
            <Typography variant="subtitle2" color="text.secondary">
              손익분기 판매량
            </Typography>
            <Typography variant="h6">
              {result.breakeven.units.toLocaleString()} 개
            </Typography>
          </Grid>
          <Grid item xs={12} md={4}>
            <Typography variant="subtitle2" color="text.secondary">
              손익분기 매출액
            </Typography>
            <Typography variant="h6">
              {formatCurrency(result.breakeven.revenue)}
            </Typography>
          </Grid>
          <Grid item xs={12} md={4}>
            <Typography variant="subtitle2" color="text.secondary">
              손익분기 도달 예상 기간
            </Typography>
            <Typography variant="h6">
              {result.breakeven.periodMonths} 개월
            </Typography>
          </Grid>
        </Grid>
      </Paper>

      {/* 투자 회수 분석 */}
      <Paper sx={{ p: 2 }}>
        <Typography variant="h6" gutterBottom>
          투자 회수 분석
        </Typography>
        <Grid container spacing={2}>
          <Grid item xs={12} md={4}>
            <Typography variant="subtitle2" color="text.secondary">
              초기 투자금
            </Typography>
            <Typography variant="h6">
              {formatCurrency(result.investment.initial)}
            </Typography>
          </Grid>
          <Grid item xs={12} md={4}>
            <Typography variant="subtitle2" color="text.secondary">
              월 평균 순이익
            </Typography>
            <Typography variant="h6">
              {formatCurrency(result.monthlyProfit)}
            </Typography>
          </Grid>
          <Grid item xs={12} md={4}>
            <Typography variant="subtitle2" color="text.secondary">
              예상 회수 기간
            </Typography>
            <Typography variant="h6">
              {result.investment.recoveryPeriodMonths} 개월
            </Typography>
          </Grid>
        </Grid>
      </Paper>
    </Box>
  );
};

export default CalculationResults; 