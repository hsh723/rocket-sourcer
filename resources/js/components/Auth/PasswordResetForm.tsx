import React from 'react';
import {
  Box,
  TextField,
  Button,
  Typography,
  Alert
} from '@mui/material';
import { useForm } from 'react-hook-form';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { authService } from '@/services/authService';

interface PasswordResetFormData {
  email: string;
  password: string;
  password_confirmation: string;
  token: string;
}

const PasswordResetForm: React.FC = () => {
  const { register, handleSubmit, watch, formState: { errors } } = useForm<PasswordResetFormData>();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);

  const password = watch('password');
  const token = searchParams.get('token');

  const onSubmit = async (data: PasswordResetFormData) => {
    try {
      await authService.resetPassword({
        ...data,
        token: token || ''
      });
      setSuccess('비밀번호가 성공적으로 재설정되었습니다.');
      setTimeout(() => {
        navigate('/login');
      }, 3000);
    } catch (err: any) {
      setError(err.response?.data?.message || '비밀번호 재설정에 실패했습니다.');
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
        id="email"
        label="이메일 주소"
        autoComplete="email"
        {...register('email', {
          required: '이메일을 입력해주세요',
          pattern: {
            value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
            message: '올바른 이메일 주소를 입력해주세요'
          }
        })}
        error={!!errors.email}
        helperText={errors.email?.message}
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
        비밀번호 재설정
      </Button>
    </Box>
  );
};

export default PasswordResetForm; 