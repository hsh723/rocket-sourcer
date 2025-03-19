import { useParams } from 'react-router-dom';
import { Grid, Box, Typography } from '@mui/material';
import { Card } from '@/components/UI/Card';
import { Loader } from '@/components/UI/Loader';
import { LineChart } from '@/components/Charts/LineChart';
import { PieChart } from '@/components/Charts/PieChart';
import { useKeywordDetail } from '@/hooks/useKeywordDetail';
import { formatNumber } from '@/utils/format';

export default function KeywordDetail() {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, error } = useKeywordDetail(Number(id));

  if (isLoading) return <Loader />;
  if (error) throw error;

  const {
    keyword,
    searchVolume,
    competition,
    trend,
    averagePrice,
    estimatedSales,
    history,
    competitors,
    products,
  } = data;

  const trendData = {
    labels: history.map((h) => h.date),
    datasets: [
      {
        label: '검색량',
        data: history.map((h) => h.searchVolume),
        borderColor: 'rgb(75, 192, 192)',
        tension: 0.1,
      },
      {
        label: '평균가격',
        data: history.map((h) => h.averagePrice),
        borderColor: 'rgb(255, 99, 132)',
        tension: 0.1,
      },
    ],
  };

  const competitorData = {
    labels: competitors.map((c) => c.name),
    datasets: [
      {
        data: competitors.map((c) => c.marketShare),
        backgroundColor: [
          'rgb(255, 99, 132)',
          'rgb(54, 162, 235)',
          'rgb(255, 206, 86)',
          'rgb(75, 192, 192)',
          'rgb(153, 102, 255)',
        ],
      },
    ],
  };

  return (
    <Box>
      <Grid container spacing={3}>
        <Grid item xs={12}>
          <Box sx={{ mb: 3 }}>
            <Typography variant="h4">{keyword}</Typography>
            <Typography variant="subtitle1" color="text.secondary">
              최근 업데이트: {new Date().toLocaleDateString()}
            </Typography>
          </Box>
        </Grid>

        <Grid item xs={12} md={3}>
          <Card title="검색량">
            <Typography variant="h4">{formatNumber(searchVolume)}</Typography>
          </Card>
        </Grid>
        <Grid item xs={12} md={3}>
          <Card title="경쟁도">
            <Typography variant="h4">{competition.toFixed(1)}%</Typography>
          </Card>
        </Grid>
        <Grid item xs={12} md={3}>
          <Card title="평균가격">
            <Typography variant="h4">
              {formatNumber(averagePrice, 'currency')}
            </Typography>
          </Card>
        </Grid>
        <Grid item xs={12} md={3}>
          <Card title="예상매출">
            <Typography variant="h4">
              {formatNumber(estimatedSales, 'currency')}
            </Typography>
          </Card>
        </Grid>

        <Grid item xs={12} md={8}>
          <Card title="트렌드">
            <Box sx={{ height: 300 }}>
              <LineChart data={trendData} />
            </Box>
          </Card>
        </Grid>
        <Grid item xs={12} md={4}>
          <Card title="시장 점유율">
            <Box sx={{ height: 300 }}>
              <PieChart data={competitorData} />
            </Box>
          </Card>
        </Grid>

        <Grid item xs={12}>
          <Card title="관련 제품">
            {/* ProductTable component will be implemented later */}
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
} 