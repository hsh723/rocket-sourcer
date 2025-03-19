import React, { useState, useEffect } from 'react';
import { 
  Box, 
  Typography, 
  Grid, 
  Card, 
  CardContent, 
  CardHeader, 
  Divider, 
  CircularProgress, 
  Alert,
  Button,
  IconButton,
  Tooltip,
  Paper,
  useTheme
} from '@mui/material';
import { 
  TrendingUp as TrendingUpIcon, 
  TrendingDown as TrendingDownIcon, 
  Refresh as RefreshIcon,
  Info as InfoIcon,
  ArrowUpward as ArrowUpwardIcon,
  ArrowDownward as ArrowDownwardIcon
} from '@mui/icons-material';
import axios from 'axios';
import { ResponsiveContainer, LineChart, Line, AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip } from 'recharts';

const PerformanceOverview = ({ dateRange, refreshInterval = 300000 }) => {
  const theme = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [performanceData, setPerformanceData] = useState(null);
  const [lastUpdated, setLastUpdated] = useState(null);

  useEffect(() => {
    fetchPerformanceData();
    
    // 자동 새로고침 설정
    const intervalId = setInterval(() => {
      fetchPerformanceData();
    }, refreshInterval);
    
    return () => clearInterval(intervalId);
  }, [dateRange]);

  const fetchPerformanceData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // 날짜 범위 파라미터 구성
      const params = {};
      if (dateRange) {
        params.start_date = dateRange.startDate;
        params.end_date = dateRange.endDate;
      }
      
      const response = await axios.get('/api/dashboard/performance-overview', { params });
      
      if (response.data.success) {
        setPerformanceData(response.data.data);
        setLastUpdated(new Date());
      } else {
        setError(response.data.message || '성과 데이터를 불러오는데 실패했습니다.');
      }
    } catch (err) {
      setError('성과 데이터를 불러오는 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const formatNumber = (num) => {
    return new Intl.NumberFormat('ko-KR').format(num);
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ko-KR', { style: 'currency', currency: 'KRW' }).format(amount);
  };

  const formatPercent = (value) => {
    return new Intl.NumberFormat('ko-KR', { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(value / 100);
  };

  const getChangeColor = (change) => {
    if (change > 0) return theme.palette.success.main;
    if (change < 0) return theme.palette.error.main;
    return theme.palette.text.secondary;
  };

  const getChangeIcon = (change) => {
    if (change > 0) return <TrendingUpIcon sx={{ color: theme.palette.success.main }} />;
    if (change < 0) return <TrendingDownIcon sx={{ color: theme.palette.error.main }} />;
    return null;
  };

  const renderKpiCard = (title, value, change, formatter, chartData, dataKey, color) => {
    return (
      <Card sx={{ height: '100%' }}>
        <CardContent>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 2 }}>
            <Typography variant="h6" color="text.secondary">
              {title}
            </Typography>
            <Box sx={{ display: 'flex', alignItems: 'center' }}>
              {getChangeIcon(change)}
              <Typography 
                variant="body2" 
                sx={{ 
                  ml: 0.5, 
                  color: getChangeColor(change),
                  fontWeight: 'bold'
                }}
              >
                {change > 0 ? '+' : ''}{change}%
              </Typography>
            </Box>
          </Box>
          
          <Typography variant="h4" component="div" sx={{ mb: 2 }}>
            {formatter(value)}
          </Typography>
          
          <Box sx={{ height: 60 }}>
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData}>
                <defs>
                  <linearGradient id={`color-${dataKey}`} x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor={color} stopOpacity={0.8}/>
                    <stop offset="95%" stopColor={color} stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <Area 
                  type="monotone" 
                  dataKey={dataKey} 
                  stroke={color} 
                  fillOpacity={1} 
                  fill={`url(#color-${dataKey})`} 
                />
              </AreaChart>
            </ResponsiveContainer>
          </Box>
        </CardContent>
      </Card>
    );
  };

  const renderSummaryChart = () => {
    if (!performanceData || !performanceData.trend_data) return null;
    
    return (
      <Card sx={{ mt: 2 }}>
        <CardHeader 
          title="성과 추이" 
          action={
            <Tooltip title="최근 30일간의 주요 지표 추이">
              <IconButton>
                <InfoIcon />
              </IconButton>
            </Tooltip>
          }
        />
        <Divider />
        <CardContent>
          <Box sx={{ height: 300 }}>
            <ResponsiveContainer width="100%" height="100%">
              <LineChart
                data={performanceData.trend_data}
                margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
              >
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis yAxisId="left" />
                <YAxis yAxisId="right" orientation="right" />
                <RechartsTooltip />
                <Line 
                  yAxisId="left"
                  type="monotone" 
                  dataKey="revenue" 
                  name="매출" 
                  stroke={theme.palette.primary.main} 
                  activeDot={{ r: 8 }} 
                />
                <Line 
                  yAxisId="left"
                  type="monotone" 
                  dataKey="profit" 
                  name="수익" 
                  stroke={theme.palette.success.main} 
                />
                <Line 
                  yAxisId="right"
                  type="monotone" 
                  dataKey="orders" 
                  name="주문수" 
                  stroke={theme.palette.secondary.main} 
                />
              </LineChart>
            </ResponsiveContainer>
          </Box>
        </CardContent>
      </Card>
    );
  };

  const renderCategoryDistribution = () => {
    if (!performanceData || !performanceData.category_distribution) return null;
    
    return (
      <Card sx={{ height: '100%' }}>
        <CardHeader title="카테고리별 매출 분포" />
        <Divider />
        <CardContent>
          <Box sx={{ height: 250 }}>
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={performanceData.category_distribution}
                margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
              >
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <RechartsTooltip />
                <Bar 
                  dataKey="value" 
                  name="매출" 
                  fill={theme.palette.primary.main} 
                  radius={[4, 4, 0, 0]}
                />
              </BarChart>
            </ResponsiveContainer>
          </Box>
        </CardContent>
      </Card>
    );
  };

  const renderMarketplacePerformance = () => {
    if (!performanceData || !performanceData.marketplace_performance) return null;
    
    return (
      <Card sx={{ height: '100%' }}>
        <CardHeader title="마켓플레이스별 성과" />
        <Divider />
        <CardContent>
          <Box sx={{ height: 250 }}>
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={performanceData.marketplace_performance}
                margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
                layout="vertical"
              >
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis type="number" />
                <YAxis dataKey="name" type="category" />
                <RechartsTooltip />
                <Bar 
                  dataKey="revenue" 
                  name="매출" 
                  fill={theme.palette.primary.main} 
                  radius={[0, 4, 4, 0]}
                />
                <Bar 
                  dataKey="profit" 
                  name="수익" 
                  fill={theme.palette.success.main} 
                  radius={[0, 4, 4, 0]}
                />
              </BarChart>
            </ResponsiveContainer>
          </Box>
        </CardContent>
      </Card>
    );
  };

  if (loading && !performanceData) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 400 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error && !performanceData) {
    return (
      <Box sx={{ mt: 2, mb: 2 }}>
        <Alert 
          severity="error" 
          action={
            <Button color="inherit" size="small" onClick={fetchPerformanceData}>
              다시 시도
            </Button>
          }
        >
          {error}
        </Alert>
      </Box>
    );
  }

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h5" component="h1">
          성과 개요
        </Typography>
        <Box sx={{ display: 'flex', alignItems: 'center' }}>
          {lastUpdated && (
            <Typography variant="body2" color="text.secondary" sx={{ mr: 2 }}>
              마지막 업데이트: {lastUpdated.toLocaleTimeString()}
            </Typography>
          )}
          <Button 
            startIcon={<RefreshIcon />} 
            variant="outlined" 
            size="small" 
            onClick={fetchPerformanceData}
            disabled={loading}
          >
            {loading ? <CircularProgress size={20} /> : '새로고침'}
          </Button>
        </Box>
      </Box>

      {performanceData && (
        <>
          <Grid container spacing={2}>
            <Grid item xs={12} sm={6} md={3}>
              {renderKpiCard(
                "총 매출", 
                performanceData.total_revenue, 
                performanceData.revenue_change, 
                formatCurrency,
                performanceData.revenue_trend,
                "revenue",
                theme.palette.primary.main
              )}
            </Grid>
            <Grid item xs={12} sm={6} md={3}>
              {renderKpiCard(
                "총 수익", 
                performanceData.total_profit, 
                performanceData.profit_change, 
                formatCurrency,
                performanceData.profit_trend,
                "profit",
                theme.palette.success.main
              )}
            </Grid>
            <Grid item xs={12} sm={6} md={3}>
              {renderKpiCard(
                "주문 수", 
                performanceData.total_orders, 
                performanceData.orders_change, 
                formatNumber,
                performanceData.orders_trend,
                "orders",
                theme.palette.secondary.main
              )}
            </Grid>
            <Grid item xs={12} sm={6} md={3}>
              {renderKpiCard(
                "평균 마진율", 
                performanceData.average_margin, 
                performanceData.margin_change, 
                formatPercent,
                performanceData.margin_trend,
                "margin",
                theme.palette.info.main
              )}
            </Grid>
          </Grid>

          {renderSummaryChart()}

          <Grid container spacing={2} sx={{ mt: 1 }}>
            <Grid item xs={12} md={6}>
              {renderCategoryDistribution()}
            </Grid>
            <Grid item xs={12} md={6}>
              {renderMarketplacePerformance()}
            </Grid>
          </Grid>
        </>
      )}
    </Box>
  );
};

export default PerformanceOverview; 