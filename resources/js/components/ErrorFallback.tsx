import { FallbackProps } from 'react-error-boundary';
import { Box, Button, Typography } from '@mui/material';

export function ErrorFallback({ error, resetErrorBoundary }: FallbackProps) {
  return (
    <Box
      sx={{
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        minHeight: '100vh',
        p: 3,
        textAlign: 'center',
      }}
    >
      <Typography variant="h4" gutterBottom>
        문제가 발생했습니다
      </Typography>
      <Typography color="text.secondary" sx={{ mb: 3 }}>
        {error.message}
      </Typography>
      <Button variant="contained" onClick={resetErrorBoundary}>
        다시 시도
      </Button>
    </Box>
  );
} 