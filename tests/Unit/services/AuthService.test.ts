import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { AuthService } from '@/services/AuthService';
import { LoginCredentials, RegisterData } from '@/types/Auth';

describe('AuthService', () => {
  let service: AuthService;
  
  beforeEach(() => {
    service = new AuthService();
    localStorage.clear();
    jest.clearAllMocks();
  });

  describe('login', () => {
    it('유효한 자격 증명으로 로그인해야 합니다', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'password123'
      };

      const mockResponse = {
        user: {
          id: '1',
          email: 'test@example.com',
          name: '테스트 사용자'
        },
        token: 'mock-jwt-token'
      };

      jest.spyOn(service['api'], 'post').mockResolvedValue({ data: mockResponse });

      const result = await service.login(credentials);

      expect(result).toEqual(mockResponse);
      expect(localStorage.getItem('token')).toBe(mockResponse.token);
      expect(service.getCurrentUser()).toEqual(mockResponse.user);
    });

    it('잘못된 자격 증명으로 로그인 시 오류를 발생시켜야 합니다', async () => {
      const credentials: LoginCredentials = {
        email: 'wrong@example.com',
        password: 'wrongpass'
      };

      jest.spyOn(service['api'], 'post').mockRejectedValue({
        response: {
          data: { message: '잘못된 이메일 또는 비밀번호입니다.' }
        }
      });

      await expect(service.login(credentials)).rejects.toThrow();
    });
  });

  describe('register', () => {
    it('새 사용자를 등록해야 합니다', async () => {
      const registerData: RegisterData = {
        name: '신규 사용자',
        email: 'new@example.com',
        password: 'password123',
        passwordConfirmation: 'password123'
      };

      const mockResponse = {
        user: {
          id: '2',
          email: registerData.email,
          name: registerData.name
        },
        token: 'mock-jwt-token'
      };

      jest.spyOn(service['api'], 'post').mockResolvedValue({ data: mockResponse });

      const result = await service.register(registerData);

      expect(result).toEqual(mockResponse);
      expect(localStorage.getItem('token')).toBe(mockResponse.token);
    });

    it('이미 존재하는 이메일로 등록 시 오류를 발생시켜야 합니다', async () => {
      const registerData: RegisterData = {
        name: '기존 사용자',
        email: 'existing@example.com',
        password: 'password123',
        passwordConfirmation: 'password123'
      };

      jest.spyOn(service['api'], 'post').mockRejectedValue({
        response: {
          data: { message: '이미 사용 중인 이메일입니다.' }
        }
      });

      await expect(service.register(registerData)).rejects.toThrow();
    });
  });

  describe('logout', () => {
    it('사용자를 로그아웃하고 토큰을 제거해야 합니다', async () => {
      localStorage.setItem('token', 'mock-token');
      service['currentUser'] = { id: '1', name: '테스트', email: 'test@example.com' };

      await service.logout();

      expect(localStorage.getItem('token')).toBeNull();
      expect(service.getCurrentUser()).toBeNull();
    });
  });

  describe('resetPassword', () => {
    it('비밀번호 재설정 이메일을 전송해야 합니다', async () => {
      const email = 'test@example.com';
      
      jest.spyOn(service['api'], 'post').mockResolvedValue({
        data: { message: '비밀번호 재설정 이메일이 전송되었습니다.' }
      });

      const result = await service.resetPassword(email);
      expect(result).toHaveProperty('message');
    });
  });

  describe('updatePassword', () => {
    it('비밀번호를 성공적으로 변경해야 합니다', async () => {
      const data = {
        currentPassword: 'oldpass123',
        newPassword: 'newpass123',
        newPasswordConfirmation: 'newpass123'
      };

      jest.spyOn(service['api'], 'put').mockResolvedValue({
        data: { message: '비밀번호가 성공적으로 변경되었습니다.' }
      });

      const result = await service.updatePassword(data);
      expect(result).toHaveProperty('message');
    });
  });
}); 