import React from 'react';
import {
  Box,
  TextField,
  Button,
  Alert,
  Typography,
  IconButton,
  Paper,
  Grid,
  Tooltip,
  Switch,
  FormControlLabel
} from '@mui/material';
import {
  ContentCopy as CopyIcon,
  Refresh as RefreshIcon,
  Visibility as VisibilityIcon,
  VisibilityOff as VisibilityOffIcon
} from '@mui/icons-material';
import { useForm } from 'react-hook-form';
import { settingsService } from '@/services/settingsService';

interface APIFormData {
  coupang_access_key: string;
  coupang_secret_key: string;
  naver_client_id: string;
  naver_client_secret: string;
  enable_api_logging: boolean;
  request_timeout: number;
}

const APIForm: React.FC = () => {
  const { register, handleSubmit, setValue, watch, formState: { errors } } = useForm<APIFormData>();
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  const [showSecrets, setShowSecrets] = React.useState<{[key: string]: boolean}>({});

  React.useEffect(() => {
    const loadAPISettings = async () => {
      try {
        const settings = await settingsService.getAPISettings();
        Object.keys(settings).forEach(key => {
          setValue(key as keyof APIFormData, settings[key]);
        });
      } catch (err: any) {
        setError('API 설정을 불러오는데 실패했습니다.');
      }
    };

    loadAPISettings();
  }, [setValue]);

  const onSubmit = async (data: APIFormData) => {
    try {
      await settingsService.updateAPISettings(data);
      setSuccess('API 설정이 성공적으로 업데이트되었습니다.');
      setError(null);
    } catch (err: any) {
      setError(err.response?.data?.message || 'API 설정 업데이트에 실패했습니다.');
      setSuccess(null);
    }
  };

  const handleCopyToClipboard = (value: string) => {
    navigator.clipboard.writeText(value);
  };

  const handleRegenerateKey = async (keyType: string) => {
    try {
      const newKey = await settingsService.regenerateAPIKey(keyType);
      setValue(keyType as keyof APIFormData, newKey);
      setSuccess('API 키가 성공적으로 재생성되었습니다.');
    } catch (err: any) {
      setError('API 키 재생성에 실패했습니다.');
    }
  };

  const toggleSecretVisibility = (field: string) => {
    setShowSecrets(prev => ({
      ...prev,
      [field]: !prev[field]
    }));
  };

  return (
    <Box component="form" onSubmit={handleSubmit(onSubmit)}>
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
          쿠팡 API 설정
        </Typography>
        <Grid container spacing={2}>
          <Grid item xs={12}>
            <TextField
              fullWidth
              label="Access Key"
              {...register('coupang_access_key', {
                required: 'Access Key는 필수입니다'
              })}
              type={showSecrets.coupang_access_key ? 'text' : 'password'}
              InputProps={{
                endAdornment: (
                  <Box>
                    <IconButton onClick={() => toggleSecretVisibility('coupang_access_key')}>
                      {showSecrets.coupang_access_key ? <VisibilityOffIcon /> : <VisibilityIcon />}
                    </IconButton>
                    <IconButton onClick={() => handleCopyToClipboard(watch('coupang_access_key'))}>
                      <CopyIcon />
                    </IconButton>
                    <IconButton onClick={() => handleRegenerateKey('coupang_access_key')}>
                      <RefreshIcon />
                    </IconButton>
                  </Box>
                )
              }}
              error={!!errors.coupang_access_key}
              helperText={errors.coupang_access_key?.message}
            />
          </Grid>
          <Grid item xs={12}>
            <TextField
              fullWidth
              label="Secret Key"
              {...register('coupang_secret_key', {
                required: 'Secret Key는 필수입니다'
              })}
              type={showSecrets.coupang_secret_key ? 'text' : 'password'}
              InputProps={{
                endAdornment: (
                  <Box>
                    <IconButton onClick={() => toggleSecretVisibility('coupang_secret_key')}>
                      {showSecrets.coupang_secret_key ? <VisibilityOffIcon /> : <VisibilityIcon />}
                    </IconButton>
                    <IconButton onClick={() => handleCopyToClipboard(watch('coupang_secret_key'))}>
                      <CopyIcon />
                    </IconButton>
                    <IconButton onClick={() => handleRegenerateKey('coupang_secret_key')}>
                      <RefreshIcon />
                    </IconButton>
                  </Box>
                )
              }}
              error={!!errors.coupang_secret_key}
              helperText={errors.coupang_secret_key?.message}
            />
          </Grid>
        </Grid>
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          네이버 API 설정
        </Typography>
        <Grid container spacing={2}>
          <Grid item xs={12}>
            <TextField
              fullWidth
              label="Client ID"
              {...register('naver_client_id', {
                required: 'Client ID는 필수입니다'
              })}
              type={showSecrets.naver_client_id ? 'text' : 'password'}
              InputProps={{
                endAdornment: (
                  <Box>
                    <IconButton onClick={() => toggleSecretVisibility('naver_client_id')}>
                      {showSecrets.naver_client_id ? <VisibilityOffIcon /> : <VisibilityIcon />}
                    </IconButton>
                    <IconButton onClick={() => handleCopyToClipboard(watch('naver_client_id'))}>
                      <CopyIcon />
                    </IconButton>
                  </Box>
                )
              }}
              error={!!errors.naver_client_id}
              helperText={errors.naver_client_id?.message}
            />
          </Grid>
          <Grid item xs={12}>
            <TextField
              fullWidth
              label="Client Secret"
              {...register('naver_client_secret', {
                required: 'Client Secret은 필수입니다'
              })}
              type={showSecrets.naver_client_secret ? 'text' : 'password'}
              InputProps={{
                endAdornment: (
                  <Box>
                    <IconButton onClick={() => toggleSecretVisibility('naver_client_secret')}>
                      {showSecrets.naver_client_secret ? <VisibilityOffIcon /> : <VisibilityIcon />}
                    </IconButton>
                    <IconButton onClick={() => handleCopyToClipboard(watch('naver_client_secret'))}>
                      <CopyIcon />
                    </IconButton>
                  </Box>
                )
              }}
              error={!!errors.naver_client_secret}
              helperText={errors.naver_client_secret?.message}
            />
          </Grid>
        </Grid>
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          고급 설정
        </Typography>
        <Grid container spacing={2}>
          <Grid item xs={12}>
            <FormControlLabel
              control={
                <Switch
                  {...register('enable_api_logging')}
                />
              }
              label="API 로깅 활성화"
            />
          </Grid>
          <Grid item xs={12}>
            <TextField
              fullWidth
              type="number"
              label="요청 타임아웃 (초)"
              {...register('request_timeout', {
                min: { value: 5, message: '최소 5초 이상이어야 합니다' },
                max: { value: 60, message: '최대 60초까지 설정 가능합니다' }
              })}
              error={!!errors.request_timeout}
              helperText={errors.request_timeout?.message}
            />
          </Grid>
        </Grid>
      </Paper>

      <Button
        type="submit"
        variant="contained"
        color="primary"
        fullWidth
      >
        설정 저장
      </Button>
    </Box>
  );
};

export default APIForm; 