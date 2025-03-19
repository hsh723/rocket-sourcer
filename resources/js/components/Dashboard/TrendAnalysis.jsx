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
  ToggleButton,
  ToggleButtonGroup,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  useTheme
} from '@mui/material';
import { 
  Refresh as RefreshIcon,
  Info as InfoIcon,
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  Timeline as TimelineIcon,
  ShowChart as ShowChartIcon,
  BarChart as BarChartIcon,
  DonutLarge as DonutLargeIcon
} from '@mui/icons-material';
import axios from 'axios';
import { 
  ResponsiveContainer, 
  LineChart, 
  Line, 
  AreaChart, 
  Area, 
  BarChart, 
  Bar, 
  ComposedChart,
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip as RechartsTooltip, 
  Legend,
  Brush,
  ReferenceArea,
  ReferenceLine,
  Label
} from 'recharts';

const TrendAnalysis = ({ dateRange, selectedMetrics = ['revenue', 'profit', 'orders'], refreshInterval = 300000 }) => {
  const theme = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [trendData, setTrendData] = useState(null);
  const [lastUpdated, setLastUpdated] = useState(null);
  const [chartType, setChartType] = useState('line');
  const [timeFrame, setTimeFrame] = useState('daily');
  const [comparisonMode, setComparisonMode] = useState('none');
  const [forecastEnabled, setForecastEnabled] = useState(false);
  const [selectedMetricsState, setSelectedMetricsState] = useState(selectedMetrics);

  useEffect(() => {
    fetchTrendData();
    
    // 자동 새로고침 설정
    const intervalId = setInterval(() => {
      fetchTrendData();
    }, refreshInterval);
    
    return () => clearInterval(intervalId);
  }, [dateRange, timeFrame, comparisonMode, forecastEnabled, selectedMetricsState]);

  const fetchTrendData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // 파라미터 구성
      const params = {
        time_frame: timeFrame,
        comparison: comparisonMode,
        forecast: forecastEnabled,
        metrics: selectedMetricsState.join(',')
      };
      
      if (dateRange) {
        params.start_date = dateRange.startDate;
        params.end_date = dateRange.endDate;
      }
      
      const response = await axios.get('/api/dashboard/trend-analysis', { params });
      
      if (response.data.success) {
        setTrendData(response.data.data);
        setLastUpdated(new Date());
      } else {
        setError(response.data.message || '트렌드 데이터를 불러오는데 실패했습니다.');
      }
    } catch (err) {
      setError('트렌드 데이터를 불러오는 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message));
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

  const handleChartTypeChange = (event, newChartType) => {
    if (newChartType !== null) {
      setChartType(newChartType);
    }
  };

  const handleTimeFrameChange = (event) => {
    setTimeFrame(event.target.value);
  };

  const handleComparisonModeChange = (event) => {
    setComparisonMode(event.target.value);
  };

  const handleForecastToggle = () => {
    setForecastEnabled(!forecastEnabled);
  };

  const handleMetricsChange = (event) => {
    setSelectedMetricsState(event.target.value);
  };

  const getMetricColor = (metric) => {
    switch (metric) {
      case 'revenue':
        return theme.palette.primary.main;
      case 'profit':
        return theme.palette.success.main;
      case 'orders':
        return theme.palette.secondary.main;
      case 'margin':
        return theme.palette.info.main;
      case 'average_order_value':
        return theme.palette.warning.main;
      case 'conversion_rate':
        return theme.palette.error.main;
      default:
        return theme.palette.grey[500];
    }
  };

  const getMetricName = (metric) => {
    switch (metric) {
      case 'revenue':
        return '매출';
      case 'profit':
        return '수익';
      case 'orders':
        return '주문수';
      case 'margin':
        return '마진율';
      case 'average_order_value':
        return '평균 주문 금액';
      case 'conversion_rate':
        return '전환율';
      default:
        return metric;
    }
  };

  const getMetricFormatter = (metric) => {
    switch (metric) {
      case 'revenue':
      case 'profit':
      case 'average_order_value':
        return formatCurrency;
      case 'margin':
      case 'conversion_rate':
        return formatPercent;
      case 'orders':
      default:
        return formatNumber;
    }
  };

  const renderTrendChart = () => {
    if (!trendData || !trendData.trend_data) return null;
    
    const { trend_data, forecast_data } = trendData;
    
    // 예측 데이터가 있고 예측 모드가 활성화된 경우 데이터 합치기
    const combinedData = forecastEnabled && forecast_data 
      ? [...trend_data, ...forecast_data] 
      : trend_data;
    
    // 차트 타입에 따라 다른 차트 렌더링
    switch (chartType) {
      case 'line':
        return (
          <ResponsiveContainer width="100%" height={400}>
            <LineChart
              data={combinedData}
              margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
            >
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="date" />
              {selectedMetricsState.map((metric, index) => (
                <YAxis 
                  key={metric}
                  yAxisId={metric}
                  orientation={index % 2 === 0 ? 'left' : 'right'}
                  tickFormatter={getMetricFormatter(metric)}
                  hide={index > 1} // 최대 2개의 Y축만 표시
                />
              ))}
              <RechartsTooltip 
                formatter={(value, name) => {
                  const formatter = getMetricFormatter(name);
                  return [formatter(value), getMetricName(name)];
                }}
              />
              <Legend />
              <Brush dataKey="date" height={30} stroke={theme.palette.primary.main} />
              
              {forecastEnabled && forecast_data && (
                <ReferenceArea 
                  x1={trend_data[trend_data.length - 1].date} 
                  x2={combinedData[combinedData.length - 1].date} 
                  strokeOpacity={0.3}
                  fill={theme.palette.grey[200]}
                >
                  <Label value="예측 데이터" position="insideTopRight" />
                </ReferenceArea>
              )}
              
              {selectedMetricsState.map(metric => (
                <Line 
                  key={metric}
                  yAxisId={metric}
                  type="monotone" 
                  dataKey={metric} 
                  name={getMetricName(metric)} 
                  stroke={getMetricColor(metric)} 
                  activeDot={{ r: 8 }} 
                  dot={{ r: 3 }}
                  strokeWidth={2}
                />
              ))}
            </LineChart>
          </ResponsiveContainer>
        );
        
      case 'area':
        return (
          <ResponsiveContainer width="100%" height={400}>
            <AreaChart
              data={combinedData}
              margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
            >
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="date" />
              {selectedMetricsState.map((metric, index) => (
                <YAxis 
                  key={metric}
                  yAxisId={metric}
                  orientation={index % 2 === 0 ? 'left' : 'right'}
                  tickFormatter={getMetricFormatter(metric)}
                  hide={index > 1} // 최대 2개의 Y축만 표시
                />
              ))}
              <RechartsTooltip 
                formatter={(value, name) => {
                  const formatter = getMetricFormatter(name);
                  return [formatter(value), getMetricName(name)];
                }}
              />
              <Legend />
              <Brush dataKey="date" height={30} stroke={theme.palette.primary.main} />
              
              {forecastEnabled && forecast_data && (
                <ReferenceArea 
                  x1={trend_data[trend_data.length - 1].date} 
                  x2={combinedData[combinedData.length - 1].date} 
                  strokeOpacity={0.3}
                  fill={theme.palette.grey[200]}
                >
                  <Label value="예측 데이터" position="insideTopRight" />
                </ReferenceArea>
              )}
              
              {selectedMetricsState.map(metric => (
                <Area 
                  key={metric}
                  yAxisId={metric}
                  type="monotone" 
                  dataKey={metric} 
                  name={getMetricName(metric)} 
                  stroke={getMetricColor(metric)} 
                  fill={getMetricColor(metric)}
                  fillOpacity={0.3}
                />
              ))}
            </AreaChart>
          </ResponsiveContainer>
        );
        
      case 'bar':
        return (
          <ResponsiveContainer width="100%" height={400}>
            <BarChart
              data={combinedData}
              margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
            >
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="date" />
              {selectedMetricsState.map((metric, index) => (
                <YAxis 
                  key={metric}
                  yAxisId={metric}
                  orientation={index % 2 === 0 ? 'left' : 'right'}
                  tickFormatter={getMetricFormatter(metric)}
                  hide={index > 1} // 최대 2개의 Y축만 표시
                />
              ))}
              <RechartsTooltip 
                formatter={(value, name) => {
                  const formatter = getMetricFormatter(name);
                  return [formatter(value), getMetricName(name)];
                }}
              />
              <Legend />
              <Brush dataKey="date" height={30} stroke={theme.palette.primary.main} />
              
              {forecastEnabled && forecast_data && (
                <ReferenceArea 
                  x1={trend_data[trend_data.length - 1].date} 
                  x2={combinedData[combinedData.length - 1].date} 
                  strokeOpacity={0.3}
                  fill={theme.palette.grey[200]}
                >
                  <Label value="예측 데이터" position="insideTopRight" />
                </ReferenceArea>
              )}
              
              {selectedMetricsState.map(metric => (
                <Bar 
                  key={metric}
                  yAxisId={metric}
                  dataKey={metric} 
                  name={getMetricName(metric)} 
                  fill={getMetricColor(metric)}
                  radius={[4, 4, 0, 0]}
                />
              ))}
            </BarChart>
          </ResponsiveContainer>
        );
        
      case 'composed':
        return (
          <ResponsiveContainer width="100%" height={400}>
            <ComposedChart
              data={combinedData}
              margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
            >
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="date" />
              {selectedMetricsState.map((metric, index) => (
                <YAxis 
                  key={metric}
                  yAxisId={metric}
                  orientation={index % 2 === 0 ? 'left' : 'right'}
                  tickFormatter={getMetricFormatter(metric)}
                  hide={index > 1} // 최대 2개의 Y축만 표시
                />
              ))}
              <RechartsTooltip 
                formatter={(value, name) => {
                  const formatter = getMetricFormatter(name);
                  return [formatter(value), getMetricName(name)];
                }}
              />
              <Legend />
              <Brush dataKey="date" height={30} stroke={theme.palette.primary.main} />
              
              {forecastEnabled && forecast_data && (
                <ReferenceArea 
                  x1={trend_data[trend_data.length - 1].date} 
                  x2={combinedData[combinedData.length - 1].date} 
                  strokeOpacity={0.3}
                  fill={theme.palette.grey[200]}
                >
                  <Label value="예측 데이터" position="insideTopRight" />
                </ReferenceArea>
              )}
              
              {selectedMetricsState.map((metric, index) => {
                // 첫 번째 지표는 영역 차트, 두 번째는 선 차트, 나머지는 막대 차트로 표시
                if (index === 0) {
                  return (
                    <Area 
                      key={metric}
                      yAxisId={metric}
                      type="monotone" 
                      dataKey={metric} 
                      name={getMetricName(metric)} 
                      stroke={getMetricColor(metric)} 
                      fill={getMetricColor(metric)}
                      fillOpacity={0.3}
                    />
                  );
                } else if (index === 1) {
                  return (
                    <Line 
                      key={metric}
                      yAxisId={metric}
                      type="monotone" 
                      dataKey={metric} 
                      name={getMetricName(metric)} 
                      stroke={getMetricColor(metric)} 
                      activeDot={{ r: 8 }} 
                      dot={{ r: 3 }}
                      strokeWidth={2}
                    />
                  );
                } else {
                  return (
                    <Bar 
                      key={metric}
                      yAxisId={metric}
                      dataKey={metric} 
                      name={getMetricName(metric)} 
                      fill={getMetricColor(metric)}
                      radius={[4, 4, 0, 0]}
                    />
                  );
                }
              })}
            </ComposedChart>
          </ResponsiveContainer>
        );
        
      default:
        return null;
    }
  };

  const renderTrendSummary = () => {
    if (!trendData || !trendData.summary) return null;
    
    const { summary } = trendData;
    
    return (
      <Grid container spacing={2} sx={{ mb: 2 }}>
        {selectedMetricsState.map(metric => {
          const metricSummary = summary[metric];
          if (!metricSummary) return null;
          
          return (
            <Grid item xs={12} sm={6} md={4} key={metric}>
              <Card>
                <CardContent>
                  <Typography variant="h6" color="text.secondary" gutterBottom>
                    {getMetricName(metric)}
                  </Typography>
                  
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
                    <Typography variant="h4">
                      {getMetricFormatter(metric)(metricSummary.current)}
                    </Typography>
                    <Box sx={{ display: 'flex', alignItems: 'center' }}>
                      {metricSummary.change >= 0 ? (
                        <TrendingUpIcon sx={{ color: theme.palette.success.main }} />
                      ) : (
                        <TrendingDownIcon sx={{ color: theme.palette.error.main }} />
                      )}
                      <Typography 
                        variant="body2" 
                        sx={{ 
                          ml: 0.5, 
                          color: metricSummary.change >= 0 ? theme.palette.success.main : theme.palette.error.main,
                          fontWeight: 'bold'
                        }}
                      >
                        {metricSummary.change >= 0 ? '+' : ''}{metricSummary.change}%
                      </Typography>
                    </Box>
                  </Box>
                  
                  <Typography variant="body2" color="text.secondary">
                    {comparisonMode === 'previous_period' ? '이전 기간 대비' : 
                     comparisonMode === 'previous_year' ? '전년 동기 대비' : 
                     '변화율'}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
          );
        })}
      </Grid>
    );
  };

  const renderSeasonalityAnalysis = () => {
    if (!trendData || !trendData.seasonality) return null;
    
    return (
      <Card sx={{ mt: 2 }}>
        <CardHeader 
          title="계절성 분석" 
          action={
            <Tooltip title="시간에 따른 패턴 분석">
              <IconButton>
                <InfoIcon />
              </IconButton>
            </Tooltip>
          }
        />
        <Divider />
        <CardContent>
          <Grid container spacing={2}>
            {selectedMetricsState.slice(0, 2).map(metric => {
              const seasonalityData = trendData.seasonality[metric];
              if (!seasonalityData) return null;
              
              return (
                <Grid item xs={12} md={6} key={metric}>
                  <Typography variant="subtitle1" gutterBottom>
                    {getMetricName(metric)} 계절성
                  </Typography>
                  <ResponsiveContainer width="100%" height={200}>
                    <BarChart
                      data={seasonalityData}
                      margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
                    >
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="period" />
                      <YAxis tickFormatter={(value) => `${value}%`} />
                      <RechartsTooltip formatter={(value) => `${value}%`} />
                      <Bar 
                        dataKey="value" 
                        name="변동" 
                        fill={getMetricColor(metric)}
                        radius={[4, 4, 0, 0]}
                      >
                        {seasonalityData.map((entry, index) => (
                          <Cell 
                            key={`cell-${index}`} 
                            fill={entry.value >= 0 ? theme.palette.success.main : theme.palette.error.main} 
                          />
                        ))}
                      </Bar>
                      <ReferenceLine y={0} stroke="#000" />
                    </BarChart>
                  </ResponsiveContainer>
                </Grid>
              );
            })}
          </Grid>
        </CardContent>
      </Card>
    );
  };

  if (loading && !trendData) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 400 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error && !trendData) {
    return (
      <Box sx={{ mt: 2, mb: 2 }}>
        <Alert 
          severity="error" 
          action={
            <Button color="inherit" size="small" onClick={fetchTrendData}>
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
          트렌드 분석
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
            onClick={fetchTrendData}
            disabled={loading}
          >
            {loading ? <CircularProgress size={20} /> : '새로고침'}
          </Button>
        </Box>
      </Box>

      <Grid container spacing={2} sx={{ mb: 2 }}>
        <Grid item xs={12} sm={6} md={3}>
          <FormControl fullWidth size="small">
            <InputLabel>시간 단위</InputLabel>
            <Select
              value={timeFrame}
              label="시간 단위"
              onChange={handleTimeFrameChange}
            >
              <MenuItem value="daily">일별</MenuItem>
              <MenuItem value="weekly">주별</MenuItem>
              <MenuItem value="monthly">월별</MenuItem>
              <MenuItem value="quarterly">분기별</MenuItem>
              <MenuItem value="yearly">연도별</MenuItem>
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <FormControl fullWidth size="small">
            <InputLabel>비교 모드</InputLabel>
            <Select
              value={comparisonMode}
              label="비교 모드"
              onChange={handleComparisonModeChange}
            >
              <MenuItem value="none">비교 없음</MenuItem>
              <MenuItem value="previous_period">이전 기간</MenuItem>
              <MenuItem value="previous_year">전년 동기</MenuItem>
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <FormControl fullWidth size="small">
            <InputLabel>지표 선택</InputLabel>
            <Select
              multiple
              value={selectedMetricsState}
              label="지표 선택"
              onChange={handleMetricsChange}
              renderValue={(selected) => selected.map(s => getMetricName(s)).join(', ')}
            >
              <MenuItem value="revenue">매출</MenuItem>
              <MenuItem value="profit">수익</MenuItem>
              <MenuItem value="orders">주문수</MenuItem>
              <MenuItem value="margin">마진율</MenuItem>
              <MenuItem value="average_order_value">평균 주문 금액</MenuItem>
              <MenuItem value="conversion_rate">전환율</MenuItem>
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
            <ToggleButtonGroup
              value={chartType}
              exclusive
              onChange={handleChartTypeChange}
              size="small"
            >
              <ToggleButton value="line">
                <Tooltip title="선 차트">
                  <ShowChartIcon />
                </Tooltip>
              </ToggleButton>
              <ToggleButton value="area">
                <Tooltip title="영역 차트">
                  <TimelineIcon />
                </Tooltip>
              </ToggleButton>
              <ToggleButton value="bar">
                <Tooltip title="막대 차트">
                  <BarChartIcon />
                </Tooltip>
              </ToggleButton>
              <ToggleButton value="composed">
                <Tooltip title="복합 차트">
                  <DonutLargeIcon />
                </Tooltip>
              </ToggleButton>
            </ToggleButtonGroup>
            
            <Button
              variant={forecastEnabled ? "contained" : "outlined"}
              size="small"
              onClick={handleForecastToggle}
              color={forecastEnabled ? "primary" : "inherit"}
            >
              예측
            </Button>
          </Box>
        </Grid>
      </Grid>

      {trendData && (
        <>
          {renderTrendSummary()}
          
          <Card>
            <CardHeader 
              title="트렌드 차트" 
              action={
                <Tooltip title="시간에 따른 지표 변화">
                  <IconButton>
                    <InfoIcon />
                  </IconButton>
                </Tooltip>
              }
            />
            <Divider />
            <CardContent>
              {renderTrendChart()}
            </CardContent>
          </Card>
          
          {renderSeasonalityAnalysis()}
        </>
      )}
    </Box>
  );
};

export default TrendAnalysis; 