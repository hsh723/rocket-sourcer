import React from 'react';
import {
  Box,
  Paper,
  Typography,
  FormControl,
  Select,
  MenuItem,
  SelectChangeEvent
} from '@mui/material';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer
} from 'recharts';

interface ComparisonChartProps {
  data: {
    category: string;
    values: {
      name: string;
      value: number;
      color: string;
    }[];
  }[];
  title: string;
}

const ComparisonChart: React.FC<ComparisonChartProps> = ({ data, title }) => {
  const [chartType, setChartType] = React.useState<'grouped' | 'stacked'>('grouped');
  const [sortBy, setSortBy] = React.useState<'category' | 'value'>('category');

  const handleChartTypeChange = (event: SelectChangeEvent<string>) => {
    setChartType(event.target.value as 'grouped' | 'stacked');
  };

  const handleSortChange = (event: SelectChangeEvent<string>) => {
    setSortBy(event.target.value as 'category' | 'value');
  };

  const sortedData = React.useMemo(() => {
    if (sortBy === 'category') {
      return [...data].sort((a, b) => a.category.localeCompare(b.category));
    }
    return [...data].sort((a, b) => {
      const sumA = a.values.reduce((sum, item) => sum + item.value, 0);
      const sumB = b.values.reduce((sum, item) => sum + item.value, 0);
      return sumB - sumA;
    });
  }, [data, sortBy]);

  return (
    <Paper sx={{ p: 2 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6" sx={{ flexGrow: 1 }}>
          {title}
        </Typography>
        <FormControl size="small" sx={{ minWidth: 120, mr: 1 }}>
          <Select
            value={chartType}
            onChange={handleChartTypeChange}
            displayEmpty
          >
            <MenuItem value="grouped">그룹형</MenuItem>
            <MenuItem value="stacked">누적형</MenuItem>
          </Select>
        </FormControl>
        <FormControl size="small" sx={{ minWidth: 120 }}>
          <Select
            value={sortBy}
            onChange={handleSortChange}
            displayEmpty
          >
            <MenuItem value="category">카테고리순</MenuItem>
            <MenuItem value="value">값순</MenuItem>
          </Select>
        </FormControl>
      </Box>

      <Box sx={{ width: '100%', height: 400 }}>
        <ResponsiveContainer>
          <BarChart
            data={sortedData}
            margin={{
              top: 20,
              right: 30,
              left: 20,
              bottom: 5
            }}
          >
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis
              dataKey="category"
              tick={{ fontSize: 12 }}
              interval={0}
              angle={-45}
              textAnchor="end"
            />
            <YAxis />
            <Tooltip
              formatter={(value: number) => new Intl.NumberFormat('ko-KR').format(value)}
            />
            <Legend />
            {data[0]?.values.map((item, index) => (
              <Bar
                key={item.name}
                dataKey={`values[${index}].value`}
                name={item.name}
                fill={item.color}
                stackId={chartType === 'stacked' ? 'stack' : undefined}
              />
            ))}
          </BarChart>
        </ResponsiveContainer>
      </Box>
    </Paper>
  );
};

export default ComparisonChart; 