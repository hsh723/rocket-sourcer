import React from 'react';
import {
  Box,
  Paper,
  Typography,
  FormControl,
  Select,
  MenuItem,
  SelectChangeEvent,
  Chip
} from '@mui/material';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer
} from 'recharts';

interface TrendAnalysisProps {
  data: {
    date: string;
    metrics: {
      name: string;
      value: number;
      color: string;
    }[];
  }[];
  title: string;
}

const TrendAnalysis: React.FC<TrendAnalysisProps> = ({ data, title }) => {
  const [timeRange, setTimeRange] = React.useState<'1M' | '3M' | '6M' | '1Y'>('3M');
  const [selectedMetrics, setSelectedMetrics] = React.useState<string[]>(
    data[0]?.metrics.map(m => m.name) || []
  );

  const handleTimeRangeChange = (event: SelectChangeEvent<string>) => {
    setTimeRange(event.target.value as '1M' | '3M' | '6M' | '1Y');
  };

  const handleMetricToggle = (metricName: string) => {
    setSelectedMetrics(prev => {
      if (prev.includes(metricName)) {
        return prev.filter(name => name !== metricName);
      }
      return [...prev, metricName];
    });
  };

  const filteredData = React.useMemo(() => {
    // 시간 범위에 따른 데이터 필터링 로직
    const now = new Date();
    const monthsToSubtract = {
      '1M': 1,
      '3M': 3,
      '6M': 6,
      '1Y': 12
    }[timeRange];

    const cutoffDate = new Date(now.setMonth(now.getMonth() - monthsToSubtract));
    return data.filter(item => new Date(item.date) >= cutoffDate);
  }, [data, timeRange]);

  return (
    <Paper sx={{ p: 2 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6" sx={{ flexGrow: 1 }}>
          {title}
        </Typography>
        <FormControl size="small" sx={{ minWidth: 120 }}>
          <Select
            value={timeRange}
            onChange={handleTimeRangeChange}
            displayEmpty
          >
            <MenuItem value="1M">1개월</MenuItem>
            <MenuItem value="3M">3개월</MenuItem>
            <MenuItem value="6M">6개월</MenuItem>
            <MenuItem value="1Y">1년</MenuItem>
          </Select>
        </FormControl>
      </Box>

      <Box sx={{ mb: 2 }}>
        {data[0]?.metrics.map(metric => (
          <Chip
            key={metric.name}
            label={metric.name}
            onClick={() => handleMetricToggle(metric.name)}
            sx={{
              m: 0.5,
              backgroundColor: selectedMetrics.includes(metric.name)
                ? metric.color
                : undefined
            }}
            color={selectedMetrics.includes(metric.name) ? 'primary' : 'default'}
          />
        ))}
      </Box>

      <Box sx={{ width: '100%', height: 400 }}>
        <ResponsiveContainer>
          <LineChart
            data={filteredData}
            margin={{
              top: 20,
              right: 30,
              left: 20,
              bottom: 5
            }}
          >
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis
              dataKey="date"
              tick={{ fontSize: 12 }}
              interval={Math.floor(filteredData.length / 6)}
            />
            <YAxis />
            <Tooltip />
            <Legend />
            {data[0]?.metrics
              .filter(metric => selectedMetrics.includes(metric.name))
              .map((metric, index) => (
                <Line
                  key={metric.name}
                  type="monotone"
                  dataKey={`metrics[${index}].value`}
                  name={metric.name}
                  stroke={metric.color}
                  dot={false}
                />
              ))}
          </LineChart>
        </ResponsiveContainer>
      </Box>
    </Paper>
  );
};

export default TrendAnalysis; 