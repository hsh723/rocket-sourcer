import React from 'react';
import {
  Box,
  Paper,
  Typography,
  Button,
  Alert,
  LinearProgress,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Grid,
  Checkbox,
  FormControlLabel,
  FormGroup,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions
} from '@mui/material';
import {
  Upload as UploadIcon,
  Download as DownloadIcon,
  Delete as DeleteIcon
} from '@mui/icons-material';
import { settingsService } from '@/services/settingsService';

interface ImportExportOptions {
  products: boolean;
  competitors: boolean;
  calculations: boolean;
  settings: boolean;
  analytics: boolean;
}

const ImportExportControls: React.FC = () => {
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  const [exportFormat, setExportFormat] = React.useState<'csv' | 'excel' | 'json'>('excel');
  const [importFile, setImportFile] = React.useState<File | null>(null);
  const [confirmDialogOpen, setConfirmDialogOpen] = React.useState(false);
  const [options, setOptions] = React.useState<ImportExportOptions>({
    products: true,
    competitors: true,
    calculations: true,
    settings: false,
    analytics: false
  });
  const fileInputRef = React.useRef<HTMLInputElement>(null);

  const handleExport = async () => {
    try {
      setLoading(true);
      const response = await settingsService.exportData({
        format: exportFormat,
        options
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `export-${new Date().toISOString()}.${exportFormat}`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      setSuccess('데이터가 성공적으로 내보내졌습니다.');
    } catch (err: any) {
      setError('데이터 내보내기에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleImportClick = () => {
    fileInputRef.current?.click();
  };

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    if (event.target.files && event.target.files[0]) {
      setImportFile(event.target.files[0]);
      setConfirmDialogOpen(true);
    }
  };

  const handleImport = async () => {
    if (!importFile) return;

    try {
      setLoading(true);
      const formData = new FormData();
      formData.append('file', importFile);
      formData.append('options', JSON.stringify(options));

      await settingsService.importData(formData);
      setSuccess('데이터가 성공적으로 가져와졌습니다.');
      setConfirmDialogOpen(false);
      setImportFile(null);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    } catch (err: any) {
      setError('데이터 가져오기에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleOptionChange = (option: keyof ImportExportOptions) => {
    setOptions(prev => ({
      ...prev,
      [option]: !prev[option]
    }));
  };

  return (
    <Box>
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      {success && (
        <Alert severity="success" sx={{ mb: 2 }}>
          {success}
        </Alert>
      )}

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          데이터 내보내기/가져오기
        </Typography>

        {loading && (
          <Box sx={{ width: '100%', mb: 2 }}>
            <LinearProgress />
          </Box>
        )}

        <Grid container spacing={3}>
          <Grid item xs={12} md={6}>
            <FormControl fullWidth sx={{ mb: 2 }}>
              <InputLabel>내보내기 형식</InputLabel>
              <Select
                value={exportFormat}
                onChange={(e) => setExportFormat(e.target.value as 'csv' | 'excel' | 'json')}
                label="내보내기 형식"
              >
                <MenuItem value="excel">Excel</MenuItem>
                <MenuItem value="csv">CSV</MenuItem>
                <MenuItem value="json">JSON</MenuItem>
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12}>
            <Typography variant="subtitle2" gutterBottom>
              데이터 선택
            </Typography>
            <FormGroup>
              <Grid container spacing={2}>
                <Grid item xs={12} sm={6}>
                  <FormControlLabel
                    control={
                      <Checkbox
                        checked={options.products}
                        onChange={() => handleOptionChange('products')}
                      />
                    }
                    label="제품 데이터"
                  />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <FormControlLabel
                    control={
                      <Checkbox
                        checked={options.competitors}
                        onChange={() => handleOptionChange('competitors')}
                      />
                    }
                    label="경쟁사 데이터"
                  />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <FormControlLabel
                    control={
                      <Checkbox
                        checked={options.calculations}
                        onChange={() => handleOptionChange('calculations')}
                      />
                    }
                    label="계산 결과"
                  />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <FormControlLabel
                    control={
                      <Checkbox
                        checked={options.settings}
                        onChange={() => handleOptionChange('settings')}
                      />
                    }
                    label="설정"
                  />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <FormControlLabel
                    control={
                      <Checkbox
                        checked={options.analytics}
                        onChange={() => handleOptionChange('analytics')}
                      />
                    }
                    label="분석 데이터"
                  />
                </Grid>
              </Grid>
            </FormGroup>
          </Grid>

          <Grid item xs={12}>
            <Box sx={{ display: 'flex', gap: 2 }}>
              <Button
                variant="contained"
                startIcon={<DownloadIcon />}
                onClick={handleExport}
                disabled={loading || !Object.values(options).some(v => v)}
              >
                내보내기
              </Button>
              <Button
                variant="contained"
                startIcon={<UploadIcon />}
                onClick={handleImportClick}
                disabled={loading}
              >
                가져오기
              </Button>
              <input
                type="file"
                ref={fileInputRef}
                onChange={handleFileSelect}
                style={{ display: 'none' }}
                accept=".xlsx,.csv,.json"
              />
            </Box>
          </Grid>
        </Grid>
      </Paper>

      <Dialog
        open={confirmDialogOpen}
        onClose={() => setConfirmDialogOpen(false)}
      >
        <DialogTitle>데이터 가져오기 확인</DialogTitle>
        <DialogContent>
          <Typography>
            선택한 데이터를 가져오면 기존 데이터가 덮어씌워질 수 있습니다. 계속하시겠습니까?
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button
            onClick={() => {
              setConfirmDialogOpen(false);
              setImportFile(null);
              if (fileInputRef.current) {
                fileInputRef.current.value = '';
              }
            }}
          >
            취소
          </Button>
          <Button
            onClick={handleImport}
            color="primary"
            variant="contained"
          >
            가져오기
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default ImportExportControls; 