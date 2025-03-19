import React from 'react';
import {
  Box,
  TextField,
  Button,
  Alert
} from '@mui/material';
import { useForm } from 'react-hook-form';
import { authService } from '@/services/authService';

interface ChangePasswordFormData {
  current_password: string;
  password: string;
  password_confirmation: string;
}

const ChangePasswordForm: React.FC = () => {
  const { register, handleSubmit, watch, reset, formState: { errors } } = useForm<ChangePasswordFormData>();
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);

  const password = watch('password');

  const onSubmit = async (data: ChangePasswordFormData) => {
    try {
      await authService.changePassword(data);
      setSuccess('비밀번호가 성공적으로 변경되었습니다.');
      setError(null);
      reset();
    } catch (err: any) {
      setError(err.response?.data?.message || '비밀번호 변경에 실패했습니다.');
      setSuccess(null);
    }
  };

  return (
    <Box component="form" onSubmit={handleSubmit(onSubmit)} sx={{ mt: 1 }}>
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

      <TextField
        margin="normal"
        required
        fullWidth
        label="현재 비밀번호"
        type="password"
        id="current_password"
        {...register('current_password', {
          required: '현재 비밀번호를 입력해주세요'
        })}
        error={!!errors.current_password}
        helperText={errors.current_password?.message}
      />

      <TextField
        margin="normal"
        required
        fullWidth
        label="새 비밀번호"
        type="password"
        id="password"
        {...register('password', {
          required: '새 비밀번호를 입력해주세요',
          minLength: {
            value: 8,
            message: '비밀번호는 최소 8자 이상이어야 합니다'
          },
          pattern: {
            value: /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/,
            message: '비밀번호는 영문자와 숫자를 포함해야 합니다'
          }
        })}
        error={!!errors.password}
        helperText={errors.password?.message}
      />

      <TextField
        margin="normal"
        required
        fullWidth
        label="새 비밀번호 확인"
        type="password"
        id="password_confirmation"
        {...register('password_confirmation', {
          required: '비밀번호 확인을 입력해주세요',
          validate: value =>
            value === password || '비밀번호가 일치하지 않습니다'
        })}
        error={!!errors.password_confirmation}
        helperText={errors.password_confirmation?.message}
      />

      <Button
        type="submit"
        fullWidth
        variant="contained"
        sx={{ mt: 3, mb: 2 }}
      >
        비밀번호 변경
      </Button>
    </Box>
  );
};

export default ChangePasswordForm; 