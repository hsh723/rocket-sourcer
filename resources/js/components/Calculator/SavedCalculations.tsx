import React from 'react';
import {
  Box,
  Paper,
  Typography,
  List,
  ListItem,
  ListItemText,
  ListItemSecondaryAction,
  IconButton,
  Menu,
  MenuItem,
  CircularProgress,
  Chip
} from '@mui/material';
import {
  MoreVert as MoreVertIcon,
  FileDownload as DownloadIcon,
  Delete as DeleteIcon
} from '@mui/icons-material';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { calculatorService } from '@/services/calculatorService';

const SavedCalculations: React.FC = () => {
  const [anchorEl, setAnchorEl] = React.useState<null | HTMLElement>(null);
  const [selectedId, setSelectedId] = React.useState<number | null>(null);
  const queryClient = useQueryClient();

  const { data: savedCalculations, isLoading } = useQuery({
    queryKey: ['savedCalculations'],
    queryFn: calculatorService.getSavedCalculations
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => calculatorService.deleteSavedCalculation(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['savedCalculations'] });
    }
  });

  const handleMenuOpen = (event: React.MouseEvent<HTMLElement>, id: number) => {
    setAnchorEl(event.currentTarget);
    setSelectedId(id);
  };

  const handleMenuClose = () => {
    setAnchorEl(null);
    setSelectedId(null);
  };

  const handleExport = async (format: 'pdf' | 'excel') => {
    if (!selectedId) return;
    
    try {
      const blob = await calculatorService.exportCalculation(selectedId, format);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `calculation-${selectedId}.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Export failed:', error);
    }
    handleMenuClose();
  };

  const handleDelete = () => {
    if (selectedId) {
      deleteMutation.mutate(selectedId);
    }
    handleMenuClose();
  };

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Paper sx={{ p: 2 }}>
      <Typography variant="h6" gutterBottom>
        저장된 계산 결과
      </Typography>

      <List>
        {savedCalculations?.map((calc) => (
          <ListItem
            key={calc.id}
            divider
            secondaryAction={
              <>
                <Chip
                  label={new Date(calc.createdAt).toLocaleDateString()}
                  size="small"
                  sx={{ mr: 1 }}
                />
                <IconButton
                  edge="end"
                  onClick={(e) => handleMenuOpen(e, calc.id)}
                >
                  <MoreVertIcon />
                </IconButton>
              </>
            }
          >
            <ListItemText
              primary={calc.productName}
              secondary={`예상 수익: ${new Intl.NumberFormat('ko-KR', {
                style: 'currency',
                currency: 'KRW'
              }).format(calc.expectedProfit)}`}
            />
          </ListItem>
        ))}
      </List>

      <Menu
        anchorEl={anchorEl}
        open={Boolean(anchorEl)}
        onClose={handleMenuClose}
      >
        <MenuItem onClick={() => handleExport('excel')}>
          <DownloadIcon fontSize="small" sx={{ mr: 1 }} />
          Excel로 내보내기
        </MenuItem>
        <MenuItem onClick={() => handleExport('pdf')}>
          <DownloadIcon fontSize="small" sx={{ mr: 1 }} />
          PDF로 내보내기
        </MenuItem>
        <MenuItem onClick={handleDelete} sx={{ color: 'error.main' }}>
          <DeleteIcon fontSize="small" sx={{ mr: 1 }} />
          삭제
        </MenuItem>
      </Menu>
    </Paper>
  );
};

export default SavedCalculations; 