import { Box, useMediaQuery } from '@mui/material';
import { useTheme } from '@mui/material/styles';

export const Layout = ({ children }) => {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));

  return (
    <Box
      sx={{
        display: 'flex',
        flexDirection: 'column',
        minHeight: '100vh',
        p: isMobile ? 2 : 4,
        transition: 'background-color 0.3s ease',
      }}
      role="main"
    >
      {children}
    </Box>
  );
};
