import React from 'react';
import {
  Box,
  Paper,
  Typography,
  FormControl,
  Select,
  MenuItem,
  Grid,
  Alert,
  Button,
  InputLabel,
  FormHelperText,
  Divider
} from '@mui/material';
import { Language as LanguageIcon } from '@mui/icons-material';
import { settingsService } from '@/services/settingsService';
import { useTranslation } from 'react-i18next';

interface Language {
  code: string;
  name: string;
  nativeName: string;
  flag: string;
}

const languages: Language[] = [
  { code: 'ko', name: 'Korean', nativeName: '한국어', flag: '🇰🇷' },
  { code: 'en', name: 'English', nativeName: 'English', flag: '🇺🇸' },
  { code: 'ja', name: 'Japanese', nativeName: '日本語', flag: '🇯🇵' },
  { code: 'zh', name: 'Chinese', nativeName: '中文', flag: '🇨🇳' }
];

const LanguageSettings: React.FC = () => {
  const { i18n } = useTranslation();
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  
  const [settings, setSettings] = React.useState({
    language: 'ko',
    dateFormat: 'YYYY-MM-DD',
    timeFormat: '24',
    timezone: 'Asia/Seoul',
    numberFormat: 'ko-KR'
  });

  React.useEffect(() => {
    const loadLanguageSettings = async () => {
      try {
        setLoading(true);
        const response = await settingsService.getLanguageSettings();
        setSettings(response.data);
      } catch (err) {
        setError('언어 설정을 불러오는데 실패했습니다.');
      } finally {
        setLoading(false);
      }
    };

    loadLanguageSettings();
  }, []);

  const handleSave = async () => {
    try {
      setLoading(true);
      await settingsService.updateLanguageSettings(settings);
      await i18n.changeLanguage(settings.language);
      setSuccess('언어 설정이 저장되었습니다.');
    } catch (err) {
      setError('언어 설정 저장에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleReset = () => {
    setSettings({
      language: 'ko',
      dateFormat: 'YYYY-MM-DD',
      timeFormat: '24',
      timezone: 'Asia/Seoul',
      numberFormat: 'ko-KR'
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
          <LanguageIcon sx={{ mr: 1 }} />
          <Typography variant="h6">
            언어 및 지역 설정
          </Typography>
        </Box>

        <Grid container spacing={4}>
          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <InputLabel>언어</InputLabel>
              <Select
                value={settings.language}
                onChange={(e) => setSettings({ ...settings, language: e.target.value })}
                label="언어"
              >
                {languages.map((lang) => (
                  <MenuItem key={lang.code} value={lang.code}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                      <span>{lang.flag}</span>
                      <span>{lang.nativeName}</span>
                      <Typography variant="body2" color="text.secondary" sx={{ ml: 'auto' }}>
                        ({lang.name})
                      </Typography>
                    </Box>
                  </MenuItem>
                ))}
              </Select>
              <FormHelperText>
                인터페이스 언어를 선택하세요
              </FormHelperText>
            </FormControl>
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <InputLabel>날짜 형식</InputLabel>
              <Select
                value={settings.dateFormat}
                onChange={(e) => setSettings({ ...settings, dateFormat: e.target.value })}
                label="날짜 형식"
              >
                <MenuItem value="YYYY-MM-DD">YYYY-MM-DD</MenuItem>
                <MenuItem value="DD/MM/YYYY">DD/MM/YYYY</MenuItem>
                <MenuItem value="MM/DD/YYYY">MM/DD/YYYY</MenuItem>
                <MenuItem value="YYYY년 MM월 DD일">YYYY년 MM월 DD일</MenuItem>
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12}>
            <Divider />
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <InputLabel>시간 형식</InputLabel>
              <Select
                value={settings.timeFormat}
                onChange={(e) => setSettings({ ...settings, timeFormat: e.target.value })}
                label="시간 형식"
              >
                <MenuItem value="12">12시간 (AM/PM)</MenuItem>
                <MenuItem value="24">24시간</MenuItem>
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <InputLabel>시간대</InputLabel>
              <Select
                value={settings.timezone}
                onChange={(e) => setSettings({ ...settings, timezone: e.target.value })}
                label="시간대"
              >
                <MenuItem value="Asia/Seoul">(GMT+9) 서울</MenuItem>
                <MenuItem value="Asia/Tokyo">(GMT+9) 도쿄</MenuItem>
                <MenuItem value="Asia/Shanghai">(GMT+8) 상하이</MenuItem>
                <MenuItem value="America/Los_Angeles">(GMT-8) 로스앤젤레스</MenuItem>
                <MenuItem value="America/New_York">(GMT-5) 뉴욕</MenuItem>
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <InputLabel>숫자 형식</InputLabel>
              <Select
                value={settings.numberFormat}
                onChange={(e) => setSettings({ ...settings, numberFormat: e.target.value })}
                label="숫자 형식"
              >
                <MenuItem value="ko-KR">한국어 (1,234,567.89)</MenuItem>
                <MenuItem value="en-US">영어 (1,234,567.89)</MenuItem>
                <MenuItem value="ja-JP">일본어 (1,234,567.89)</MenuItem>
                <MenuItem value="zh-CN">중국어 (1,234,567.89)</MenuItem>
              </Select>
            </FormControl>
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

export default LanguageSettings; 