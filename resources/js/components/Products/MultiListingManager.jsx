import React, { useState, useEffect } from 'react';
import { 
  Box, 
  Typography, 
  Paper, 
  Tabs, 
  Tab, 
  Button, 
  TextField, 
  Chip,
  Table, 
  TableBody, 
  TableCell, 
  TableContainer, 
  TableHead, 
  TableRow,
  CircularProgress,
  Snackbar,
  Alert,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  IconButton,
  Tooltip,
  Card,
  CardContent,
  CardHeader,
  Divider,
  Grid,
  Slider,
  InputAdornment,
  FormControlLabel,
  Switch
} from '@mui/material';
import { 
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Save as SaveIcon,
  ContentCopy as CopyIcon,
  Visibility as VisibilityIcon,
  VisibilityOff as VisibilityOffIcon,
  Refresh as RefreshIcon,
  Category as CategoryIcon,
  ShoppingCart as ShoppingCartIcon,
  LocalOffer as LocalOfferIcon,
  MonetizationOn as MonetizationOnIcon
} from '@mui/icons-material';
import axios from 'axios';

const MultiListingManager = ({ productId }) => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [analysisData, setAnalysisData] = useState(null);
  const [activeTab, setActiveTab] = useState(0);
  const [editMode, setEditMode] = useState({});
  const [editData, setEditData] = useState({});
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' });
  const [keywordDialog, setKeywordDialog] = useState({ open: false, categoryId: null });
  const [priceDialog, setPriceDialog] = useState({ open: false, categoryId: null });
  const [selectedKeywords, setSelectedKeywords] = useState({});
  const [customKeyword, setCustomKeyword] = useState('');

  useEffect(() => {
    if (productId) {
      fetchListingData();
    }
  }, [productId]);

  const fetchListingData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await axios.get(`/api/analysis/cross-category/${productId}/listings`);
      
      if (response.data.success) {
        setAnalysisData(response.data.data);
        
        // 초기 선택 키워드 설정
        const initialKeywords = {};
        response.data.data.listing_recommendations.forEach(listing => {
          initialKeywords[listing.category_id] = listing.keywords || [];
        });
        setSelectedKeywords(initialKeywords);
      } else {
        setError(response.data.message || '리스팅 데이터를 불러오는데 실패했습니다.');
      }
    } catch (err) {
      setError('리스팅 데이터를 불러오는 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const handleTabChange = (event, newValue) => {
    setActiveTab(newValue);
  };

  const handleEditClick = (categoryId) => {
    const listing = analysisData.listing_recommendations.find(item => item.category_id === categoryId);
    
    setEditMode(prev => ({ ...prev, [categoryId]: true }));
    setEditData(prev => ({ 
      ...prev, 
      [categoryId]: { 
        title: listing.title,
        price: listing.price,
        keywords: [...listing.keywords]
      } 
    }));
  };

  const handleCancelEdit = (categoryId) => {
    setEditMode(prev => ({ ...prev, [categoryId]: false }));
    setEditData(prev => ({ ...prev, [categoryId]: null }));
  };

  const handleInputChange = (categoryId, field, value) => {
    setEditData(prev => ({
      ...prev,
      [categoryId]: {
        ...prev[categoryId],
        [field]: value
      }
    }));
  };

  const handleSaveChanges = async (categoryId) => {
    try {
      const response = await axios.put(`/api/analysis/cross-category/${productId}/listings/${categoryId}`, editData[categoryId]);
      
      if (response.data.success) {
        // 데이터 업데이트
        setAnalysisData(prev => ({
          ...prev,
          listing_recommendations: prev.listing_recommendations.map(listing => 
            listing.category_id === categoryId 
              ? { ...listing, ...editData[categoryId] } 
              : listing
          )
        }));
        
        // 편집 모드 종료
        setEditMode(prev => ({ ...prev, [categoryId]: false }));
        
        // 성공 메시지 표시
        setSnackbar({
          open: true,
          message: '리스팅 정보가 성공적으로 저장되었습니다.',
          severity: 'success'
        });
      } else {
        setSnackbar({
          open: true,
          message: response.data.message || '리스팅 정보 저장에 실패했습니다.',
          severity: 'error'
        });
      }
    } catch (err) {
      setSnackbar({
        open: true,
        message: '리스팅 정보 저장 중 오류가 발생했습니다: ' + (err.response?.data?.message || err.message),
        severity: 'error'
      });
    }
  };

  const handleCopyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(
      () => {
        setSnackbar({
          open: true,
          message: '클립보드에 복사되었습니다.',
          severity: 'success'
        });
      },
      () => {
        setSnackbar({
          open: true,
          message: '클립보드 복사에 실패했습니다.',
          severity: 'error'
        });
      }
    );
  };

  const handleKeywordDialogOpen = (categoryId) => {
    setKeywordDialog({ open: true, categoryId });
  };

  const handleKeywordDialogClose = () => {
    setKeywordDialog({ open: false, categoryId: null });
    setCustomKeyword('');
  };

  const handleKeywordSelect = (keyword) => {
    const categoryId = keywordDialog.categoryId;
    const currentKeywords = editData[categoryId]?.keywords || [];
    
    if (currentKeywords.includes(keyword)) {
      // 이미 선택된 키워드라면 제거
      handleInputChange(categoryId, 'keywords', currentKeywords.filter(k => k !== keyword));
    } else {
      // 새 키워드 추가
      handleInputChange(categoryId, 'keywords', [...currentKeywords, keyword]);
    }
  };

  const handleAddCustomKeyword = () => {
    if (!customKeyword.trim()) return;
    
    const categoryId = keywordDialog.categoryId;
    const currentKeywords = editData[categoryId]?.keywords || [];
    
    if (!currentKeywords.includes(customKeyword)) {
      handleInputChange(categoryId, 'keywords', [...currentKeywords, customKeyword]);
    }
    
    setCustomKeyword('');
  };

  const handlePriceDialogOpen = (categoryId) => {
    setPriceDialog({ open: true, categoryId });
  };

  const handlePriceDialogClose = () => {
    setPriceDialog({ open: false, categoryId: null });
  };

  const handlePriceChange = (value) => {
    const categoryId = priceDialog.categoryId;
    handleInputChange(categoryId, 'price', value);
  };

  const handleSnackbarClose = () => {
    setSnackbar(prev => ({ ...prev, open: false }));
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

  const renderKeywordDialog = () => {
    if (!keywordDialog.open || !keywordDialog.categoryId) return null;
    
    const categoryId = keywordDialog.categoryId;
    const listing = analysisData.listing_recommendations.find(item => item.category_id === categoryId);
    const selectedKeywordList = editData[categoryId]?.keywords || [];
    
    // 추천 키워드 목록 (선택된 키워드 제외)
    const recommendedKeywords = analysisData.available_keywords?.[categoryId] || [];
    
    return (
      <Dialog 
        open={keywordDialog.open} 
        onClose={handleKeywordDialogClose}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>
          <Box sx={{ display: 'flex', alignItems: 'center' }}>
            <LocalOfferIcon sx={{ mr: 1 }} />
            {listing.category_name} 카테고리 키워드 관리
          </Box>
        </DialogTitle>
        <DialogContent>
          <Box sx={{ mb: 2 }}>
            <Typography variant="subtitle1" gutterBottom>선택된 키워드 ({selectedKeywordList.length})</Typography>
            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
              {selectedKeywordList.map((keyword, index) => (
                <Chip 
                  key={index} 
                  label={keyword} 
                  color="primary" 
                  onDelete={() => handleKeywordSelect(keyword)}
                />
              ))}
              {selectedKeywordList.length === 0 && (
                <Typography variant="body2" color="text.secondary">선택된 키워드가 없습니다.</Typography>
              )}
            </Box>
          </Box>
          
          <Divider sx={{ my: 2 }} />
          
          <Box sx={{ mb: 2 }}>
            <Typography variant="subtitle1" gutterBottom>추천 키워드</Typography>
            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
              {recommendedKeywords.map((keyword, index) => (
                <Chip 
                  key={index} 
                  label={keyword} 
                  color={selectedKeywordList.includes(keyword) ? "primary" : "default"}
                  onClick={() => handleKeywordSelect(keyword)}
                  clickable
                />
              ))}
              {recommendedKeywords.length === 0 && (
                <Typography variant="body2" color="text.secondary">추천 키워드가 없습니다.</Typography>
              )}
            </Box>
          </Box>
          
          <Divider sx={{ my: 2 }} />
          
          <Box sx={{ display: 'flex', alignItems: 'center', mt: 2 }}>
            <TextField
              label="커스텀 키워드 추가"
              value={customKeyword}
              onChange={(e) => setCustomKeyword(e.target.value)}
              variant="outlined"
              size="small"
              fullWidth
              onKeyPress={(e) => {
                if (e.key === 'Enter') {
                  handleAddCustomKeyword();
                }
              }}
            />
            <Button 
              variant="contained" 
              onClick={handleAddCustomKeyword}
              disabled={!customKeyword.trim()}
              sx={{ ml: 1 }}
            >
              추가
            </Button>
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleKeywordDialogClose}>닫기</Button>
        </DialogActions>
      </Dialog>
    );
  };

  const renderPriceDialog = () => {
    if (!priceDialog.open || !priceDialog.categoryId) return null;
    
    const categoryId = priceDialog.categoryId;
    const listing = analysisData.listing_recommendations.find(item => item.category_id === categoryId);
    const priceStrategy = listing.price_strategy;
    const currentPrice = editData[categoryId]?.price || listing.price;
    
    return (
      <Dialog 
        open={priceDialog.open} 
        onClose={handlePriceDialogClose}
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle>
          <Box sx={{ display: 'flex', alignItems: 'center' }}>
            <MonetizationOnIcon sx={{ mr: 1 }} />
            {listing.category_name} 카테고리 가격 설정
          </Box>
        </DialogTitle>
        <DialogContent>
          <Box sx={{ mb: 3 }}>
            <Typography variant="subtitle1" gutterBottom>가격 전략</Typography>
            <Typography variant="body2" color="text.secondary" paragraph>
              {priceStrategy.strategy_description}
            </Typography>
            
            <Grid container spacing={2} sx={{ mt: 1 }}>
              <Grid item xs={6}>
                <Typography variant="body2" color="text.secondary">현재 가격</Typography>
                <Typography variant="h6">{formatCurrency(priceStrategy.current_price)}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="text.secondary">카테고리 평균 가격</Typography>
                <Typography variant="h6">{formatCurrency(priceStrategy.average_price)}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="text.secondary">추천 가격</Typography>
                <Typography variant="h6" color="primary">{formatCurrency(priceStrategy.recommended_price)}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="text.secondary">가격 비율</Typography>
                <Typography variant="h6">{priceStrategy.price_ratio}x</Typography>
              </Grid>
            </Grid>
          </Box>
          
          <Divider sx={{ my: 2 }} />
          
          <Box sx={{ mt: 3 }}>
            <Typography variant="subtitle1" gutterBottom>가격 설정</Typography>
            <Box sx={{ px: 2 }}>
              <Slider
                value={currentPrice}
                onChange={(e, newValue) => handlePriceChange(newValue)}
                min={priceStrategy.min_price}
                max={priceStrategy.max_price}
                step={100}
                marks={[
                  { value: priceStrategy.min_price, label: formatCurrency(priceStrategy.min_price) },
                  { value: priceStrategy.recommended_price, label: '추천' },
                  { value: priceStrategy.max_price, label: formatCurrency(priceStrategy.max_price) }
                ]}
                valueLabelDisplay="on"
                valueLabelFormat={(value) => formatCurrency(value)}
              />
            </Box>
            
            <TextField
              label="가격"
              value={currentPrice}
              onChange={(e) => {
                const value = parseInt(e.target.value.replace(/[^0-9]/g, ''), 10);
                if (!isNaN(value)) {
                  handlePriceChange(value);
                }
              }}
              variant="outlined"
              fullWidth
              margin="normal"
              InputProps={{
                startAdornment: <InputAdornment position="start">₩</InputAdornment>,
              }}
            />
            
            <FormControlLabel
              control={
                <Switch 
                  checked={currentPrice === priceStrategy.recommended_price}
                  onChange={(e) => {
                    if (e.target.checked) {
                      handlePriceChange(priceStrategy.recommended_price);
                    }
                  }}
                  color="primary"
                />
              }
              label="추천 가격 사용"
            />
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={handlePriceDialogClose}>닫기</Button>
          <Button 
            variant="contained" 
            onClick={handlePriceDialogClose}
            color="primary"
          >
            적용
          </Button>
        </DialogActions>
      </Dialog>
    );
  };

  const renderListingTabs = () => {
    if (!analysisData || !analysisData.listing_recommendations) return null;
    
    return (
      <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
        <Tabs 
          value={activeTab} 
          onChange={handleTabChange} 
          variant="scrollable"
          scrollButtons="auto"
        >
          {analysisData.listing_recommendations.map((listing, index) => (
            <Tab 
              key={index} 
              label={
                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                  {listing.is_current ? <CategoryIcon fontSize="small" sx={{ mr: 0.5 }} /> : null}
                  {listing.category_name}
                </Box>
              } 
              id={`listing-tab-${index}`}
              aria-controls={`listing-tabpanel-${index}`}
            />
          ))}
        </Tabs>
      </Box>
    );
  };

  const renderListingPanel = (index) => {
    if (!analysisData || !analysisData.listing_recommendations) return null;
    
    const listing = analysisData.listing_recommendations[index];
    const isEditing = editMode[listing.category_id] || false;
    const editingData = editData[listing.category_id] || {};
    
    return (
      <Box
        role="tabpanel"
        hidden={activeTab !== index}
        id={`listing-tabpanel-${index}`}
        aria-labelledby={`listing-tab-${index}`}
        sx={{ py: 2 }}
      >
        {activeTab === index && (
          <Box>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
              <Typography variant="h6" component="h2">
                {listing.is_current ? '현재 카테고리' : '추천 카테고리'}: {listing.category_name}
              </Typography>
              {isEditing ? (
                <Box>
                  <Button 
                    variant="outlined" 
                    color="secondary" 
                    onClick={() => handleCancelEdit(listing.category_id)}
                    sx={{ mr: 1 }}
                  >
                    취소
                  </Button>
                  <Button 
                    variant="contained" 
                    color="primary" 
                    startIcon={<SaveIcon />}
                    onClick={() => handleSaveChanges(listing.category_id)}
                  >
                    저장
                  </Button>
                </Box>
              ) : (
                <Button 
                  variant="outlined" 
                  startIcon={<EditIcon />}
                  onClick={() => handleEditClick(listing.category_id)}
                >
                  편집
                </Button>
              )}
            </Box>
            
            <Grid container spacing={3}>
              {/* 상품명 섹션 */}
              <Grid item xs={12}>
                <Card>
                  <CardHeader 
                    title="상품명" 
                    action={
                      !isEditing && (
                        <IconButton onClick={() => handleCopyToClipboard(listing.title)}>
                          <CopyIcon />
                        </IconButton>
                      )
                    }
                  />
                  <Divider />
                  <CardContent>
                    {isEditing ? (
                      <TextField
                        fullWidth
                        multiline
                        rows={2}
                        variant="outlined"
                        value={editingData.title || ''}
                        onChange={(e) => handleInputChange(listing.category_id, 'title', e.target.value)}
                      />
                    ) : (
                      <Typography variant="body1">{listing.title}</Typography>
                    )}
                  </CardContent>
                </Card>
              </Grid>
              
              {/* 키워드 섹션 */}
              <Grid item xs={12} md={6}>
                <Card>
                  <CardHeader 
                    title="키워드" 
                    action={
                      isEditing && (
                        <Button 
                          size="small" 
                          startIcon={<EditIcon />}
                          onClick={() => handleKeywordDialogOpen(listing.category_id)}
                        >
                          키워드 관리
                        </Button>
                      )
                    }
                  />
                  <Divider />
                  <CardContent>
                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                      {(isEditing ? editingData.keywords : listing.keywords)?.map((keyword, i) => (
                        <Chip 
                          key={i} 
                          label={keyword} 
                          color={isEditing ? "primary" : "default"}
                          onDelete={isEditing ? () => {
                            const updatedKeywords = [...editingData.keywords];
                            updatedKeywords.splice(i, 1);
                            handleInputChange(listing.category_id, 'keywords', updatedKeywords);
                          } : undefined}
                        />
                      ))}
                      {(!isEditing && listing.keywords?.length === 0) && (
                        <Typography variant="body2" color="text.secondary">키워드가 없습니다.</Typography>
                      )}
                    </Box>
                  </CardContent>
                </Card>
              </Grid>
              
              {/* 가격 전략 섹션 */}
              <Grid item xs={12} md={6}>
                <Card>
                  <CardHeader 
                    title="가격 전략" 
                    action={
                      isEditing && (
                        <Button 
                          size="small" 
                          startIcon={<EditIcon />}
                          onClick={() => handlePriceDialogOpen(listing.category_id)}
                        >
                          가격 설정
                        </Button>
                      )
                    }
                  />
                  <Divider />
                  <CardContent>
                    <Grid container spacing={2}>
                      <Grid item xs={6}>
                        <Typography variant="body2" color="text.secondary">판매 가격</Typography>
                        <Typography variant="h6" color={isEditing ? "primary" : "textPrimary"}>
                          {formatCurrency(isEditing ? editingData.price : listing.price)}
                        </Typography>
                      </Grid>
                      <Grid item xs={6}>
                        <Typography variant="body2" color="text.secondary">카테고리 평균 가격</Typography>
                        <Typography variant="body1">
                          {formatCurrency(listing.price_strategy.average_price)}
                        </Typography>
                      </Grid>
                      <Grid item xs={12}>
                        <Typography variant="body2" color="text.secondary">가격 전략</Typography>
                        <Typography variant="body1">
                          {listing.price_strategy.strategy_description}
                        </Typography>
                      </Grid>
                    </Grid>
                  </CardContent>
                </Card>
              </Grid>
            </Grid>
          </Box>
        )}
      </Box>
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
            <Button color="inherit" size="small" onClick={fetchListingData}>
              다시 시도
            </Button>
          }
        >
          {error}
        </Alert>
      </Box>
    );
  }

  if (!analysisData || !analysisData.listing_recommendations) {
    return (
      <Box sx={{ mt: 2, mb: 2 }}>
        <Alert 
          severity="info" 
          action={
            <Button color="inherit" size="small" onClick={fetchListingData}>
              다시 시도
            </Button>
          }
        >
          이 제품에 대한 멀티 리스팅 데이터가 없습니다. 먼저 크로스 카테고리 분석을 실행해주세요.
        </Alert>
      </Box>
    );
  }

  return (
    <Box sx={{ mt: 2 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6" component="h2">
          멀티 리스팅 관리
        </Typography>
        <Button 
          startIcon={<RefreshIcon />} 
          variant="outlined" 
          size="small" 
          onClick={fetchListingData}
        >
          새로고침
        </Button>
      </Box>
      
      {renderListingTabs()}
      
      {analysisData.listing_recommendations.map((listing, index) => (
        renderListingPanel(index)
      ))}
      
      {renderKeywordDialog()}
      {renderPriceDialog()}
      
      <Snackbar 
        open={snackbar.open} 
        autoHideDuration={6000} 
        onClose={handleSnackbarClose}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
      >
        <Alert onClose={handleSnackbarClose} severity={snackbar.severity} sx={{ width: '100%' }}>
          {snackbar.message}
        </Alert>
      </Snackbar>
    </Box>
  );
};

export default MultiListingManager; 