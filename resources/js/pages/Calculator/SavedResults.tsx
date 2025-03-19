import React from 'react';
import {
  Box,
  Container,
  Grid,
  Typography,
  Paper,
  TextField,
  InputAdornment,
  IconButton
} from '@mui/material';
import { Search as SearchIcon } from '@mui/icons-material';
import SavedCalculations from '@/components/Calculator/SavedCalculations';

const SavedResults: React.FC = () => {
  const [searchTerm, setSearchTerm] = React.useState('');

  return (
    <Container maxWidth="xl">
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          저장된 계산 결과
        </Typography>

        <Grid container spacing={3}>
          <Grid item xs={12}>
            <Paper sx={{ p: 2, mb: 3 }}>
              <TextField
                fullWidth
                placeholder="제품명으로 검색"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                InputProps={{
                  endAdornment: (
                    <InputAdornment position="end">
                      <IconButton>
                        <SearchIcon />
                      </IconButton>
                    </InputAdornment>
                  )
                }}
              />
            </Paper>
          </Grid>

          <Grid item xs={12}>
            <SavedCalculations />
          </Grid>
        </Grid>
      </Box>
    </Container>
  );
};

export default SavedResults; 