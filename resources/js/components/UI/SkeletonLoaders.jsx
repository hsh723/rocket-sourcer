import React from 'react';
import { Box, Card, CardContent, CardHeader, Grid, Skeleton, Typography } from '@mui/material';
import { useTheme } from './ThemeProvider';

/**
 * 텍스트 스켈레톤 로더
 * @param {Object} props - 컴포넌트 속성
 * @param {number} props.lines - 텍스트 라인 수 (기본값: 3)
 * @param {string} props.width - 너비 (기본값: '100%')
 * @param {Object} props.sx - 추가 스타일
 */
export const TextSkeleton = ({ lines = 3, width = '100%', sx = {} }) => {
  return (
    <Box sx={{ width, ...sx }}>
      {Array.from(new Array(lines)).map((_, index) => (
        <Skeleton
          key={index}
          variant="text"
          width={index === lines - 1 && lines > 1 ? '80%' : '100%'}
          sx={{ 
            height: 20, 
            marginBottom: 1,
            borderRadius: 1
          }}
        />
      ))}
    </Box>
  );
};

/**
 * 카드 스켈레톤 로더
 * @param {Object} props - 컴포넌트 속성
 * @param {boolean} props.hasHeader - 헤더 포함 여부 (기본값: true)
 * @param {number} props.contentLines - 컨텐츠 라인 수 (기본값: 3)
 * @param {Object} props.sx - 추가 스타일
 */
export const CardSkeleton = ({ hasHeader = true, contentLines = 3, sx = {} }) => {
  return (
    <Card sx={{ width: '100%', ...sx }}>
      {hasHeader && (
        <CardHeader
          title={<Skeleton variant="text" width="60%" height={30} />}
          subheader={<Skeleton variant="text" width="40%" height={20} />}
        />
      )}
      <CardContent>
        <TextSkeleton lines={contentLines} />
      </CardContent>
    </Card>
  );
};

/**
 * 테이블 스켈레톤 로더
 * @param {Object} props - 컴포넌트 속성
 * @param {number} props.rows - 행 수 (기본값: 5)
 * @param {number} props.columns - 열 수 (기본값: 4)
 * @param {Object} props.sx - 추가 스타일
 */
export const TableSkeleton = ({ rows = 5, columns = 4, sx = {} }) => {
  const { theme } = useTheme();
  
  return (
    <Box sx={{ width: '100%', ...sx }}>
      {/* 테이블 헤더 */}
      <Box 
        sx={{ 
          display: 'flex', 
          p: 1.5, 
          borderBottom: `1px solid ${theme.palette.divider}`,
          backgroundColor: theme.palette.mode === 'light' ? '#f5f5f5' : '#2d2d2d',
          borderTopLeftRadius: theme.shape.borderRadius,
          borderTopRightRadius: theme.shape.borderRadius,
        }}
      >
        {Array.from(new Array(columns)).map((_, index) => (
          <Box 
            key={`header-${index}`} 
            sx={{ 
              flex: 1, 
              px: 1 
            }}
          >
            <Skeleton variant="text" width="80%" height={24} />
          </Box>
        ))}
      </Box>
      
      {/* 테이블 행 */}
      {Array.from(new Array(rows)).map((_, rowIndex) => (
        <Box 
          key={`row-${rowIndex}`} 
          sx={{ 
            display: 'flex', 
            p: 1.5, 
            borderBottom: `1px solid ${theme.palette.divider}`,
            '&:last-child': {
              borderBottomLeftRadius: theme.shape.borderRadius,
              borderBottomRightRadius: theme.shape.borderRadius,
            }
          }}
        >
          {Array.from(new Array(columns)).map((_, colIndex) => (
            <Box 
              key={`cell-${rowIndex}-${colIndex}`} 
              sx={{ 
                flex: 1, 
                px: 1 
              }}
            >
              <Skeleton 
                variant="text" 
                width={colIndex === 0 ? '60%' : '80%'} 
                height={20} 
              />
            </Box>
          ))}
        </Box>
      ))}
    </Box>
  );
};

/**
 * 차트 스켈레톤 로더
 * @param {Object} props - 컴포넌트 속성
 * @param {number} props.height - 높이 (기본값: 300)
 * @param {Object} props.sx - 추가 스타일
 */
export const ChartSkeleton = ({ height = 300, sx = {} }) => {
  return (
    <Box sx={{ width: '100%', ...sx }}>
      <Skeleton 
        variant="rectangular" 
        width="100%" 
        height={height} 
        sx={{ borderRadius: 1 }}
      />
      <Box sx={{ display: 'flex', justifyContent: 'center', mt: 2 }}>
        {Array.from(new Array(5)).map((_, index) => (
          <Skeleton 
            key={index} 
            variant="rectangular" 
            width={60} 
            height={24} 
            sx={{ mx: 1, borderRadius: 1 }}
          />
        ))}
      </Box>
    </Box>
  );
};

