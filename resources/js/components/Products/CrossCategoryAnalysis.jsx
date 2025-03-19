import React, { useState, useEffect } from 'react';
import { 
  Box, 
  Typography, 
  Paper, 
  Grid, 
  Card, 
  CardContent, 
  CardHeader,
  Divider,
  Button,
  CircularProgress,
  Alert,
  Chip,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  LinearProgress,
  Tooltip,
  IconButton
} from '@mui/material';
import { 
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  CompareArrows as CompareArrowsIcon,
  Category as CategoryIcon,
  Info as InfoIcon,
  Refresh as RefreshIcon
} from '@mui/icons-material';
import { useTheme } from '@mui/material/styles';
import axios from 'axios';
import { 
  BarChart, 
  Bar, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip as RechartsTooltip, 
  Legend, 
  ResponsiveContainer,
  RadarChart,
  PolarGrid,
  PolarAngleAxis,
  PolarRadiusAxis,
  Radar
} from 'recharts';

const CrossCategoryAnalysis = ({ productId }) => {
  const theme = useTheme();
  const [loading, setLoading] = useState(true);
  const [analyzing, setAnalyzing] = useState(false);
  const [error, setError] = useState(null);
  const [analysisData, setAnalysisData] = useState(null);

  useEffect(() => {
    if (productId) {
      fetchAnalysisData();
    }
  }, [productId]);

  const fetchAnalysisData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await axios.get(`/api/analysis/cross-category/${productId}`);
      
      if (response.data.success) {
        setAnalysisData(response.data.data);
      } else {
        setError(response.data.message || '분석 데이터를 불러오는데 실패했습니다.');
      }
    } catch (err) {
      setError('분석 데이터를 불러오는 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const runAnalysis = async () => {
    setAnalyzing(true);
    setError(null);
    
    try {
      const response = await axios.post(`/api/analysis/cross-category/${productId}`);
      
      if (response.data.success) {
        setAnalysisData(response.data.data);
      } else {
        setError(response.data.message || '분석을 실행하는데 실패했습니다.');
      }
    } catch (err) {
      setError('분석 실행 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message));
    } finally {
      setAnalyzing(false);
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

  const getCompetitionLevelText = (level) => {
    if (level <= 3) return '낮음';
    if (level <= 6) return '중간';
    return '높음';
  };

  const getCompetitionLevelColor = (level) => {
    if (level <= 3) return theme.palette.success.main;
    if (level <= 6) return theme.palette.warning.main;
    return theme.palette.error.main;
  };

  const getScoreColor = (score) => {
    if (score >= 70) return theme.palette.success.main;
    if (score >= 50) return theme.palette.warning.main;
    return theme.palette.error.main;
  };

  const getDiffColor = (value) => {
    if (value > 0) return theme.palette.success.main;
    if (value < 0) return theme.palette.error.main;
    return theme.palette.text.secondary;
  };

  const renderCategoryPerformanceChart = () => {
    if (!analysisData || !analysisData.category_analysis) return null;
    
    const { current_category, recommended_categories } = analysisData.category_analysis;
    
    const chartData = [
      {
        name: current_category.name,
        마진율: current_category.performance.average_margin,
        판매량: current_category.performance.average_sales / 10, // 스케일 조정
        경쟁강도: 10 - current_category.performance.competition_level, // 역으로 표시 (낮을수록 좋음)
        점수: current_category.performance.score,
        isCurrent: true
      },
      ...recommended_categories.slice(0, 3).map(category => ({
        name: category.name,
        마진율: category.performance.average_margin,
        판매량: category.performance.average_sales / 10, // 스케일 조정
        경쟁강도: 10 - category.performance.competition_level, // 역으로 표시 (낮을수록 좋음)
        점수: category.performance.score,
        isCurrent: false
      }))
    ];
    
    return (
      <ResponsiveContainer width="100%" height={300}>
        <BarChart
          data={chartData}
          margin={{ top: 20, right: 30, left: 20, bottom: 5 }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="name" />
          <YAxis />
          <RechartsTooltip 
            formatter={(value, name, props) => {
              if (name === '마진율') return [formatPercent(value), name];
              if (name === '경쟁강도') return [10 - value, '경쟁강도 (1-10)'];
              return [value, name];
            }}
          />
          <Legend />
          <Bar 
            dataKey="마진율" 
            fill={theme.palette.primary.main} 
            name="마진율"
            radius={[4, 4, 0, 0]}
          />
          <Bar 
            dataKey="판매량" 
            fill={theme.palette.secondary.main} 
            name="판매량 (x10)"
            radius={[4, 4, 0, 0]}
          />
          <Bar 
            dataKey="경쟁강도" 
            fill={theme.palette.warning.main} 
            name="경쟁강도 (낮을수록 좋음)"
            radius={[4, 4, 0, 0]}
          />
          <Bar 
            dataKey="점수" 
            fill={theme.palette.success.main} 
            name="종합 점수"
            radius={[4, 4, 0, 0]}
          />
        </BarChart>
      </ResponsiveContainer>
    );
  };

  const renderCategoryRadarChart = () => {
    if (!analysisData || !analysisData.category_analysis) return null;
    
    const { current_category, recommended_categories } = analysisData.category_analysis;
    
    // 최고 성과 카테고리
    const bestCategory = recommended_categories.length > 0 ? recommended_categories[0] : null;
    
    if (!bestCategory) return null;
    
    const radarData = [
      { subject: '마진율', A: current_category.performance.average_margin, B: bestCategory.performance.average_margin, fullMark: 50 },
      { subject: '판매량', A: Math.min(current_category.performance.average_sales / 10, 100), B: Math.min(bestCategory.performance.average_sales / 10, 100), fullMark: 100 },
      { subject: '경쟁 낮음', A: 10 - current_category.performance.competition_level, B: 10 - bestCategory.performance.competition_level, fullMark: 10 },
      { subject: '가격 경쟁력', A: current_category.performance.price_ratio * 50, B: bestCategory.performance.price_ratio * 50, fullMark: 100 },
      { subject: '종합 점수', A: current_category.performance.score, B: bestCategory.performance.score, fullMark: 100 },
    ];
    
    return (
      <ResponsiveContainer width="100%" height={300}>
        <RadarChart outerRadius={90} data={radarData}>
          <PolarGrid />
          <PolarAngleAxis dataKey="subject" />
          <PolarRadiusAxis angle={30} domain={[0, 100]} />
          <Radar name={`현재: ${current_category.name}`} dataKey="A" stroke={theme.palette.primary.main} fill={theme.palette.primary.main} fillOpacity={0.6} />
          <Radar name={`추천: ${bestCategory.name}`} dataKey="B" stroke={theme.palette.secondary.main} fill={theme.palette.secondary.main} fillOpacity={0.6} />
          <Legend />
          <RechartsTooltip />
        </RadarChart>
      </ResponsiveContainer>
    );
  };

  const renderComparisonTable = () => {
    if (!analysisData || !analysisData.category_analysis || !analysisData.category_analysis.comparison) return null;
    
    const { comparison } = analysisData.category_analysis;
    
    return (
      <TableContainer component={Paper} sx={{ mt: 2 }}>
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>카테고리</TableCell>
              <TableCell align="right">마진율 차이</TableCell>
              <TableCell align="right">판매량 차이</TableCell>
              <TableCell align="right">경쟁 강도 차이</TableCell>
              <TableCell align="right">개선 가능성</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {comparison.map((item, index) => (
              <TableRow key={index}>
                <TableCell component="th" scope="row">
                  {item.category_name}
                </TableCell>
                <TableCell align="right" sx={{ color: getDiffColor(item.margin_diff) }}>
                  {item.margin_diff > 0 ? '+' : ''}{item.margin_diff.toFixed(1)}%
                </TableCell>
                <TableCell align="right" sx={{ color: getDiffColor(item.sales_diff) }}>
                  {item.sales_diff > 0 ? '+' : ''}{formatNumber(item.sales_diff)}
                </TableCell>
                <TableCell align="right" sx={{ color: getDiffColor(item.competition_diff) }}>
                  {item.competition_diff > 0 ? '+' : ''}{item.competition_diff}
                </TableCell>
                <TableCell align="right">
                  <Box sx={{ display: 'flex', alignItems: 'center' }}>
                    <LinearProgress 
                      variant="determinate" 
                      value={item.potential_improvement} 
                      sx={{ 
                        flexGrow: 1, 
                        mr: 1,
                        height: 10,
                        borderRadius: 5,
                        backgroundColor: theme.palette.grey[200],
                        '& .MuiLinearProgress-bar': {
                          backgroundColor: getScoreColor(item.potential_improvement),
                          borderRadius: 5,
                        }
                      }} 
                    />
                    <Typography variant="body2" color="text.secondary">
                      {item.potential_improvement}%
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

  if (loading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 400 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error) {
    return (
      <Box sx={{ mt: 2, mb: 2 }}>
        <Alert 
          severity="error" 
          action={
            <Button color="inherit" size="small" onClick={runAnalysis} disabled={analyzing}>
              {analyzing ? <CircularProgress size={20} /> : '분석 실행'}
            </Button>
          }
        >
          {error}
        </Alert>
      </Box>
    );
  }

  if (!analysisData) {
    return (
      <Box sx={{ mt: 2, mb: 2 }}>
        <Alert 
          severity="info" 
          action={
            <Button color="inherit" size="small" onClick={runAnalysis} disabled={analyzing}>
              {analyzing ? <CircularProgress size={20} /> : '분석 실행'}
            </Button>
          }
        >
          이 제품에 대한 크로스 카테고리 분석 데이터가 없습니다. 분석을 실행해주세요.
        </Alert>
      </Box>
    );
  }

  const { product, category_analysis } = analysisData;
  const { current_category, recommended_categories } = category_analysis;

  return (
    <Box sx={{ mt: 2 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6" component="h2">
          크로스 카테고리 분석
        </Typography>
        <Button 
          startIcon={<RefreshIcon />} 
          variant="outlined" 
          size="small" 
          onClick={runAnalysis}
          disabled={analyzing}
        >
          {analyzing ? <CircularProgress size={20} /> : '분석 갱신'}
        </Button>
      </Box>

      <Grid container spacing={2}>
        {/* 현재 카테고리 정보 */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardHeader 
              title="현재 카테고리" 
              subheader={current_category.name}
              avatar={<CategoryIcon color="primary" />}
            />
            <Divider />
            <CardContent>
              <Grid container spacing={2}>
                <Grid item xs={6}>
                  <Typography variant="body2" color="text.secondary">평균 마진율</Typography>
                  <Typography variant="h6">{formatPercent(current_category.performance.average_margin)}</Typography>
                </Grid>
                <Grid item xs={6}>
                  <Typography variant="body2" color="text.secondary">평균 판매량</Typography>
                  <Typography variant="h6">{formatNumber(current_category.performance.average_sales)}</Typography>
                </Grid>
                <Grid item xs={6}>
                  <Typography variant="body2" color="text.secondary">경쟁 강도</Typography>
                  <Box sx={{ display: 'flex', alignItems: 'center' }}>
                    <Typography variant="h6" sx={{ color: getCompetitionLevelColor(current_category.performance.competition_level) }}>
                      {current_category.performance.competition_level}/10
                    </Typography>
                    <Typography variant="body2" sx={{ ml: 1, color: getCompetitionLevelColor(current_category.performance.competition_level) }}>
                      ({getCompetitionLevelText(current_category.performance.competition_level)})
                    </Typography>
                  </Box>
                </Grid>
                <Grid item xs={6}>
                  <Typography variant="body2" color="text.secondary">종합 점수</Typography>
                  <Box sx={{ display: 'flex', alignItems: 'center' }}>
                    <Typography variant="h6" sx={{ color: getScoreColor(current_category.performance.score) }}>
                      {current_category.performance.score}/100
                    </Typography>
                  </Box>
                </Grid>
              </Grid>
            </CardContent>
          </Card>
        </Grid>

        {/* 최고 추천 카테고리 */}
        {recommended_categories.length > 0 && (
          <Grid item xs={12} md={6}>
            <Card>
              <CardHeader 
                title="최고 추천 카테고리" 
                subheader={recommended_categories[0].name}
                avatar={<TrendingUpIcon color="secondary" />}
              />
              <Divider />
              <CardContent>
                <Grid container spacing={2}>
                  <Grid item xs={6}>
                    <Typography variant="body2" color="text.secondary">평균 마진율</Typography>
                    <Typography variant="h6">
                      {formatPercent(recommended_categories[0].performance.average_margin)}
                      {recommended_categories[0].performance.average_margin > current_category.performance.average_margin && (
                        <Chip 
                          size="small" 
                          label={`+${(recommended_categories[0].performance.average_margin - current_category.performance.average_margin).toFixed(1)}%`} 
                          color="success" 
                          sx={{ ml: 1 }} 
                        />
                      )}
                    </Typography>
                  </Grid>
                  <Grid item xs={6}>
                    <Typography variant="body2" color="text.secondary">평균 판매량</Typography>
                    <Typography variant="h6">
                      {formatNumber(recommended_categories[0].performance.average_sales)}
                      {recommended_categories[0].performance.average_sales > current_category.performance.average_sales && (
                        <Chip 
                          size="small" 
                          label={`+${formatNumber(recommended_categories[0].performance.average_sales - current_category.performance.average_sales)}`} 
                          color="success" 
                          sx={{ ml: 1 }} 
                        />
                      )}
                    </Typography>
                  </Grid>
                  <Grid item xs={6}>
                    <Typography variant="body2" color="text.secondary">경쟁 강도</Typography>
                    <Box sx={{ display: 'flex', alignItems: 'center' }}>
                      <Typography variant="h6" sx={{ color: getCompetitionLevelColor(recommended_categories[0].performance.competition_level) }}>
                        {recommended_categories[0].performance.competition_level}/10
                      </Typography>
                      <Typography variant="body2" sx={{ ml: 1, color: getCompetitionLevelColor(recommended_categories[0].performance.competition_level) }}>
                        ({getCompetitionLevelText(recommended_categories[0].performance.competition_level)})
                      </Typography>
                      {recommended_categories[0].performance.competition_level < current_category.performance.competition_level && (
                        <Chip 
                          size="small" 
                          label={`-${current_category.performance.competition_level - recommended_categories[0].performance.competition_level}`} 
                          color="success" 
                          sx={{ ml: 1 }} 
                        />
                      )}
                    </Box>
                  </Grid>
                  <Grid item xs={6}>
                    <Typography variant="body2" color="text.secondary">종합 점수</Typography>
                    <Box sx={{ display: 'flex', alignItems: 'center' }}>
                      <Typography variant="h6" sx={{ color: getScoreColor(recommended_categories[0].performance.score) }}>
                        {recommended_categories[0].performance.score}/100
                      </Typography>
                      {recommended_categories[0].performance.score > current_category.performance.score && (
                        <Chip 
                          size="small" 
                          label={`+${recommended_categories[0].performance.score - current_category.performance.score}`} 
                          color="success" 
                          sx={{ ml: 1 }} 
                        />
                      )}
                    </Box>
                  </Grid>
                </Grid>
              </CardContent>
            </Card>
          </Grid>
        )}

        {/* 카테고리 성과 차트 */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardHeader title="카테고리 성과 비교" />
            <Divider />
            <CardContent>
              {renderCategoryPerformanceChart()}
            </CardContent>
          </Card>
        </Grid>

        {/* 레이더 차트 */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardHeader title="카테고리 성과 레이더" />
            <Divider />
            <CardContent>
              {renderCategoryRadarChart()}
            </CardContent>
          </Card>
        </Grid>

        {/* 카테고리 비교 테이블 */}
        <Grid item xs={12}>
          <Card>
            <CardHeader 
              title="카테고리 비교 분석" 
              subheader="현재 카테고리 대비 개선 가능성"
              action={
                <Tooltip title="개선 가능성은 마진율, 판매량, 경쟁 강도 차이를 종합적으로 고려하여 계산됩니다.">
                  <IconButton>
                    <InfoIcon />
                  </IconButton>
                </Tooltip>
              }
            />
            <Divider />
            <CardContent>
              {renderComparisonTable()}
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
};

export default CrossCategoryAnalysis; 