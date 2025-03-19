import React from 'react';
import {
  Box,
  Paper,
  Typography,
  ToggleButton,
  ToggleButtonGroup
} from '@mui/material';
import {
  PieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  Tooltip,
  Legend
} from 'recharts';

interface ProfitBreakdownProps {
  data: {
    costs: {
      name: string;
      value: number;
      color: string;
    }[];
    revenues: {
      name: string;
      value: number;
      color: string;
    }[];
  };
}

const RADIAN = Math.PI / 180;
const renderCustomizedLabel = ({
  cx,
  cy,
  midAngle,
  innerRadius,
  outerRadius,
  percent
}: any) => {
  const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
  const x = cx + radius * Math.cos(-midAngle * RADIAN);
  const y = cy + radius * Math.sin(-midAngle * RADIAN);

  return (
    <text
      x={x}
      y={y}
      fill="white"
      textAnchor={x > cx ? 'start' : 'end'}
      dominantBaseline="central"
    >
      {`${(percent * 100).toFixed(0)}%`}
    </text>
  );
};

const ProfitBreakdown: React.FC<ProfitBreakdownProps> = ({ data }) => {
  const [view, setView] = React.useState<'costs' | 'revenues'>('costs');

  const handleViewChange = (
    event: React.MouseEvent<HTMLElement>,
    newView: 'costs' | 'revenues'
  ) => {
    if (newView !== null) {
      setView(newView);
    }
  };

  const currentData = view === 'costs' ? data.costs : data.revenues;
  const total = currentData.reduce((sum, item) => sum + item.value, 0);

  return (
    <Paper sx={{ p: 2 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6" sx={{ flexGrow: 1 }}>
          {view === 'costs' ? '비용 구성' : '수익 구성'}
        </Typography>
        <ToggleButtonGroup
          value={view}
          exclusive
          onChange={handleViewChange}
          size="small"
        >
          <ToggleButton value="costs">비용</ToggleButton>
          <ToggleButton value="revenues">수익</ToggleButton>
        </ToggleButtonGroup>
      </Box>

      <Box sx={{ display: 'flex', height: 400 }}>
        <ResponsiveContainer>
          <PieChart>
            <Pie
              data={currentData}
              cx="50%"
              cy="50%"
              labelLine={false}
              label={renderCustomizedLabel}
              outerRadius={150}
              fill="#8884d8"
              dataKey="value"
            >
              {currentData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.color} />
              ))}
            </Pie>
            <Tooltip
              formatter={(value: number) => 
                new Intl.NumberFormat('ko-KR', {
                  style: 'currency',
                  currency: 'KRW'
                }).format(value)
              }
            />
            <Legend />
          </PieChart>
        </ResponsiveContainer>

        <Box sx={{ minWidth: 200, p: 2 }}>
          <Typography variant="subtitle2" gutterBottom>
            총 {view === 'costs' ? '비용' : '수익'}
          </Typography>
          <Typography variant="h6" gutterBottom>
            {new Intl.NumberFormat('ko-KR', {
              style: 'currency',
              currency: 'KRW'
            }).format(total)}
          </Typography>
          
          {currentData.map((item) => (
            <Box key={item.name} sx={{ mt: 1 }}>
              <Typography variant="body2" color="text.secondary">
                {item.name}
              </Typography>
              <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                <Typography variant="body2">
                  {new Intl.NumberFormat('ko-KR', {
                    style: 'currency',
                    currency: 'KRW'
                  }).format(item.value)}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {((item.value / total) * 100).toFixed(1)}%
                </Typography>
              </Box>
            </Box>
          ))}
        </Box>
      </Box>
    </Paper>
  );
};

export default ProfitBreakdown; 