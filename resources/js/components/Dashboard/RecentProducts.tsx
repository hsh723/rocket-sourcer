import { Card } from '@/components/UI/Card';
import { Table } from '@/components/UI/Table';
import { Avatar } from '@mui/material';
import { formatNumber } from '@/utils/format';

interface Product {
  id: number;
  name: string;
  image: string;
  price: number;
  sales: number;
  rating: number;
}

interface RecentProductsProps {
  products: Product[];
}

export function RecentProducts({ products }: RecentProductsProps) {
  const columns = [
    {
      id: 'name',
      label: '제품명',
      render: (value: string, row: Product) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: 2 }}>
          <Avatar src={row.image} sx={{ width: 32, height: 32 }} />
          <span>{value}</span>
        </div>
      ),
    },
    {
      id: 'price',
      label: '가격',
      render: (value: number) => formatNumber(value, 'currency'),
    },
    {
      id: 'sales',
      label: '판매량',
      render: (value: number) => formatNumber(value),
    },
    {
      id: 'rating',
      label: '평점',
      render: (value: number) => value.toFixed(1),
    },
  ];

  return (
    <Card title="최근 제품">
      <Table
        columns={columns}
        data={products}
      />
    </Card>
  );
} 