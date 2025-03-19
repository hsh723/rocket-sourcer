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
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TableSortLabel,
  Paper,
  Chip,
  LinearProgress,
  useTheme
} from '@mui/material';
import { 
  TrendingUp as TrendingUpIcon, 
  TrendingDown as TrendingDownIcon, 
  Refresh as RefreshIcon,
  Info as InfoIcon,
  Category as CategoryIcon,
  MoreVert as MoreVertIcon
} from '@mui/icons-material';
import axios from 'axios';
import { 
  ResponsiveContainer, 
  PieChart, 
  Pie, 
  Cell, 
  Tooltip as RechartsTooltip, 
  Legend,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Radar,
  RadarChart,
  PolarGrid,
  PolarAngleAxis,
  PolarRadiusAxis
} from 'recharts';

const CategoryPerformance = ({ dateRange, selectedCategories, refreshInterval = 300000 }) => {
  const theme = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [categoryData, setCategoryData] = useState(null);
  const [lastUpdated, setLastUpdated] = useState(null);
  const [sortConfig, setSortConfig] = useState({ key: 'revenue', direction: 'desc' });

  useEffect(() => {
    fetchCategoryData();
    
    // 자동 새로고침 설정
    const intervalId = setInterval(() => {
      fetchCategoryData();
    }, refreshInterval);
    
    return () => clearInterval(intervalId);
  }, [dateRange, selectedCategories]);

  const fetchCategoryData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // 날짜 범위 및 카테고리 파라미터 구성
      const params = {};
      if (dateRange) {
        params.start_date = dateRange.startDate;
        params.end_date = dateRange.endDate;
      }
      
      if (selectedCategories && selectedCategories.length > 0) {
        params.categories = selectedCategories.join(',');
      }
      
      const response = await axios.get('/api/dashboard/category-performance', { params });
      
      if (response.data.success) {
        setCategoryData(response.data.data);
        setLastUpdated(new Date());
      } else {
        setError(response.data.message || '카테고리 성과 데이터를 불러오는데 실패했습니다.');
      }
    } catch (err) {
      setError('카테고리 성과 데이터를 불러오는 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message));
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

  const getPerformanceColor = (value, threshold1 = 33, threshold2 = 66) => {
    if (value >= threshold2) return theme.palette.success.main;
    if (value >= threshold1) return theme.palette.warning.main;
    return theme.palette.error.main;
  };

  const handleSort = (key) => {
    let direction = 'asc';
    if (sortConfig.key === key && sortConfig.direction === 'asc') {
      direction = 'desc';
    }
    setSortConfig({ key, direction });
  };

  const sortedCategories = () => {
    if (!categoryData || !categoryData.categories) return [];
    
    const sortableCategories = [...categoryData.categories];
    
    return sortableCategories.sort((a, b) => {
      if (a[sortConfig.key] < b[sortConfig.key]) {
        return sortConfig.direction === 'asc' ? -1 : 1;
      }
      if (a[sortConfig.key] > b[sortConfig.key]) {
        return sortConfig.direction === 'asc' ? 1 : -1;
      }
      return 0;
    });
  };

  const renderDistributionChart = () => {
    if (!categoryData || !categoryData.categories) return null;
    
    // 상위 5개 카테고리 + 기타
    const topCategories = [...categoryData.categories]
      .sort((a, b) => b.revenue - a.revenue)
      .slice(0, 5);
    
    const otherCategories = categoryData.categories
      .filter(cat => !topCategories.includes(cat));
    
    const otherRevenue = otherCategories.reduce((sum, cat) => sum + cat.revenue, 0);
    
    const chartData = [
      ...topCategories.map(cat => ({
        name: cat.name,
        value: cat.revenue
      }))
    ];
    
    if (otherRevenue > 0) {
      chartData.push({
        name: '기타',
        value: otherRevenue
      });
    }
    
    const COLORS = [
      theme.palette.primary.main,
      theme.palette.secondary.main,
      theme.palette.success.main,
      theme.palette.warning.main,
      theme.palette.info.main,
      theme.palette.grey[500]
    ];
    
    return (
      <ResponsiveContainer width="100%" height={300}>
        <PieChart>
          <Pie
            data={chartData}
            cx="50%"
            cy="50%"
            labelLine={false}
            outerRadius={100}
            fill="#8884d8"
            dataKey="value"
            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(1)}%`}
          >
            {chartData.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
            ))}
          </Pie>
          <RechartsTooltip formatter={(value) => formatCurrency(value)} />
          <Legend />
        </PieChart>
      </ResponsiveContainer>
    );
  };

  const renderGrowthChart = () => {
    if (!categoryData || !categoryData.growth_data) return null;
    
    return (
      <ResponsiveContainer width="100%" height={300}>
        <BarChart
          data={categoryData.growth_data}
          margin={{ top: 20, right: 30, left: 20, bottom: 5 }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="name" />
          <YAxis tickFormatter={(value) => `${value}%`} />
          <RechartsTooltip formatter={(value) => `${value}%`} />
          <Bar 
            dataKey="growth" 
            name="성장률" 
            fill={theme.palette.primary.main}
            radius={[4, 4, 0, 0]}
          >
            {categoryData.growth_data.map((entry, index) => (
              <Cell 
                key={`cell-${index}`} 
                fill={entry.growth >= 0 ? theme.palette.success.main : theme.palette.error.main} 
              />
            ))}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    );
  };

  const renderPerformanceRadar = () => {
    if (!categoryData || !categoryData.radar_data) return null;
    
    return (
      <ResponsiveContainer width="100%" height={300}>
        <RadarChart outerRadius={90} data={categoryData.radar_data}>
          <PolarGrid />
          <PolarAngleAxis dataKey="category" />
          <PolarRadiusAxis angle={30} domain={[0, 100]} />
          <Radar 
            name="매출" 
            dataKey="revenue" 
            stroke={theme.palette.primary.main} 
            fill={theme.palette.primary.main} 
            fillOpacity={0.6} 
          />
          <Radar 
            name="수익" 
            dataKey="profit" 
            stroke={theme.palette.success.main} 
            fill={theme.palette.success.main} 
            fillOpacity={0.6} 
          />
          <Radar 
            name="주문수" 
            dataKey="orders" 
            stroke={theme.palette.secondary.main} 
            fill={theme.palette.secondary.main} 
            fillOpacity={0.6} 
          />
          <Legend />
          <RechartsTooltip />
        </RadarChart>
      </ResponsiveContainer>
    );
  };

  const renderCategoryTable = () => {
    if (!categoryData || !categoryData.categories) return null;
    
    return (
      <TableContainer component={Paper}>
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>카테고리</TableCell>
              <TableCell align="right">
                <TableSortLabel
                  active={sortConfig.key === 'revenue'}
                  direction={sortConfig.key === 'revenue' ? sortConfig.direction : 'asc'}
                  onClick={() => handleSort('revenue')}
                >
                  매출
                </TableSortLabel>
              </TableCell>
              <TableCell align="right">
                <TableSortLabel
                  active={sortConfig.key === 'profit'}
                  direction={sortConfig.key === 'profit' ? sortConfig.direction : 'asc'}
                  onClick={() => handleSort('profit')}
                >
                  수익
                </TableSortLabel>
              </TableCell>
              <TableCell align="right">
                <TableSortLabel
                  active={sortConfig.key === 'margin'}
                  direction={sortConfig.key === 'margin' ? sortConfig.direction : 'asc'}
                  onClick={() => handleSort('margin')}
                >
                  마진율
                </TableSortLabel>
              </TableCell>
              <TableCell align="right">
                <TableSortLabel
                  active={sortConfig.key === 'orders'}
                  direction={sortConfig.key === 'orders' ? sortConfig.direction : 'asc'}
                  onClick={() => handleSort('orders')}
                >
                  주문수
                </TableSortLabel>
              </TableCell>
              <TableCell align="right">
                <TableSortLabel
                  active={sortConfig.key === 'growth'}
                  direction={sortConfig.key === 'growth' ? sortConfig.direction : 'asc'}
                  onClick={() => handleSort('growth')}
                >
                  성장률
                </TableSortLabel>
              </TableCell>
              <TableCell align="right">성과</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {sortedCategories().map((category, index) => (
              <TableRow key={index}>
                <TableCell component="th" scope="row">
                  <Box sx={{ display: 'flex', alignItems: 'center' }}>
                    <CategoryIcon sx={{ mr: 1, color: theme.palette.primary.main }} />
                    {category.name}
                  </Box>
                </TableCell>
                <TableCell align="right">{formatCurrency(category.revenue)}</TableCell>
                <TableCell align="right">{formatCurrency(category.profit)}</TableCell>
                <TableCell align="right">{formatPercent(category.margin)}</TableCell>
                <TableCell align="right">{formatNumber(category.orders)}</TableCell>
                <TableCell align="right">
                  <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end' }}>
                    {getChangeIcon(category.growth)}
                    <Typography 
                      variant="body2" 
                      sx={{ 
                        ml: 0.5, 
                        color: getChangeColor(category.growth),
                        fontWeight: 'bold'
                      }}
                    >
                      {category.growth > 0 ? '+' : ''}{category.growth}%
                    </Typography>
                  </Box>
                </TableCell>
                <TableCell align="right">
                  <Box sx={{ display: 'flex', alignItems: 'center' }}>
                    <LinearProgress 
                      variant="determinate" 
                      value={category.performance_score} 
                      sx={{ 
                        flexGrow: 1, 
                        mr: 1,
                        height: 10,
                        borderRadius: 5,
                        backgroundColor: theme.palette.grey[200],
                        '& .MuiLinearProgress-bar': {
                          backgroundColor: getPerformanceColor(category.performance_score),
                          borderRadius: 5,
                        }
                      }} 
                    />
                    <Typography variant="body2" color="text.secondary">
                      {category.performance_score}
                    </Typography>
                  </Box>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    );
  };

  if (loading && !categoryData) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 400 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error && !categoryData) {
    return (
      <Box sx={{ mt: 2, mb: 2 }}>
        <Alert 
          severity="error" 
          action={
            <Button color="inherit" size="small" onClick={fetchCategoryData}>
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
          카테고리별 성과
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
            onClick={fetchCategoryData}
            disabled={loading}
          >
            {loading ? <CircularProgress size={20} /> : '새로고침'}
          </Button>
        </Box>
      </Box>

      {categoryData && (
        <>
          <Grid container spacing={2}>
            <Grid item xs={12} md={6}>
              <Card>
                <CardHeader 
                  title="매출 분포" 
                  action={
                    <Tooltip title="카테고리별 매출 분포">
                      <IconButton>
                        <InfoIcon />
                      </IconButton>
                    </Tooltip>
                  }
                />
                <Divider />
                <CardContent>
                  {renderDistributionChart()}
                </CardContent>
              </Card>
            </Grid>
            <Grid item xs={12} md={6}>
              <Card>
                <CardHeader 
                  title="카테고리 성장률" 
                  action={
                    <Tooltip title="전년 대비 성장률">
                      <IconButton>
                        <InfoIcon />
                      </IconButton>
                    </Tooltip>
                  }
                />
                <Divider />
                <CardContent>
                  {renderGrowthChart()}
                </CardContent>
              </Card>
            </Grid>
          </Grid>

          <Card sx={{ mt: 2 }}>
            <CardHeader 
              title="카테고리 성과 비교" 
              action={
                <Tooltip title="주요 지표별 카테고리 성과 비교">
                  <IconButton>
                    <InfoIcon />
                  </IconButton>
                </Tooltip>
              }
            />
            <Divider />
            <CardContent>
              {renderPerformanceRadar()}
            </CardContent>
          </Card>

          <Card sx={{ mt: 2 }}>
            <CardHeader 
              title="카테고리 상세 성과" 
              action={
                <Tooltip title="카테고리별 상세 성과 지표">
                  <IconButton>
                    <InfoIcon />
                  </IconButton>
                </Tooltip>
              }
            />
            <Divider />
            <CardContent>
              {renderCategoryTable()}
            </CardContent>
          </Card>
        </>
      )}
    </Box>
  );
};

export default CategoryPerformance; 