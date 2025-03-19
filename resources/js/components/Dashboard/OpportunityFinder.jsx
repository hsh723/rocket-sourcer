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
  Chip,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  ListItemAvatar,
  Avatar,
  Paper,
  Tabs,
  Tab,
  Badge,
  LinearProgress,
  useTheme
} from '@mui/material';
import { 
  Refresh as RefreshIcon,
  Info as InfoIcon,
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  Lightbulb as LightbulbIcon,
  Category as CategoryIcon,
  ShoppingCart as ShoppingCartIcon,
  AttachMoney as AttachMoneyIcon,
  Inventory as InventoryIcon,
  LocalOffer as LocalOfferIcon,
  NewReleases as NewReleasesIcon,
  Warning as WarningIcon,
  Star as StarIcon,
  ArrowUpward as ArrowUpwardIcon,
  ArrowDownward as ArrowDownwardIcon
} from '@mui/icons-material';
import axios from 'axios';
import { 
  ResponsiveContainer, 
  ScatterChart, 
  Scatter, 
  XAxis, 
  YAxis, 
  ZAxis,
  CartesianGrid, 
  Tooltip as RechartsTooltip, 
  Legend,
  Cell,
  LabelList,
  Radar,
  RadarChart,
  PolarGrid,
  PolarAngleAxis,
  PolarRadiusAxis,
  Treemap
} from 'recharts';

