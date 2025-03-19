import React from 'react';
import { useMutation } from '@tanstack/react-query';
import {
  Box,
  Container,
  Grid,
  Paper,
  Typography,
  Button,
  CircularProgress
} from '@mui/material';
import { Save as SaveIcon, GetApp as ExportIcon } from '@mui/icons-material';
import CostInputForm from '@components/Calculator/CostInputForm';
import PriceInputForm from '@components/Calculator/PriceInputForm';
import CalculationResults from '@components/Calculator/CalculationResults';
import ROISimulator from '@components/Calculator/ROISimulator';
import MarginChart from '@components/Calculator/MarginChart';
import { calculatorService } from '@services/calculatorService';
import { CalculationParams, CalculationResult } from '@/types/calculator';

const ProfitCalculator: React.FC = () => {
  const [calculationData, setCalculationData] = React.useState<CalculationParams>({
    costPrice: 0,
    shippingCost: 0,
    customsDuty: 0,
    marketplaceFee: 0,
    marketingCost: 0,
    additionalCosts: 0,
    sellingPrice: 0,
    expectedSales: 0
  });

  const [result, setResult] = React.useState<CalculationResult | null>(null);

  const calculateMutation = useMutation({
    mutationFn: calculatorService.calculateProfit,
    onSuccess: (data) => {
      setResult(data);
    }
  });

  const saveMutation = useMutation({
    mutationFn: calculatorService.saveCalculation
  });

  const handleCostChange = (costs: Partial<CalculationParams>) => {
    setCalculationData(prev => ({ ...prev, ...costs }));
  };

  const handlePriceChange = (prices: Partial<CalculationParams>) => {
    setCalculationData(prev => ({ ...prev, ...prices }));
  };

  const handleCalculate = () => {
    calculateMutation.mutate(calculationData);
  };

  const handleSave = () => {
    if (result) {
      saveMutation.mutate(result);
    }
  };

  const handleExport = async () => {
    if (result) {
      try {
        const blob = await calculatorService.exportCalculation(result.id, 'excel');
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `profit-calculation-${result.id}.xlsx`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Export failed:', error);
      }
    }
  };

  return (
    <Container maxWidth="lg">
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          수익성 계산기
        </Typography>

        <Grid container spacing={3}>
          {/* 입력 폼 */}
          <Grid item xs={12} md={6}>
            <Paper sx={{ p: 3, mb: 3 }}>
              <Typography variant="h6" gutterBottom>
                비용 입력
              </Typography>
              <CostInputForm
                costs={calculationData}
                onChange={handleCostChange}
              />
            </Paper>

            <Paper sx={{ p: 3 }}>
              <Typography variant="h6" gutterBottom>
                가격 및 판매량 입력
              </Typography>
              <PriceInputForm
                prices={calculationData}
                onChange={handlePriceChange}
              />
            </Paper>
          </Grid>

          {/* 결과 표시 */}
          <Grid item xs={12} md={6}>
            <Paper sx={{ p: 3, mb: 3 }}>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
                <Typography variant="h6">계산 결과</Typography>
                <Box>
                  <Button
                    startIcon={<SaveIcon />}
                    onClick={handleSave}
                    disabled={!result || saveMutation.isPending}
                    sx={{ mr: 1 }}
                  >
                    저장
                  </Button>
                  <Button
                    startIcon={<ExportIcon />}
                    onClick={handleExport}
                    disabled={!result}
                  >
                    내보내기
                  </Button>
                </Box>
              </Box>

              {calculateMutation.isPending ? (
                <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
                  <CircularProgress />
                </Box>
              ) : result ? (
                <>
                  <CalculationResults result={result} />
                  <MarginChart data={result.marginAnalysis} />
                  <ROISimulator
                    initialInvestment={calculationData.costPrice}
                    monthlyProfit={result.monthlyProfit}
                  />
                </>
              ) : (
                <Box sx={{ textAlign: 'center', py: 3 }}>
                  <Button
                    variant="contained"
                    size="large"
                    onClick={handleCalculate}
                  >
                    계산하기
                  </Button>
                </Box>
              )}
            </Paper>
          </Grid>
        </Grid>
      </Box>
    </Container>
  );
};

export default ProfitCalculator; 