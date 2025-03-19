import React from 'react';
import {
  Box,
  Paper,
  Typography,
  Switch,
  FormControlLabel,
  FormGroup,
  Grid,
  TextField,
  Button,
  Alert,
  Divider,
  Select,
  MenuItem,
  FormControl,
  InputLabel
} from '@mui/material';
import { useForm, Controller } from 'react-hook-form';
import { settingsService } from '@/services/settingsService';

interface NotificationFormData {
  email_notifications: boolean;
  push_notifications: boolean;
  slack_notifications: boolean;
  slack_webhook_url?: string;
  notification_frequency: 'realtime' | 'hourly' | 'daily' | 'weekly';
  price_alert_threshold: number;
  stock_alert_threshold: number;
  notify_on_price_change: boolean;
  notify_on_stock_change: boolean;
  notify_on_competitor_change: boolean;
  notify_on_trend_change: boolean;
  quiet_hours_start?: string;
  quiet_hours_end?: string;
}

const NotificationForm: React.FC = () => {
  const { control, register, handleSubmit, watch, formState: { errors } } = useForm<NotificationFormData>();
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);

  const slackNotifications = watch('slack_notifications');

  React.useEffect(() => {
    const loadNotificationSettings = async () => {
      try {
        const settings = await settingsService.getNotificationSettings();
        Object.keys(settings).forEach(key => {
          // setValue는 여기서 사용하지 않습니다. Controller 컴포넌트가 처리합니다.
        });
      } catch (err: any) {
        setError('알림 설정을 불러오는데 실패했습니다.');
      }
    };

    loadNotificationSettings();
  }, []);

  const onSubmit = async (data: NotificationFormData) => {
    try {
      await settingsService.updateNotificationSettings(data);
      setSuccess('알림 설정이 성공적으로 업데이트되었습니다.');
      setError(null);
    } catch (err: any) {
      setError(err.response?.data?.message || '알림 설정 업데이트에 실패했습니다.');
      setSuccess(null);
    }
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
          알림 채널 설정
        </Typography>
        <FormGroup>
          <Controller
            name="email_notifications"
            control={control}
            defaultValue={false}
            render={({ field }) => (
              <FormControlLabel
                control={<Switch {...field} />}
                label="이메일 알림"
              />
            )}
          />
          <Controller
            name="push_notifications"
            control={control}
            defaultValue={false}
            render={({ field }) => (
              <FormControlLabel
                control={<Switch {...field} />}
                label="푸시 알림"
              />
            )}
          />
          <Controller
            name="slack_notifications"
            control={control}
            defaultValue={false}
            render={({ field }) => (
              <FormControlLabel
                control={<Switch {...field} />}
                label="Slack 알림"
              />
            )}
          />
        </FormGroup>

        {slackNotifications && (
          <TextField
            fullWidth
            margin="normal"
            label="Slack Webhook URL"
            {...register('slack_webhook_url', {
              required: 'Slack Webhook URL은 필수입니다'
            })}
            error={!!errors.slack_webhook_url}
            helperText={errors.slack_webhook_url?.message}
          />
        )}
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          알림 트리거 설정
        </Typography>
        <Grid container spacing={2}>
          <Grid item xs={12}>
            <FormControl fullWidth>
              <InputLabel>알림 빈도</InputLabel>
              <Controller
                name="notification_frequency"
                control={control}
                defaultValue="daily"
                render={({ field }) => (
                  <Select {...field} label="알림 빈도">
                    <MenuItem value="realtime">실시간</MenuItem>
                    <MenuItem value="hourly">시간별</MenuItem>
                    <MenuItem value="daily">일별</MenuItem>
                    <MenuItem value="weekly">주별</MenuItem>
                  </Select>
                )}
              />
            </FormControl>
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField
              fullWidth
              type="number"
              label="가격 변동 알림 기준 (%)"
              {...register('price_alert_threshold', {
                min: { value: 1, message: '최소 1% 이상이어야 합니다' },
                max: { value: 100, message: '최대 100%까지 설정 가능합니다' }
              })}
              error={!!errors.price_alert_threshold}
              helperText={errors.price_alert_threshold?.message}
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField
              fullWidth
              type="number"
              label="재고 알림 기준 (개)"
              {...register('stock_alert_threshold', {
                min: { value: 1, message: '최소 1개 이상이어야 합니다' }
              })}
              error={!!errors.stock_alert_threshold}
              helperText={errors.stock_alert_threshold?.message}
            />
          </Grid>
        </Grid>

        <Box sx={{ mt: 2 }}>
          <FormGroup>
            <Controller
              name="notify_on_price_change"
              control={control}
              defaultValue={false}
              render={({ field }) => (
                <FormControlLabel
                  control={<Switch {...field} />}
                  label="가격 변동 시 알림"
                />
              )}
            />
            <Controller
              name="notify_on_stock_change"
              control={control}
              defaultValue={false}
              render={({ field }) => (
                <FormControlLabel
                  control={<Switch {...field} />}
                  label="재고 변동 시 알림"
                />
              )}
            />
            <Controller
              name="notify_on_competitor_change"
              control={control}
              defaultValue={false}
              render={({ field }) => (
                <FormControlLabel
                  control={<Switch {...field} />}
                  label="경쟁사 변동 시 알림"
                />
              )}
            />
            <Controller
              name="notify_on_trend_change"
              control={control}
              defaultValue={false}
              render={({ field }) => (
                <FormControlLabel
                  control={<Switch {...field} />}
                  label="트렌드 변동 시 알림"
                />
              )}
            />
          </FormGroup>
        </Box>
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          방해 금지 시간 설정
        </Typography>
        <Grid container spacing={2}>
          <Grid item xs={12} sm={6}>
            <TextField
              fullWidth
              type="time"
              label="시작 시간"
              InputLabelProps={{ shrink: true }}
              {...register('quiet_hours_start')}
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField
              fullWidth
              type="time"
              label="종료 시간"
              InputLabelProps={{ shrink: true }}
              {...register('quiet_hours_end')}
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

export default NotificationForm; 