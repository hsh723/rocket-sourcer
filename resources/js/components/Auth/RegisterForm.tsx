import React from 'react';
import {
  Box,
  TextField,
  Button,
  Typography,
  Link,
  Alert,
  Grid
} from '@mui/material';
import { useForm } from 'react-hook-form';
import { useAuth } from '@/context/AuthContext';
import { useNavigate, Link as RouterLink } from 'react-router-dom';
import { authService } from '@/services/authService';

interface RegisterFormData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

const RegisterForm: React.FC = () => {
  const { register, handleSubmit, watch, formState: { errors } } = useForm<RegisterFormData>();
  const navigate = useNavigate();
  const [error, setError] = React.useState<string | null>(null);

  const password = watch('password');

  const onSubmit = async (data: RegisterFormData) => {
    try {
      await authService.register(data);
      navigate('/login', { state: { message: '회원가입이 완료되었습니다. 로그인해주세요.' } });
    } catch (err: any) {
      setError(err.response?.data?.message || '회원가입에 실패했습니다.');
    }
  };

  return (
    <Box component="form" onSubmit={handleSubmit(onSubmit)} sx={{ mt: 1 }}>
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <Grid container spacing={2}>
        <Grid item xs={12}>
          <TextField
            required
            fullWidth
            id="name"
            label="이름"
            autoComplete="name"
            autoFocus
            {...register('name', {
              required: '이름을 입력해주세요',
              minLength: {
                value: 2,
                message: '이름은 최소 2자 이상이어야 합니다'
              }
            })}
            error={!!errors.name}
            helperText={errors.name?.message}
          />
        </Grid>

        <Grid item xs={12}>
          <TextField
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
        </Grid>

        <Grid item xs={12}>
          <TextField
            required
            fullWidth
            label="비밀번호"
            type="password"
            id="password"
            autoComplete="new-password"
            {...register('password', {
              required: '비밀번호를 입력해주세요',
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
        </Grid>

        <Grid item xs={12}>
          <TextField
            required
            fullWidth
            label="비밀번호 확인"
            type="password"
            id="password_confirmation"
            autoComplete="new-password"
            {...register('password_confirmation', {
              required: '비밀번호 확인을 입력해주세요',
              validate: value =>
                value === password || '비밀번호가 일치하지 않습니다'
            })}
            error={!!errors.password_confirmation}
            helperText={errors.password_confirmation?.message}
          />
        </Grid>
      </Grid>

      <Button
        type="submit"
        fullWidth
        variant="contained"
        sx={{ mt: 3, mb: 2 }}
      >
        회원가입
      </Button>

      <Box sx={{ textAlign: 'center' }}>
        <Link component={RouterLink} to="/login" variant="body2">
          이미 계정이 있으신가요? 로그인
        </Link>
      </Box>
    </Box>
  );
};

export default RegisterForm; 