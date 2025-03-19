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
  TablePagination,
  Paper,
  Chip,
  Avatar,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  TextField,
  InputAdornment,
  Badge,
  LinearProgress,
  useTheme
} from '@mui/material';
import { 
  Refresh as RefreshIcon,
  Info as InfoIcon,
  Search as SearchIcon,
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  Star as StarIcon,
  StarBorder as StarBorderIcon,
  ShoppingCart as ShoppingCartIcon,
  AttachMoney as AttachMoneyIcon,
  Inventory as InventoryIcon,
  FilterList as FilterListIcon
} from '@mui/icons-material';
import axios from 'axios';
import { 
  ResponsiveContainer, 
  BarChart, 
  Bar, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip as RechartsTooltip, 
  Cell
} from 'recharts';

const TopProducts = ({ dateRange, limit = 10, refreshInterval = 300000 }) => {
  const theme = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [productsData, setProductsData] = useState(null);
  const [lastUpdated, setLastUpdated] = useState(null);
  const [sortConfig, setSortConfig] = useState({ key: 'revenue', direction: 'desc' });
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterMetric, setFilterMetric] = useState('all');
  const [filterThreshold, setFilterThreshold] = useState(0);

  useEffect(() => {
    fetchProductsData();
    
    // 자동 새로고침 설정
    const intervalId = setInterval(() => {
      fetchProductsData();
    }, refreshInterval);
    
    return () => clearInterval(intervalId);
  }, [dateRange, limit]);

  const fetchProductsData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // 날짜 범위 파라미터 구성
      const params = { limit };
      if (dateRange) {
        params.start_date = dateRange.startDate;
        params.end_date = dateRange.endDate;
      }
      
      const response = await axios.get('/api/dashboard/top-products', { params });
      
      if (response.data.success) {
        setProductsData(response.data.data);
        setLastUpdated(new Date());
      } else {
        setError(response.data.message || '상위 제품 데이터를 불러오는데 실패했습니다.');
      }
    } catch (err) {
      setError('상위 제품 데이터를 불러오는 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message));
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

  const handleChangePage = (event, newPage) => {
    setPage(newPage);
  };

  const handleChangeRowsPerPage = (event) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  };

  const handleSearchChange = (event) => {
    setSearchTerm(event.target.value);
    setPage(0);
  };

  const handleFilterMetricChange = (event) => {
    setFilterMetric(event.target.value);
    setPage(0);
  };

  const handleFilterThresholdChange = (event) => {
    setFilterThreshold(Number(event.target.value));
    setPage(0);
  };

  const filteredProducts = () => {
    if (!productsData || !productsData.products) return [];
    
    return productsData.products
      .filter(product => {
        // 검색어 필터링
        if (searchTerm && !product.name.toLowerCase().includes(searchTerm.toLowerCase())) {
          return false;
        }
        
        // 지표 기준 필터링
        if (filterMetric !== 'all') {
          return product[filterMetric] >= filterThreshold;
        }
        
        return true;
      })
      .sort((a, b) => {
        if (a[sortConfig.key] < b[sortConfig.key]) {
          return sortConfig.direction === 'asc' ? -1 : 1;
        }
        if (a[sortConfig.key] > b[sortConfig.key]) {
          return sortConfig.direction === 'asc' ? 1 : -1;
        }
        return 0;
      });
  };

  const paginatedProducts = () => {
    const filtered = filteredProducts();
    return filtered.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);
  };

  const renderTopProductsChart = () => {
    if (!productsData || !productsData.products) return null;
    
    // 상위 5개 제품만 차트에 표시
    const topProducts = [...productsData.products]
      .sort((a, b) => b[sortConfig.key] - a[sortConfig.key])
      .slice(0, 5);
    
    const chartData = topProducts.map(product => ({
      name: product.name.length > 20 ? product.name.substring(0, 20) + '...' : product.name,
      value: product[sortConfig.key]
    }));
    
    const getValueFormatter = () => {
      switch (sortConfig.key) {
        case 'revenue':
        case 'profit':
        case 'average_price':
          return formatCurrency;
        case 'margin':
        case 'conversion_rate':
          return formatPercent;
        case 'orders':
        case 'units_sold':
        case 'views':
        default:
          return formatNumber;
      }
    };
    
    const valueFormatter = getValueFormatter();
    
    return (
      <ResponsiveContainer width="100%" height={300}>
        <BarChart
          data={chartData}
          layout="vertical"
          margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis type="number" />
          <YAxis dataKey="name" type="category" width={150} />
          <RechartsTooltip formatter={(value) => valueFormatter(value)} />
          <Bar 
            dataKey="value" 
            name={getSortLabel(sortConfig.key)} 
            fill={theme.palette.primary.main}
            radius={[0, 4, 4, 0]}
          >
            {chartData.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={theme.palette.primary.main} />
            ))}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    );
  };

  const getSortLabel = (key) => {
    switch (key) {
      case 'revenue':
        return '매출';
      case 'profit':
        return '수익';
      case 'orders':
        return '주문수';
      case 'units_sold':
        return '판매 수량';
      case 'margin':
        return '마진율';
      case 'average_price':
        return '평균 가격';
      case 'views':
        return '조회수';
      case 'conversion_rate':
        return '전환율';
      case 'rating':
        return '평점';
      default:
        return key;
    }
  };

  const renderStarRating = (rating) => {
    const stars = [];
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    
    for (let i = 0; i < 5; i++) {
      if (i < fullStars) {
        stars.push(<StarIcon key={i} fontSize="small" sx={{ color: theme.palette.warning.main }} />);
      } else if (i === fullStars && hasHalfStar) {
        stars.push(<StarIcon key={i} fontSize="small" sx={{ color: theme.palette.warning.main, opacity: 0.5 }} />);
      } else {
        stars.push(<StarBorderIcon key={i} fontSize="small" sx={{ color: theme.palette.warning.main }} />);
      }
    }
    
    return (
      <Box sx={{ display: 'flex', alignItems: 'center' }}>
        {stars}
        <Typography variant="body2" sx={{ ml: 0.5 }}>
          ({rating.toFixed(1)})
        </Typography>
      </Box>
    );
  };

  const renderProductsTable = () => {
    const products = paginatedProducts();
    
    return (
      <Paper>
        <TableContainer>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell>제품</TableCell>
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
                    active={sortConfig.key === 'units_sold'}
                    direction={sortConfig.key === 'units_sold' ? sortConfig.direction : 'asc'}
                    onClick={() => handleSort('units_sold')}
                  >
                    판매 수량
                  </TableSortLabel>
                </TableCell>
                <TableCell align="right">
                  <TableSortLabel
                    active={sortConfig.key === 'conversion_rate'}
                    direction={sortConfig.key === 'conversion_rate' ? sortConfig.direction : 'asc'}
                    onClick={() => handleSort('conversion_rate')}
                  >
                    전환율
                  </TableSortLabel>
                </TableCell>
                <TableCell align="right">
                  <TableSortLabel
                    active={sortConfig.key === 'rating'}
                    direction={sortConfig.key === 'rating' ? sortConfig.direction : 'asc'}
                    onClick={() => handleSort('rating')}
                  >
                    평점
                  </TableSortLabel>
                </TableCell>
                <TableCell align="right">성과</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {products.map((product, index) => (
                <TableRow key={index}>
                  <TableCell>
                    <Box sx={{ display: 'flex', alignItems: 'center' }}>
                      {product.image_url ? (
                        <Avatar 
                          src={product.image_url} 
                          alt={product.name}
                          variant="rounded"
                          sx={{ width: 40, height: 40, mr: 2 }}
                        />
                      ) : (
                        <Avatar 
                          variant="rounded"
                          sx={{ width: 40, height: 40, mr: 2, bgcolor: theme.palette.primary.main }}
                        >
                          <InventoryIcon />
                        </Avatar>
                      )}
                      <Box>
                        <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                          {product.name}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          {product.category}
                        </Typography>
                      </Box>
                    </Box>
                  </TableCell>
                  <TableCell align="right">
                    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end' }}>
                      <Typography variant="body2">{formatCurrency(product.revenue)}</Typography>
                      <Box sx={{ display: 'flex', alignItems: 'center' }}>
                        {getChangeIcon(product.revenue_change)}
                        <Typography 
                          variant="caption" 
                          sx={{ 
                            color: getChangeColor(product.revenue_change)
                          }}
                        >
                          {product.revenue_change > 0 ? '+' : ''}{product.revenue_change}%
                        </Typography>
                      </Box>
                    </Box>
                  </TableCell>
                  <TableCell align="right">
                    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end' }}>
                      <Typography variant="body2">{formatCurrency(product.profit)}</Typography>
                      <Box sx={{ display: 'flex', alignItems: 'center' }}>
                        {getChangeIcon(product.profit_change)}
                        <Typography 
                          variant="caption" 
                          sx={{ 
                            color: getChangeColor(product.profit_change)
                          }}
                        >
                          {product.profit_change > 0 ? '+' : ''}{product.profit_change}%
                        </Typography>
                      </Box>
                    </Box>
                  </TableCell>
                  <TableCell align="right">{formatPercent(product.margin)}</TableCell>
                  <TableCell align="right">{formatNumber(product.orders)}</TableCell>
                  <TableCell align="right">{formatNumber(product.units_sold)}</TableCell>
                  <TableCell align="right">{formatPercent(product.conversion_rate)}</TableCell>
                  <TableCell align="right">{renderStarRating(product.rating)}</TableCell>
                  <TableCell align="right">
                    <Box sx={{ display: 'flex', alignItems: 'center' }}>
                      <LinearProgress 
                        variant="determinate" 
                        value={product.performance_score} 
                        sx={{ 
                          flexGrow: 1, 
                          mr: 1,
                          height: 10,
                          borderRadius: 5,
                          backgroundColor: theme.palette.grey[200],
                          '& .MuiLinearProgress-bar': {
                            backgroundColor: getPerformanceColor(product.performance_score),
                            borderRadius: 5,
                          }
                        }} 
                      />
                      <Typography variant="body2" color="text.secondary">
                        {product.performance_score}
                      </Typography>
                    </Box>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
        <TablePagination
          rowsPerPageOptions={[5, 10, 25]}
          component="div"
          count={filteredProducts().length}
          rowsPerPage={rowsPerPage}
          page={page}
          onPageChange={handleChangePage}
          onRowsPerPageChange={handleChangeRowsPerPage}
          labelRowsPerPage="행 수:"
          labelDisplayedRows={({ from, to, count }) => `${from}-${to} / ${count}`}
        />
      </Paper>
    );
  };

  const renderCategoryDistribution = () => {
    if (!productsData || !productsData.category_distribution) return null;
    
    return (
      <Card sx={{ height: '100%' }}>
        <CardHeader 
          title="카테고리별 상위 제품 분포" 
          action={
            <Tooltip title="카테고리별 상위 제품 분포">
              <IconButton>
                <InfoIcon />
              </IconButton>
            </Tooltip>
          }
        />
        <Divider />
        <CardContent>
          <Box sx={{ height: 250 }}>
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={productsData.category_distribution}
                margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
              >
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <RechartsTooltip />
                <Bar 
                  dataKey="value" 
                  name="제품 수" 
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

  const renderPerformanceDistribution = () => {
    if (!productsData || !productsData.performance_distribution) return null;
    
    return (
      <Card sx={{ height: '100%' }}>
        <CardHeader 
          title="성과 분포" 
          action={
            <Tooltip title="성과 점수별 제품 분포">
              <IconButton>
                <InfoIcon />
              </IconButton>
            </Tooltip>
          }
        />
        <Divider />
        <CardContent>
          <Box sx={{ height: 250 }}>
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={productsData.performance_distribution}
                margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
              >
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="range" />
                <YAxis />
                <RechartsTooltip />
                <Bar 
                  dataKey="count" 
                  name="제품 수" 
                  radius={[4, 4, 0, 0]}
                >
                  {productsData.performance_distribution.map((entry, index) => (
                    <Cell 
                      key={`cell-${index}`} 
                      fill={getPerformanceColor(
                        parseInt(entry.range.split('-')[0]) + 
                        (parseInt(entry.range.split('-')[1]) - parseInt(entry.range.split('-')[0])) / 2
                      )} 
                    />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </Box>
        </CardContent>
      </Card>
    );
  };

  if (loading && !productsData) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 400 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error && !productsData) {
    return (
      <Box sx={{ mt: 2, mb: 2 }}>
        <Alert 
          severity="error" 
          action={
            <Button color="inherit" size="small" onClick={fetchProductsData}>
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
          상위 제품
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
            onClick={fetchProductsData}
            disabled={loading}
          >
            {loading ? <CircularProgress size={20} /> : '새로고침'}
          </Button>
        </Box>
      </Box>

      {productsData && (
        <>
          <Grid container spacing={2} sx={{ mb: 2 }}>
            <Grid item xs={12} md={8}>
              <Card>
                <CardHeader 
                  title={`상위 제품 (${getSortLabel(sortConfig.key)} 기준)`}
                  action={
                    <Tooltip title={`${getSortLabel(sortConfig.key)} 기준 상위 5개 제품`}>
                      <IconButton>
                        <InfoIcon />
                      </IconButton>
                    </Tooltip>
                  }
                />
                <Divider />
                <CardContent>
                  {renderTopProductsChart()}
                </CardContent>
              </Card>
            </Grid>
            <Grid item xs={12} md={4}>
              <Grid container spacing={2} direction="column">
                <Grid item>
                  <Card>
                    <CardContent>
                      <Typography variant="h6" color="text.secondary" gutterBottom>
                        총 제품 수
                      </Typography>
                      <Typography variant="h4">
                        {formatNumber(productsData.total_products)}
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
                <Grid item>
                  <Card>
                    <CardContent>
                      <Typography variant="h6" color="text.secondary" gutterBottom>
                        평균 성과 점수
                      </Typography>
                      <Box sx={{ display: 'flex', alignItems: 'center' }}>
                        <Typography variant="h4" sx={{ mr: 1 }}>
                          {productsData.average_performance_score}
                        </Typography>
                        <LinearProgress 
                          variant="determinate" 
                          value={productsData.average_performance_score} 
                          sx={{ 
                            flexGrow: 1,
                            height: 10,
                            borderRadius: 5,
                            backgroundColor: theme.palette.grey[200],
                            '& .MuiLinearProgress-bar': {
                              backgroundColor: getPerformanceColor(productsData.average_performance_score),
                              borderRadius: 5,
                            }
                          }} 
                        />
                      </Box>
                    </CardContent>
                  </Card>
                </Grid>
                <Grid item>
                  <Card>
                    <CardContent>
                      <Typography variant="h6" color="text.secondary" gutterBottom>
                        상위 카테고리
                      </Typography>
                      <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                        {productsData.top_categories.map((category, index) => (
                          <Chip 
                            key={index} 
                            label={category} 
                            color="primary" 
                            size="small"
                          />
                        ))}
                      </Box>
                    </CardContent>
                  </Card>
                </Grid>
              </Grid>
            </Grid>
          </Grid>

          <Grid container spacing={2} sx={{ mb: 2 }}>
            <Grid item xs={12} md={6}>
              {renderCategoryDistribution()}
            </Grid>
            <Grid item xs={12} md={6}>
              {renderPerformanceDistribution()}
            </Grid>
          </Grid>

          <Card sx={{ mb: 2 }}>
            <CardContent>
              <Grid container spacing={2} alignItems="center">
                <Grid item xs={12} md={4}>
                  <TextField
                    fullWidth
                    placeholder="제품명 검색"
                    value={searchTerm}
                    onChange={handleSearchChange}
                    variant="outlined"
                    size="small"
                    InputProps={{
                      startAdornment: (
                        <InputAdornment position="start">
                          <SearchIcon />
                        </InputAdornment>
                      ),
                    }}
                  />
                </Grid>
                <Grid item xs={12} md={4}>
                  <FormControl fullWidth size="small">
                    <InputLabel>필터 지표</InputLabel>
                    <Select
                      value={filterMetric}
                      label="필터 지표"
                      onChange={handleFilterMetricChange}
                    >
                      <MenuItem value="all">모든 제품</MenuItem>
                      <MenuItem value="revenue">매출</MenuItem>
                      <MenuItem value="profit">수익</MenuItem>
                      <MenuItem value="margin">마진율</MenuItem>
                      <MenuItem value="orders">주문수</MenuItem>
                      <MenuItem value="units_sold">판매 수량</MenuItem>
                      <MenuItem value="conversion_rate">전환율</MenuItem>
                      <MenuItem value="rating">평점</MenuItem>
                      <MenuItem value="performance_score">성과 점수</MenuItem>
                    </Select>
                  </FormControl>
                </Grid>
                <Grid item xs={12} md={4}>
                  <TextField
                    fullWidth
                    label="최소값"
                    type="number"
                    value={filterThreshold}
                    onChange={handleFilterThresholdChange}
                    variant="outlined"
                    size="small"
                    disabled={filterMetric === 'all'}
                    InputProps={{
                      startAdornment: (
                        <InputAdornment position="start">
                          <FilterListIcon />
                        </InputAdornment>
                      ),
                    }}
                  />
                </Grid>
              </Grid>
            </CardContent>
          </Card>

          <Card>
            <CardHeader 
              title="제품 상세 목록" 
              action={
                <Badge 
                  badgeContent={filteredProducts().length} 
                  color="primary"
                  sx={{ mr: 2 }}
                >
                  <InventoryIcon />
                </Badge>
              }
            />
            <Divider />
            <CardContent>
              {renderProductsTable()}
            </CardContent>
          </Card>
        </>
      )}
    </Box>
  );
};

export default TopProducts; 