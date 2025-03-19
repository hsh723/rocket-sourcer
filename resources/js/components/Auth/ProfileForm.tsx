import React from 'react';
import {
  Box,
  TextField,
  Button,
  Grid,
  Alert,
  Avatar,
  IconButton
} from '@mui/material';
import { PhotoCamera } from '@mui/icons-material';
import { useForm } from 'react-hook-form';
import { useAuth } from '@/context/AuthContext';
import { authService } from '@/services/authService';

interface ProfileFormData {
  name: string;
  email: string;
  phone?: string;
  company?: string;
  position?: string;
  avatar?: FileList;
}

const ProfileForm: React.FC = () => {
  const { user, updateUser } = useAuth();
  const { register, handleSubmit, formState: { errors } } = useForm<ProfileFormData>({
    defaultValues: {
      name: user?.name || '',
      email: user?.email || '',
      phone: user?.phone || '',
      company: user?.company || '',
      position: user?.position || ''
    }
  });

  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  const [avatarPreview, setAvatarPreview] = React.useState<string | null>(null);

  const handleAvatarChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    if (event.target.files && event.target.files[0]) {
      const file = event.target.files[0];
      setAvatarPreview(URL.createObjectURL(file));
    }
  };

  const onSubmit = async (data: ProfileFormData) => {
    try {
      const response = await authService.updateProfile(data);
      updateUser(response.data.user);
      setSuccess('프로필이 성공적으로 업데이트되었습니다.');
      setError(null);
    } catch (err: any) {
      setError(err.response?.data?.message || '프로필 업데이트에 실패했습니다.');
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

      <Box sx={{ display: 'flex', justifyContent: 'center', mb: 3 }}>
        <Box sx={{ position: 'relative' }}>
          <Avatar
            src={avatarPreview || user?.avatar}
            sx={{ width: 100, height: 100 }}
          />
          <IconButton
            color="primary"
            aria-label="upload picture"
            component="label"
            sx={{
              position: 'absolute',
              bottom: -8,
              right: -8,
              backgroundColor: 'background.paper'
            }}
          >
            <input
              hidden
              accept="image/*"
              type="file"
              {...register('avatar')}
              onChange={handleAvatarChange}
            />
            <PhotoCamera />
          </IconButton>
        </Box>
      </Box>

      <Grid container spacing={2}>
        <Grid item xs={12} sm={6}>
          <TextField
            required
            fullWidth
            id="name"
            label="이름"
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

        <Grid item xs={12} sm={6}>
          <TextField
            required
            fullWidth
            id="email"
            label="이메일 주소"
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

        <Grid item xs={12} sm={6}>
          <TextField
            fullWidth
            id="phone"
            label="전화번호"
            {...register('phone')}
          />
        </Grid>

        <Grid item xs={12} sm={6}>
          <TextField
            fullWidth
            id="company"
            label="회사명"
            {...register('company')}
          />
        </Grid>

        <Grid item xs={12}>
          <TextField
            fullWidth
            id="position"
            label="직책"
            {...register('position')}
          />
        </Grid>
      </Grid>

      <Button
        type="submit"
        fullWidth
        variant="contained"
        sx={{ mt: 3, mb: 2 }}
      >
        프로필 업데이트
      </Button>
    </Box>
  );
};

export default ProfileForm; 