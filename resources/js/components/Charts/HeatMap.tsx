import { Box, Paper, Typography } from '@mui/material';

interface HeatMapData {
  x: string;
  y: string;
  value: number;
}

interface HeatMapProps {
  data: HeatMapData[];
  xLabels: string[];
  yLabels: string[];
  colorScale?: (value: number) => string;
}

export function HeatMap({
  data,
  xLabels,
  yLabels,
  colorScale = (value) => `hsl(200, 50%, ${100 - value * 50}%)`,
}: HeatMapProps) {
  const cellWidth = 40;
  const cellHeight = 40;
  const labelWidth = 100;

  return (
    <Box sx={{ overflowX: 'auto' }}>
      <Box sx={{ display: 'flex', ml: `${labelWidth}px` }}>
        {xLabels.map((label) => (
          <Typography
            key={label}
            sx={{
              width: cellWidth,
              textAlign: 'center',
              transform: 'rotate(-45deg)',
              transformOrigin: 'bottom left',
              whiteSpace: 'nowrap',
              mb: 4,
            }}
          >
            {label}
          </Typography>
        ))}
      </Box>
      <Box sx={{ display: 'flex' }}>
        <Box sx={{ width: labelWidth }}>
          {yLabels.map((label) => (
            <Typography
              key={label}
              sx={{
                height: cellHeight,
                display: 'flex',
                alignItems: 'center',
                pr: 2,
              }}
            >
              {label}
            </Typography>
          ))}
        </Box>
        <Box>
          {yLabels.map((y, yi) => (
            <Box key={y} sx={{ display: 'flex' }}>
              {xLabels.map((x, xi) => {
                const cell = data.find((d) => d.x === x && d.y === y);
                return (
                  <Paper
                    key={`${x}-${y}`}
                    sx={{
                      width: cellWidth,
                      height: cellHeight,
                      bgcolor: cell ? colorScale(cell.value) : 'transparent',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      border: '1px solid',
                      borderColor: 'divider',
                    }}
                  >
                    {cell?.value.toFixed(1)}
                  </Paper>
                );
              })}
            </Box>
          ))}
        </Box>
      </Box>
    </Box>
  );
} 