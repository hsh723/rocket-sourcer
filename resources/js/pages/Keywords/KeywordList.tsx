import { useState } from 'react';
import {
  Grid,
  Box,
  Typography,
  TextField,
  InputAdornment,
  IconButton,
} from '@mui/material';
import SearchIcon from '@mui/icons-material/Search';
import FilterListIcon from '@mui/icons-material/FilterList';
import { Card } from '@/components/UI/Card';
import { Table } from '@/components/UI/Table';
import { Button } from '@/components/UI/Button';
import { Loader } from '@/components/UI/Loader';
import { useKeywords } from '@/hooks/useKeywords';
import { formatNumber } from '@/utils/format';

export default function KeywordList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [filters, setFilters] = useState({
    competition: 'all',
    trend: 'all',
  });

  const { data, isLoading, error } = useKeywords({ searchTerm, ...filters });

  const columns = [
    { id: 'keyword', label: '키워드' },
    {
      id: 'searchVolume',
      label: '검색량',
      render: (value: number) => formatNumber(value),
    },
    {
      id: 'competition',
      label: '경쟁도',
      render: (value: number) => `${value.toFixed(1)}%`,
    },
    {
      id: 'trend',
      label: '트렌드',
      render: (value: 'up' | 'down' | 'stable') => (
        <Button
          variant="outlined"
          size="small"
          color={value === 'up' ? 'success' : value === 'down' ? 'error' : 'info'}
        >
          {value === 'up' ? '상승' : value === 'down' ? '하락' : '안정'}
        </Button>
      ),
    },
    {
      id: 'averagePrice',
      label: '평균가격',
      render: (value: number) => formatNumber(value, 'currency'),
    },
    {
      id: 'estimatedSales',
      label: '예상매출',
      render: (value: number) => formatNumber(value, 'currency'),
    },
  ];

  if (isLoading) return <Loader />;
  if (error) throw error;

  return (
    <Box>
      <Grid container spacing={3}>
        <Grid item xs={12}>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 3 }}>
            <Typography variant="h4">키워드 분석</Typography>
            <Button
              variant="contained"
              color="primary"
              startIcon={<SearchIcon />}
            >
              새 키워드 분석
            </Button>
          </Box>
        </Grid>
        <Grid item xs={12}>
          <Card>
            <Box sx={{ display: 'flex', gap: 2, mb: 3 }}>
              <TextField
                fullWidth
                placeholder="키워드 검색..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                InputProps={{
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchIcon />
                    </InputAdornment>
                  ),
                }}
              />
              <IconButton>
                <FilterListIcon />
              </IconButton>
            </Box>
            <Table
              columns={columns}
              data={data}
              onRowClick={(row) => {
                // Handle row click - navigate to detail page
              }}
            />
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
} 