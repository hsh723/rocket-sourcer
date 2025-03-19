import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip } from 'recharts';

interface ChartProps {
  data: any[];
  width?: number;
  height?: number;
}

export const Chart = ({ data, width = 600, height = 400 }: ChartProps) => (
  <LineChart width={width} height={height} data={data}>
    <CartesianGrid strokeDasharray="3 3" />
    <XAxis dataKey="name" />
    <YAxis />
    <Tooltip />
    <Line type="monotone" dataKey="value" stroke="#8884d8" />
  </LineChart>
);
