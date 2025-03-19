import React from 'react';
import {
  Box,
  Paper,
  Typography,
  FormControl,
  FormControlLabel,
  Radio,
  RadioGroup,
  Switch,
  Slider,
  Grid,
  Alert,
  Button,
  useTheme,
  alpha
} from '@mui/material';
import { ColorLens as ColorLensIcon } from '@mui/icons-material';
import { settingsService } from '@/services/settingsService';
import { useThemeContext } from '@/context/ThemeContext';

interface ColorOption {
  name: string;
  primary: string;
  secondary: string;
}

const colorOptions: ColorOption[] = [
  { name: '기본', primary: '#1976d2', secondary: '#9c27b0' },
  { name: '그린', primary: '#2e7d32', secondary: '#00796b' },
  { name: '퍼플', primary: '#7b1fa2', secondary: '#d32f2f' },
  { name: '오렌지', primary: '#ed6c02', secondary: '#2196f3' },
  { name: '레드', primary: '#d32f2f', secondary: '#0288d1' }
];

const ThemeSettings: React.FC = () => {
  const theme = useTheme();
  const { updateTheme } = useThemeContext();
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  
  const [settings, setSettings] = React.useState({
    mode: 'light',
    colorScheme: colorOptions[0].name,
    isDense: false,
    fontSize: 14,
    borderRadius: 4
  });

  React.useEffect(() => {
    const loadThemeSettings = async () => {
      try {
        setLoading(true);
        const response = await settingsService.getThemeSettings();
        setSettings(response.data);
      } catch (err) {
        setError('테마 설정을 불러오는데 실패했습니다.');
      } finally {
        setLoading(false);
      }
    };

    loadThemeSettings();
  }, []);

  const handleSave = async () => {
    try {
      setLoading(true);
      await settingsService.updateThemeSettings(settings);
      updateTheme(settings);
      setSuccess('테마 설정이 저장되었습니다.');
    } catch (err) {
      setError('테마 설정 저장에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleReset = () => {
    setSettings({
      mode: 'light',
      colorScheme: colorOptions[0].name,
      isDense: false,
      fontSize: 14,
      borderRadius: 4
    });
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

      <Paper sx={{ p: 3 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
          <ColorLensIcon sx={{ mr: 1 }} />
          <Typography variant="h6">
            테마 설정
          </Typography>
        </Box>

        <Grid container spacing={4}>
          <Grid item xs={12} md={6}>
            <Typography variant="subtitle2" gutterBottom>
              테마 모드
            </Typography>
            <FormControl component="fieldset">
              <RadioGroup
                value={settings.mode}
                onChange={(e) => setSettings({ ...settings, mode: e.target.value })}
              >
                <FormControlLabel
                  value="light"
                  control={<Radio />}
                  label="라이트 모드"
                />
                <FormControlLabel
                  value="dark"
                  control={<Radio />}
                  label="다크 모드"
                />
                <FormControlLabel
                  value="system"
                  control={<Radio />}
                  label="시스템 설정 사용"
                />
              </RadioGroup>
            </FormControl>
          </Grid>

          <Grid item xs={12} md={6}>
            <Typography variant="subtitle2" gutterBottom>
              색상 테마
            </Typography>
            <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
              {colorOptions.map((option) => (
                <Box
                  key={option.name}
                  onClick={() => setSettings({ ...settings, colorScheme: option.name })}
                  sx={{
                    width: 48,
                    height: 48,
                    borderRadius: 1,
                    cursor: 'pointer',
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden',
                    border: theme => `2px solid ${
                      settings.colorScheme === option.name
                        ? theme.palette.primary.main
                        : 'transparent'
                    }`,
                    '&:hover': {
                      opacity: 0.8
                    }
                  }}
                >
                  <Box sx={{ flex: 1, bgcolor: option.primary }} />
                  <Box sx={{ flex: 1, bgcolor: option.secondary }} />
                </Box>
              ))}
            </Box>
          </Grid>

          <Grid item xs={12}>
            <FormControlLabel
              control={
                <Switch
                  checked={settings.isDense}
                  onChange={(e) => setSettings({ ...settings, isDense: e.target.checked })}
                />
              }
              label="조밀한 레이아웃 사용"
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <Typography variant="subtitle2" gutterBottom>
              기본 글자 크기
            </Typography>
            <Slider
              value={settings.fontSize}
              onChange={(_, value) => setSettings({ ...settings, fontSize: value as number })}
              min={12}
              max={18}
              step={1}
              marks={[
                { value: 12, label: '12px' },
                { value: 14, label: '14px' },
                { value: 16, label: '16px' },
                { value: 18, label: '18px' }
              ]}
              sx={{ width: '100%' }}
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <Typography variant="subtitle2" gutterBottom>
              모서리 둥글기
            </Typography>
            <Slider
              value={settings.borderRadius}
              onChange={(_, value) => setSettings({ ...settings, borderRadius: value as number })}
              min={0}
              max={16}
              step={2}
              marks={[
                { value: 0, label: '0px' },
                { value: 4, label: '4px' },
                { value: 8, label: '8px' },
                { value: 16, label: '16px' }
              ]}
              sx={{ width: '100%' }}
            />
          </Grid>

          <Grid item xs={12}>
            <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
              <Button
                variant="outlined"
                onClick={handleReset}
              >
                초기화
              </Button>
              <Button
                variant="contained"
                onClick={handleSave}
                disabled={loading}
              >
                저장
              </Button>
            </Box>
          </Grid>
        </Grid>
      </Paper>
    </Box>
  );
};

export default ThemeSettings; 