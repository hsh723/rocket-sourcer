import React from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  Box,
  Typography,
  CircularProgress,
  Paper,
  Grid,
  LinearProgress,
  Tooltip,
  IconButton,
  Divider
} from '@mui/material';
import {
  Info as InfoIcon,
  CheckCircle as CheckIcon,
  Warning as WarningIcon,
  Error as ErrorIcon
} from '@mui/icons-material';
import { sourcingService } from '@/services/sourcingService';

interface ProductMatchProps {
  productId: string;
  suppliers: string[];
}

const ProductMatch: React.FC<ProductMatchProps> = ({ productId, suppliers }) => {
  const { data, isLoading, error } = useQuery({
    queryKey: ['productMatch', productId, suppliers],
    queryFn: () => sourcingService.analyzeProductMatch(Number(productId), suppliers[0]),
    enabled: Boolean(productId && suppliers.length > 0)
  });

  const getMatchScore = (score: number) => {
    if (score >= 90) return { color: 'success', icon: <CheckIcon color="success" /> };
    if (score >= 70) return { color: 'warning', icon: <WarningIcon color="warning" /> };
    return { color: 'error', icon: <ErrorIcon color="error" /> };
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
        제품 매칭 분석을 불러오는데 실패했습니다.
      </Typography>
    );
  }

  if (!data) return null;

  return (
    <Box>
      <Typography variant="h6" gutterBottom>
        제품 매칭 분석
      </Typography>

      <Grid container spacing={2}>
        {/* 전체 매칭 점수 */}
        <Grid item xs={12}>
          <Paper sx={{ p: 2, mb: 2 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
              <Typography variant="subtitle1" sx={{ flexGrow: 1 }}>
                전체 매칭 점수
              </Typography>
              {getMatchScore(data.overallScore).icon}
            </Box>
            <LinearProgress
              variant="determinate"
              value={data.overallScore}
              color={getMatchScore(data.overallScore).color as any}
              sx={{ height: 10, borderRadius: 5 }}
            />
            <Typography variant="h4" align="center" sx={{ mt: 1 }}>
              {data.overallScore}%
            </Typography>
          </Paper>
        </Grid>

        {/* 세부 매칭 항목 */}
        <Grid item xs={12}>
          <Paper sx={{ p: 2 }}>
            <Typography variant="subtitle1" gutterBottom>
              세부 매칭 항목
            </Typography>
            
            {data.matchingDetails.map((detail, index) => (
              <React.Fragment key={detail.category}>
                <Box sx={{ py: 1 }}>
                  <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
                    <Typography variant="body2" sx={{ flexGrow: 1 }}>
                      {detail.category}
                    </Typography>
                    <Tooltip title={detail.description}>
                      <IconButton size="small">
                        <InfoIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
                  </Box>
                  <LinearProgress
                    variant="determinate"
                    value={detail.score}
                    color={getMatchScore(detail.score).color as any}
                  />
                  <Typography variant="caption" sx={{ display: 'block', mt: 0.5 }}>
                    {detail.score}% - {detail.comment}
                  </Typography>
                </Box>
                {index < data.matchingDetails.length - 1 && <Divider />}
              </React.Fragment>
            ))}
          </Paper>
        </Grid>
      </Grid>
    </Box>
  );
};

export default ProductMatch; 