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
import { useParams } from 'react-router-dom';
import ProductImageGallery from '@/components/Products/ProductImageGallery';
import { productService } from '@/services/productService';

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
      id={`product-tabpanel-${index}`}
    >
      {value === index && <Box sx={{ py: 3 }}>{children}</Box>}
    </div>
  );
};

const ProductDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [tabValue, setTabValue] = React.useState(0);

  const { data, isLoading, error } = useQuery({
    queryKey: ['product', id],
    queryFn: () => productService.getProduct(Number(id)),
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
        제품 정보를 불러오는데 실패했습니다.
      </Typography>
    );
  }

  if (!data) return null;

  return (
    <Container maxWidth="xl">
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          {data.name}
        </Typography>

        <Paper sx={{ mt: 3 }}>
          <Tabs
            value={tabValue}
            onChange={handleTabChange}
            indicatorColor="primary"
            textColor="primary"
          >
            <Tab label="제품 정보" />
            <Tab label="이미지 갤러리" />
            <Tab label="상세 스펙" />
          </Tabs>

          <TabPanel value={tabValue} index={0}>
            <Grid container spacing={3}>
              <Grid item xs={12} md={6}>
                {/* 제품 기본 정보 */}
                <Typography variant="h6" gutterBottom>
                  기본 정보
                </Typography>
                {/* 제품 정보 표시 */}
              </Grid>
              <Grid item xs={12} md={6}>
                {/* 가격 및 재고 정보 */}
                <Typography variant="h6" gutterBottom>
                  가격 및 재고
                </Typography>
                {/* 가격 정보 표시 */}
              </Grid>
            </Grid>
          </TabPanel>

          <TabPanel value={tabValue} index={1}>
            <ProductImageGallery productId={id} />
          </TabPanel>

          <TabPanel value={tabValue} index={2}>
            {/* 상세 스펙 정보 */}
            <Typography variant="h6" gutterBottom>
              상세 스펙
            </Typography>
            {/* 스펙 정보 표시 */}
          </TabPanel>
        </Paper>
      </Box>
    </Container>
  );
};

export default ProductDetail; 