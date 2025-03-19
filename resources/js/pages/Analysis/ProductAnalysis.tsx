import React from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  Box,
  Container,
  Grid,
  Paper,
  Typography,
  CircularProgress,
  Tab,
  Tabs
} from '@mui/material';
import ComparisonChart from '@/components/Charts/ComparisonChart';
import ProfitBreakdown from '@/components/Charts/ProfitBreakdown';
import TrendAnalysis from '@/components/Charts/TrendAnalysis';
import { productService } from '@/services/productService';
import { useParams } from 'react-router-dom';

interface TabPanelProps {
  children?: React.ReactNode;
  index: number;
  value: number;
}

const TabPanel: React.FC<TabPanelProps> = ({ children, value, index }) => {
  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`analysis-tabpanel-${index}`}
    >
      {value === index && <Box sx={{ py: 3 }}>{children}</Box>}
    </div>
  );
};

const ProductAnalysis: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [tabValue, setTabValue] = React.useState(0);

  const { data, isLoading, error } = useQuery({
    queryKey: ['productAnalysis', id],
    queryFn: () => productService.analyzeProduct(Number(id)),
    enabled: Boolean(id)
  });

  const handleTabChange = (event: React.SyntheticEvent, newValue: number) => {
    setTabValue(newValue);
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
      <Typography color="error" sx={{ p: 3 }}>
        데이터를 불러오는데 실패했습니다.
      </Typography>
    );
  }

  if (!data) return null;

  return (
    <Container maxWidth="xl">
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          제품 분석
        </Typography>
        <Typography variant="subtitle1" color="text.secondary" gutterBottom>
          {data.productName}
        </Typography>

        <Paper sx={{ mt: 3 }}>
          <Tabs
            value={tabValue}
            onChange={handleTabChange}
            indicatorColor="primary"
            textColor="primary"
          >
            <Tab label="수익성 분석" />
            <Tab label="경쟁사 분석" />
            <Tab label="트렌드 분석" />
          </Tabs>

          {/* 수익성 분석 */}
          <TabPanel value={tabValue} index={0}>
            <Grid container spacing={3}>
              <Grid item xs={12} md={6}>
                <ProfitBreakdown
                  data={{
                    costs: data.profitAnalysis.costs,
                    revenues: data.profitAnalysis.revenues
                  }}
                />
              </Grid>
              <Grid item xs={12} md={6}>
                <ComparisonChart
                  data={data.profitAnalysis.comparison}
                  title="월별 수익 비교"
                />
              </Grid>
            </Grid>
          </TabPanel>

          {/* 경쟁사 분석 */}
          <TabPanel value={tabValue} index={1}>
            <Grid container spacing={3}>
              <Grid item xs={12}>
                <ComparisonChart
                  data={data.competitorAnalysis.priceComparison}
                  title="경쟁사 가격 비교"
                />
              </Grid>
              <Grid item xs={12}>
                <ComparisonChart
                  data={data.competitorAnalysis.marketShare}
                  title="시장 점유율"
                />
              </Grid>
            </Grid>
          </TabPanel>

          {/* 트렌드 분석 */}
          <TabPanel value={tabValue} index={2}>
            <Grid container spacing={3}>
              <Grid item xs={12}>
                <TrendAnalysis
                  data={data.trendAnalysis.salesTrend}
                  title="판매 트렌드"
                />
              </Grid>
              <Grid item xs={12}>
                <TrendAnalysis
                  data={data.trendAnalysis.priceTrend}
                  title="가격 트렌드"
                />
              </Grid>
            </Grid>
          </TabPanel>
        </Paper>
      </Box>
    </Container>
  );
};

export default ProductAnalysis; 