import { Grid } from '@mui/material';
import { SummaryCards } from '@/components/Dashboard/SummaryCards';
import { TrendChart } from '@/components/Dashboard/TrendChart';
import { RecentKeywords } from '@/components/Dashboard/RecentKeywords';
import { RecentProducts } from '@/components/Dashboard/RecentProducts';
import { NotificationCenter } from '@/components/Dashboard/NotificationCenter';
import { useDashboard } from '@/hooks/useDashboard';
import { Loader } from '@/components/UI/Loader';

export default function Dashboard() {
  const { data, isLoading, error } = useDashboard();

  if (isLoading) return <Loader />;
  if (error) throw error;

  return (
    <Grid container spacing={3}>
      <Grid item xs={12}>
        <SummaryCards data={data.summary} />
      </Grid>
      <Grid item xs={12} md={8}>
        <TrendChart data={data.trends} />
      </Grid>
      <Grid item xs={12} md={4}>
        <NotificationCenter notifications={data.notifications} />
      </Grid>
      <Grid item xs={12} md={6}>
        <RecentKeywords keywords={data.recentKeywords} />
      </Grid>
      <Grid item xs={12} md={6}>
        <RecentProducts products={data.recentProducts} />
      </Grid>
    </Grid>
  );
} 