const OpportunityFinder = ({ dateRange, refreshInterval = 300000 }) => {
  const theme = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [opportunityData, setOpportunityData] = useState(null);
  const [lastUpdated, setLastUpdated] = useState(null);
  const [activeTab, setActiveTab] = useState(0);

  useEffect(() => {
    fetchOpportunityData();
    
    // 자동 새로고침 설정
    const intervalId = setInterval(() => {
      fetchOpportunityData();
    }, refreshInterval);
    
    return () => clearInterval(intervalId);
  }, [dateRange]);

  const fetchOpportunityData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // 날짜 범위 파라미터 구성
      const params = {};
      if (dateRange) {
        params.start_date = dateRange.startDate;
        params.end_date = dateRange.endDate;
      }
      
      const response = await axios.get('/api/dashboard/opportunity-finder', { params });
      
      if (response.data.success) {
        setOpportunityData(response.data.data);
        setLastUpdated(new Date());
      } else {
        setError(response.data.message || '기회 데이터를 불러오는데 실패했습니다.');
      }
    } catch (err) {
      setError('기회 데이터를 불러오는 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message));
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

  const getOpportunityColor = (score) => {
    if (score >= 70) return theme.palette.success.main;
    if (score >= 40) return theme.palette.warning.main;
    return theme.palette.error.main;
  };

  const handleTabChange = (event, newValue) => {
    setActiveTab(newValue);
  };

  const renderOpportunityScatterChart = () => {
    if (!opportunityData || !opportunityData.market_opportunities) return null;
    
    const { market_opportunities } = opportunityData;
    
    // 데이터 포인트 크기 조정
    const sizeData = market_opportunities.map(item => ({
      ...item,
      z: item.opportunity_score * 5 // 점수에 비례하여 크기 조정
    }));
    
    return (
      <ResponsiveContainer width="100%" height={400}>
        <ScatterChart
          margin={{ top: 20, right: 20, bottom: 20, left: 20 }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis 
            type="number" 
            dataKey="competition_level" 
            name="경쟁 강도" 
            domain={[0, 10]}
            label={{ value: '경쟁 강도', position: 'bottom', offset: 0 }}
          />
          <YAxis 
            type="number" 
            dataKey="growth_rate" 
            name="성장률" 
            unit="%" 
            label={{ value: '성장률 (%)', angle: -90, position: 'left' }}
          />
          <ZAxis 
            type="number" 
            dataKey="z" 
            range={[100, 500]} 
          />
          <RechartsTooltip 
            cursor={{ strokeDasharray: '3 3' }}
            formatter={(value, name) => {
              if (name === '경쟁 강도') return [value, name];
              if (name === '성장률') return [`${value}%`, name];
              return [value, name];
            }}
            content={({ active, payload }) => {
              if (active && payload && payload.length) {
                const data = payload[0].payload;
                return (
                  <Paper sx={{ p: 1, bgcolor: 'background.paper' }}>
                    <Typography variant="subtitle2">{data.category}</Typography>
                    <Typography variant="body2">경쟁 강도: {data.competition_level}/10</Typography>
                    <Typography variant="body2">성장률: {data.growth_rate}%</Typography>
                    <Typography variant="body2">시장 규모: {formatCurrency(data.market_size)}</Typography>
                    <Typography variant="body2">기회 점수: {data.opportunity_score}</Typography>
                  </Paper>
                );
              }
              return null;
            }}
          />
          <Legend />
          <Scatter 
            name="카테고리 기회" 
            data={sizeData} 
            fill={theme.palette.primary.main}
          >
            {sizeData.map((entry, index) => (
              <Cell 
                key={`cell-${index}`} 
                fill={getOpportunityColor(entry.opportunity_score)} 
              />
            ))}
          </Scatter>
        </ScatterChart>
      </ResponsiveContainer>
    );
  };

  const renderTrendOpportunities = () => {
    if (!opportunityData || !opportunityData.trend_opportunities) return null;
    
    const { trend_opportunities } = opportunityData;
    
    return (
      <List>
        {trend_opportunities.map((trend, index) => (
          <ListItem 
            key={index}
            divider={index < trend_opportunities.length - 1}
            sx={{ 
              py: 2,
              '&:hover': {
                bgcolor: 'action.hover',
              }
            }}
          >
            <ListItemAvatar>
              <Avatar 
                sx={{ 
                  bgcolor: getOpportunityColor(trend.opportunity_score),
                  width: 48,
                  height: 48
                }}
              >
                <TrendingUpIcon />
              </Avatar>
            </ListItemAvatar>
            <ListItemText
              primary={
                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 'bold' }}>
                    {trend.name}
                  </Typography>
                  <Chip 
                    label={`${trend.opportunity_score} 점`} 
                    size="small" 
                    sx={{ ml: 1, bgcolor: getOpportunityColor(trend.opportunity_score), color: 'white' }}
                  />
                </Box>
              }
              secondary={
                <>
                  <Typography variant="body2" color="text.secondary">
                    {trend.description}
                  </Typography>
                  <Box sx={{ display: 'flex', mt: 1, flexWrap: 'wrap', gap: 1 }}>
                    <Chip 
                      size="small" 
                      label={`성장률: ${trend.growth_rate}%`} 
                      icon={<TrendingUpIcon />} 
                      variant="outlined"
                    />
                    <Chip 
                      size="small" 
                      label={`검색량: ${formatNumber(trend.search_volume)}`} 
                      icon={<SearchIcon />} 
                      variant="outlined"
                    />
                    <Chip 
                      size="small" 
                      label={`관련 카테고리: ${trend.related_categories.join(', ')}`} 
                      icon={<CategoryIcon />} 
                      variant="outlined"
                    />
                  </Box>
                </>
              }
            />
          </ListItem>
        ))}
      </List>
    );
  };

  const renderKeywordOpportunities = () => {
    if (!opportunityData || !opportunityData.keyword_opportunities) return null;
    
    const { keyword_opportunities } = opportunityData;
    
    return (
      <ResponsiveContainer width="100%" height={400}>
        <Treemap
          data={keyword_opportunities}
          dataKey="search_volume"
          aspectRatio={4/3}
          stroke="#fff"
          fill={theme.palette.primary.main}
        >
          <RechartsTooltip 
            content={({ active, payload }) => {
              if (active && payload && payload.length) {
                const data = payload[0].payload;
                return (
                  <Paper sx={{ p: 1, bgcolor: 'background.paper' }}>
                    <Typography variant="subtitle2">{data.keyword}</Typography>
                    <Typography variant="body2">검색량: {formatNumber(data.search_volume)}</Typography>
                    <Typography variant="body2">경쟁 강도: {data.competition_level}/10</Typography>
                    <Typography variant="body2">성장률: {data.growth_rate}%</Typography>
                    <Typography variant="body2">기회 점수: {data.opportunity_score}</Typography>
                  </Paper>
                );
              }
              return null;
            }}
          />
          {keyword_opportunities.map((entry, index) => (
            <Cell 
              key={`cell-${index}`} 
              fill={getOpportunityColor(entry.opportunity_score)} 
            >
              <LabelList 
                dataKey="keyword" 
                style={{ 
                  fontSize: 12, 
                  fill: '#fff',
                  textShadow: '0px 0px 3px rgba(0, 0, 0, 0.5)'
                }} 
              />
            </Cell>
          ))}
        </Treemap>
      </ResponsiveContainer>
    );
  };

  const renderProductGapOpportunities = () => {
    if (!opportunityData || !opportunityData.product_gap_opportunities) return null;
    
    const { product_gap_opportunities } = opportunityData;
    
    return (
      <ResponsiveContainer width="100%" height={400}>
        <RadarChart outerRadius={150} data={product_gap_opportunities}>
          <PolarGrid />
          <PolarAngleAxis dataKey="category" />
          <PolarRadiusAxis angle={30} domain={[0, 100]} />
          <Radar 
            name="수요" 
            dataKey="demand_score" 
            stroke={theme.palette.primary.main} 
            fill={theme.palette.primary.main} 
            fillOpacity={0.6} 
          />
          <Radar 
            name="공급" 
            dataKey="supply_score" 
            stroke={theme.palette.secondary.main} 
            fill={theme.palette.secondary.main} 
            fillOpacity={0.6} 
          />
          <Radar 
            name="기회 점수" 
            dataKey="opportunity_score" 
            stroke={theme.palette.success.main} 
            fill={theme.palette.success.main} 
            fillOpacity={0.6} 
          />
          <Legend />
          <RechartsTooltip />
        </RadarChart>
      </ResponsiveContainer>
    );
  };

  const renderOpportunityList = () => {
    if (!opportunityData || !opportunityData.top_opportunities) return null;
    
    const { top_opportunities } = opportunityData;
    
    return (
      <List>
        {top_opportunities.map((opportunity, index) => (
          <ListItem 
            key={index}
            divider={index < top_opportunities.length - 1}
            sx={{ 
              py: 2,
              '&:hover': {
                bgcolor: 'action.hover',
              }
            }}
          >
            <ListItemIcon>
              <Avatar 
                sx={{ 
                  bgcolor: getOpportunityColor(opportunity.score),
                  width: 48,
                  height: 48
                }}
              >
                {opportunity.type === 'category' && <CategoryIcon />}
                {opportunity.type === 'product' && <InventoryIcon />}
                {opportunity.type === 'trend' && <TrendingUpIcon />}
                {opportunity.type === 'keyword' && <LocalOfferIcon />}
              </Avatar>
            </ListItemIcon>
            <ListItemText
              primary={
                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 'bold' }}>
                    {opportunity.name}
                  </Typography>
                  <Chip 
                    label={`${opportunity.score} 점`} 
                    size="small" 
                    sx={{ ml: 1, bgcolor: getOpportunityColor(opportunity.score), color: 'white' }}
                  />
                </Box>
              }
              secondary={
                <>
                  <Typography variant="body2" color="text.secondary">
                    {opportunity.description}
                  </Typography>
                  <Box sx={{ display: 'flex', mt: 1, flexWrap: 'wrap', gap: 1 }}>
                    {opportunity.potential_revenue && (
                      <Chip 
                        size="small" 
                        label={`잠재 매출: ${formatCurrency(opportunity.potential_revenue)}`} 
                        icon={<AttachMoneyIcon />} 
                        variant="outlined"
                      />
                    )}
                    {opportunity.growth_rate && (
                      <Chip 
                        size="small" 
                        label={`성장률: ${opportunity.growth_rate}%`} 
                        icon={<TrendingUpIcon />} 
                        variant="outlined"
                      />
                    )}
                    {opportunity.competition_level && (
                      <Chip 
                        size="small" 
                        label={`경쟁 강도: ${opportunity.competition_level}/10`} 
                        icon={<WarningIcon />} 
                        variant="outlined"
                      />
                    )}
                    {opportunity.type && (
                      <Chip 
                        size="small" 
                        label={
                          opportunity.type === 'category' ? '카테고리' :
                          opportunity.type === 'product' ? '제품' :
                          opportunity.type === 'trend' ? '트렌드' :
                          opportunity.type === 'keyword' ? '키워드' :
                          opportunity.type
                        } 
                        variant="outlined"
                      />
                    )}
                  </Box>
                </>
              }
            />
            <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', ml: 2 }}>
              <Typography 
                variant="h5" 
                sx={{ 
                  color: getOpportunityColor(opportunity.score),
                  fontWeight: 'bold'
                }}
              >
                {opportunity.score}
              </Typography>
              <Typography variant="caption" color="text.secondary">
                기회 점수
              </Typography>
            </Box>
          </ListItem>
        ))}
      </List>
    );
  };

  if (loading && !opportunityData) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 400 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error && !opportunityData) {
    return (
      <Box sx={{ mt: 2, mb: 2 }}>
        <Alert 
          severity="error" 
          action={
            <Button color="inherit" size="small" onClick={fetchOpportunityData}>
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
          기회 발굴
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
            onClick={fetchOpportunityData}
            disabled={loading}
          >
            {loading ? <CircularProgress size={20} /> : '새로고침'}
          </Button>
        </Box>
      </Box>

      {opportunityData && (
        <>
          <Grid container spacing={2} sx={{ mb: 2 }}>
            <Grid item xs={12} sm={6} md={3}>
              <Card>
                <CardContent>
                  <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                    <Avatar sx={{ bgcolor: theme.palette.primary.main, mr: 2 }}>
                      <LightbulbIcon />
                    </Avatar>
                    <Typography variant="h6" color="text.secondary">
                      발견된 기회
                    </Typography>
                  </Box>
                  <Typography variant="h4">
                    {formatNumber(opportunityData.total_opportunities)}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
            <Grid item xs={12} sm={6} md={3}>
              <Card>
                <CardContent>
                  <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                    <Avatar sx={{ bgcolor: theme.palette.success.main, mr: 2 }}>
                      <TrendingUpIcon />
                    </Avatar>
                    <Typography variant="h6" color="text.secondary">
                      성장 트렌드
                    </Typography>
                  </Box>
                  <Typography variant="h4">
                    {formatNumber(opportunityData.growing_trends)}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
            <Grid item xs={12} sm={6} md={3}>
              <Card>
                <CardContent>
                  <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                    <Avatar sx={{ bgcolor: theme.palette.warning.main, mr: 2 }}>
                      <CategoryIcon />
                    </Avatar>
                    <Typography variant="h6" color="text.secondary">
                      유망 카테고리
                    </Typography>
                  </Box>
                  <Typography variant="h4">
                    {formatNumber(opportunityData.promising_categories)}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
            <Grid item xs={12} sm={6} md={3}>
              <Card>
                <CardContent>
                  <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                    <Avatar sx={{ bgcolor: theme.palette.info.main, mr: 2 }}>
                      <LocalOfferIcon />
                    </Avatar>
                    <Typography variant="h6" color="text.secondary">
                      키워드 기회
                    </Typography>
                  </Box>
                  <Typography variant="h4">
                    {formatNumber(opportunityData.keyword_opportunities?.length || 0)}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
          </Grid>

          <Card sx={{ mb: 2 }}>
            <CardHeader 
              title="상위 기회 목록" 
              action={
                <Tooltip title="가장 높은 기회 점수를 가진 항목">
                  <IconButton>
                    <InfoIcon />
                  </IconButton>
                </Tooltip>
              }
            />
            <Divider />
            <CardContent>
              {renderOpportunityList()}
            </CardContent>
          </Card>

          <Card>
            <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
              <Tabs 
                value={activeTab} 
                onChange={handleTabChange}
                variant="fullWidth"
              >
                <Tab 
                  label="시장 기회" 
                  icon={<CategoryIcon />} 
                  iconPosition="start"
                />
                <Tab 
                  label="트렌드 기회" 
                  icon={<TrendingUpIcon />} 
                  iconPosition="start"
                />
                <Tab 
                  label="키워드 기회" 
                  icon={<LocalOfferIcon />} 
                  iconPosition="start"
                />
                <Tab 
                  label="제품 갭 분석" 
                  icon={<InventoryIcon />} 
                  iconPosition="start"
                />
              </Tabs>
            </Box>
            <CardContent>
              <Box sx={{ p: 2 }}>
                {activeTab === 0 && (
                  <>
                    <Typography variant="subtitle1" gutterBottom>
                      시장 기회 분석
                    </Typography>
                    <Typography variant="body2" color="text.secondary" paragraph>
                      경쟁 강도와 성장률을 기준으로 한 카테고리별 기회 분석입니다. 
                      점의 크기는 기회 점수를 나타내며, 색상은 기회의 강도를 나타냅니다.
                    </Typography>
                    {renderOpportunityScatterChart()}
                  </>
                )}
                {activeTab === 1 && (
                  <>
                    <Typography variant="subtitle1" gutterBottom>
                      트렌드 기회 분석
                    </Typography>
                    <Typography variant="body2" color="text.secondary" paragraph>
                      현재 성장 중인 트렌드와 관련된 기회 목록입니다. 
                      각 트렌드의 성장률, 검색량, 관련 카테고리 정보를 확인할 수 있습니다.
                    </Typography>
                    {renderTrendOpportunities()}
                  </>
                )}
                {activeTab === 2 && (
                  <>
                    <Typography variant="subtitle1" gutterBottom>
                      키워드 기회 분석
                    </Typography>
                    <Typography variant="body2" color="text.secondary" paragraph>
                      검색량과 경쟁 강도를 기준으로 한 키워드 기회 분석입니다. 
                      박스의 크기는 검색량을, 색상은 기회 점수를 나타냅니다.
                    </Typography>
                    {renderKeywordOpportunities()}
                  </>
                )}
                {activeTab === 3 && (
                  <>
                    <Typography variant="subtitle1" gutterBottom>
                      제품 갭 분석
                    </Typography>
                    <Typography variant="body2" color="text.secondary" paragraph>
                      수요와 공급의 차이를 기준으로 한 제품 갭 분석입니다. 
                      수요 점수와 공급 점수의 차이가 클수록 기회 점수가 높아집니다.
                    </Typography>
                    {renderProductGapOpportunities()}
                  </>
                )}
              </Box>
            </CardContent>
          </Card>
        </>
      )}
    </Box>
  );
};

export default OpportunityFinder; 