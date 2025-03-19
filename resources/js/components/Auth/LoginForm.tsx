import React from 'react';
import {
  Box,
  TextField,
  Button,
  Typography,
  Link,
  FormControlLabel,
  Checkbox,
  Alert
} from '@mui/material';
import { useForm } from 'react-hook-form';
import { useAuth } from '@/context/AuthContext';
import { useNavigate, Link as RouterLink } from 'react-router-dom';

interface LoginFormData {
  email: string;
  password: string;
  remember: boolean;
}

const LoginForm: React.FC = () => {
  const { register, handleSubmit, formState: { errors } } = useForm<LoginFormData>();
  const { login } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = React.useState<string | null>(null);

  const onSubmit = async (data: LoginFormData) => {
    try {
      await login(data.email, data.password);
      navigate('/dashboard');
    } catch (err) {
      setError('로그인에 실패했습니다. 이메일과 비밀번호를 확인해주세요.');
    }
  };

  return (
    <Box component="form" onSubmit={handleSubmit(onSubmit)} sx={{ mt: 1 }}>
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <TextField
        margin="normal"
        required
        fullWidth
        id="email"
        label="이메일 주소"
        autoComplete="email"
        autoFocus
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
        label="비밀번호"
        type="password"
        id="password"
        autoComplete="current-password"
        {...register('password', {
          required: '비밀번호를 입력해주세요',
          minLength: {
            value: 8,
            message: '비밀번호는 최소 8자 이상이어야 합니다'
          }
        })}
        error={!!errors.password}
        helperText={errors.password?.message}
      />

      <FormControlLabel
        control={
          <Checkbox
            color="primary"
            {...register('remember')}
          />
        }
        label="로그인 상태 유지"
      />

      <Button
        type="submit"
        fullWidth
        variant="contained"
        sx={{ mt: 3, mb: 2 }}
      >
        로그인
      </Button>

      <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
        <Link component={RouterLink} to="/forgot-password" variant="body2">
          비밀번호를 잊으셨나요?
        </Link>
        <Link component={RouterLink} to="/register" variant="body2">
          계정이 없으신가요? 회원가입
        </Link>
      </Box>
    </Box>
  );
};

export default LoginForm; 