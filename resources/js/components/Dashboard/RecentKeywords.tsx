import { Card } from '@/components/UI/Card';
import { Table } from '@/components/UI/Table';
import { Chip } from '@mui/material';
import { formatNumber } from '@/utils/format';

interface Keyword {
  id: number;
  keyword: string;
  searchVolume: number;
  competition: number;
  trend: 'up' | 'down' | 'stable';
}

interface RecentKeywordsProps {
  keywords: Keyword[];
}

export function RecentKeywords({ keywords }: RecentKeywordsProps) {
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
        <Chip
          label={value === 'up' ? '상승' : value === 'down' ? '하락' : '안정'}
          color={value === 'up' ? 'success' : value === 'down' ? 'error' : 'default'}
          size="small"
        />
      ),
    },
  ];

  return (
    <Card title="최근 키워드">
      <Table
        columns={columns}
        data={keywords}
      />
    </Card>
  );
} 