/**
 * 프로필 스켈레톤 로더
 * @param {Object} props - 컴포넌트 속성
 * @param {Object} props.sx - 추가 스타일
 */
export const ProfileSkeleton = ({ sx = {} }) => {
  return (
    <Box sx={{ display: 'flex', alignItems: 'center', ...sx }}>
      <Skeleton 
        variant="circular" 
        width={50} 
        height={50} 
        sx={{ mr: 2 }}
      />
      <Box>
        <Skeleton variant="text" width={120} height={24} />
        <Skeleton variant="text" width={180} height={18} />
      </Box>
    </Box>
  );
};

/**
 * 대시보드 스켈레톤 로더
 * @param {Object} props - 컴포넌트 속성
 * @param {Object} props.sx - 추가 스타일
 */
export const DashboardSkeleton = ({ sx = {} }) => {
  return (
    <Box sx={{ width: '100%', ...sx }}>
      {/* 상단 카드 섹션 */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        {Array.from(new Array(4)).map((_, index) => (
          <Grid item xs={12} sm={6} md={3} key={`card-${index}`}>
            <Card>
              <CardContent>
                <Typography variant="subtitle2" component="div">
                  <Skeleton width="60%" />
                </Typography>
                <Typography variant="h5" component="div" sx={{ mt: 1, mb: 1 }}>
                  <Skeleton width="40%" />
                </Typography>
                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                  <Skeleton width="30%" />
                  <Skeleton width="20%" sx={{ ml: 1 }} />
                </Box>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      {/* 차트 섹션 */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={12} md={8}>
          <ChartSkeleton height={350} />
        </Grid>
        <Grid item xs={12} md={4}>
          <CardSkeleton contentLines={6} sx={{ height: '100%' }} />
        </Grid>
      </Grid>

      {/* 테이블 섹션 */}
      <TableSkeleton rows={5} columns={5} />
    </Box>
  );
};

/**
 * 제품 목록 스켈레톤 로더
 * @param {Object} props - 컴포넌트 속성
 * @param {number} props.items - 아이템 수 (기본값: 6)
 * @param {Object} props.sx - 추가 스타일
 */
export const ProductListSkeleton = ({ items = 6, sx = {} }) => {
  return (
    <Grid container spacing={3} sx={{ ...sx }}>
      {Array.from(new Array(items)).map((_, index) => (
        <Grid item xs={12} sm={6} md={4} key={`product-${index}`}>
          <Card>
            <Skeleton 
              variant="rectangular" 
              width="100%" 
              height={200} 
              sx={{ borderTopLeftRadius: 12, borderTopRightRadius: 12 }}
            />
            <CardContent>
              <Typography variant="h6" component="div">
                <Skeleton width="70%" />
              </Typography>
              <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                <Skeleton width="40%" />
              </Typography>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', mt: 2 }}>
                <Skeleton width="30%" height={30} />
                <Skeleton width="20%" height={30} />
              </Box>
            </CardContent>
          </Card>
        </Grid>
      ))}
    </Grid>
  );
};

/**
 * 폼 스켈레톤 로더
 * @param {Object} props - 컴포넌트 속성
 * @param {number} props.fields - 필드 수 (기본값: 4)
 * @param {boolean} props.hasButton - 버튼 포함 여부 (기본값: true)
 * @param {Object} props.sx - 추가 스타일
 */
export const FormSkeleton = ({ fields = 4, hasButton = true, sx = {} }) => {
  return (
    <Box sx={{ width: '100%', ...sx }}>
      {Array.from(new Array(fields)).map((_, index) => (
        <Box key={`field-${index}`} sx={{ mb: 3 }}>
          <Skeleton variant="text" width="30%" height={20} sx={{ mb: 1 }} />
          <Skeleton variant="rectangular" width="100%" height={56} sx={{ borderRadius: 1 }} />
        </Box>
      ))}
      
      {hasButton && (
        <Box sx={{ display: 'flex', justifyContent: 'flex-end', mt: 2 }}>
          <Skeleton variant="rectangular" width={120} height={40} sx={{ borderRadius: 1 }} />
        </Box>
      )}
    </Box>
  );
};

export default {
  TextSkeleton,
  CardSkeleton,
  TableSkeleton,
  ChartSkeleton,
  ProfileSkeleton,
  DashboardSkeleton,
  ProductListSkeleton,
  FormSkeleton
}; 