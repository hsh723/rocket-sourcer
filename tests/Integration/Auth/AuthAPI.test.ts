import { describe, expect, it, beforeEach, afterEach } from '@jest/globals';
import request from 'supertest';
import { app } from '@/app';
import { db } from '@/database';
import { hashPassword } from '@/utils/auth';
import { createTestUser, generateAuthToken } from '@/tests/helpers/auth';

describe('Auth API Integration', () => {
  beforeEach(async () => {
    await db.migrate.latest();
  });

  afterEach(async () => {
    await db.migrate.rollback();
  });

  describe('회원가입 API', () => {
    const newUser = {
      name: '테스트 사용자',
      email: 'test@example.com',
      password: 'password123',
      passwordConfirmation: 'password123'
    };

    it('새 사용자를 등록할 수 있어야 합니다', async () => {
      const response = await request(app)
        .post('/api/auth/register')
        .send(newUser);

      expect(response.status).toBe(201);
      expect(response.body).toHaveProperty('token');
      expect(response.body.user).toHaveProperty('id');
      expect(response.body.user.email).toBe(newUser.email);
    });

    it('이미 존재하는 이메일로 가입을 시도하면 오류를 반환해야 합니다', async () => {
      await request(app).post('/api/auth/register').send(newUser);
      
      const response = await request(app)
        .post('/api/auth/register')
        .send(newUser);

      expect(response.status).toBe(400);
      expect(response.body.message).toBe('이미 사용 중인 이메일입니다');
    });

    it('비밀번호 확인이 일치하지 않으면 오류를 반환해야 합니다', async () => {
      const response = await request(app)
        .post('/api/auth/register')
        .send({
          ...newUser,
          passwordConfirmation: 'wrongpassword'
        });

      expect(response.status).toBe(400);
      expect(response.body.message).toBe('비밀번호가 일치하지 않습니다');
    });
  });

  describe('로그인 API', () => {
    const userCredentials = {
      email: 'test@example.com',
      password: 'password123'
    };

    beforeEach(async () => {
      await createTestUser(userCredentials);
    });

    it('유효한 자격 증명으로 로그인할 수 있어야 합니다', async () => {
      const response = await request(app)
        .post('/api/auth/login')
        .send(userCredentials);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('token');
      expect(response.body.user.email).toBe(userCredentials.email);
    });

    it('잘못된 이메일로 로그인을 시도하면 오류를 반환해야 합니다', async () => {
      const response = await request(app)
        .post('/api/auth/login')
        .send({
          email: 'wrong@example.com',
          password: userCredentials.password
        });

      expect(response.status).toBe(401);
      expect(response.body.message).toBe('잘못된 이메일 또는 비밀번호입니다');
    });

    it('잘못된 비밀번호로 로그인을 시도하면 오류를 반환해야 합니다', async () => {
      const response = await request(app)
        .post('/api/auth/login')
        .send({
          email: userCredentials.email,
          password: 'wrongpassword'
        });

      expect(response.status).toBe(401);
      expect(response.body.message).toBe('잘못된 이메일 또는 비밀번호입니다');
    });
  });

  describe('비밀번호 재설정 API', () => {
    let testUser: any;

    beforeEach(async () => {
      testUser = await createTestUser();
    });

    it('비밀번호 재설정 이메일을 요청할 수 있어야 합니다', async () => {
      const response = await request(app)
        .post('/api/auth/forgot-password')
        .send({ email: testUser.email });

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('비밀번호 재설정 이메일이 전송되었습니다');
    });

    it('존재하지 않는 이메일로 재설정을 요청하면 오류를 반환해야 합니다', async () => {
      const response = await request(app)
        .post('/api/auth/forgot-password')
        .send({ email: 'nonexistent@example.com' });

      expect(response.status).toBe(404);
      expect(response.body.message).toBe('해당 이메일을 가진 사용자를 찾을 수 없습니다');
    });

    it('유효한 토큰으로 비밀번호를 재설정할 수 있어야 합니다', async () => {
      const resetToken = 'valid-reset-token';
      await db('password_resets').insert({
        email: testUser.email,
        token: resetToken,
        expires_at: new Date(Date.now() + 3600000)
      });

      const response = await request(app)
        .post('/api/auth/reset-password')
        .send({
          token: resetToken,
          password: 'newpassword123',
          passwordConfirmation: 'newpassword123'
        });

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('비밀번호가 성공적으로 재설정되었습니다');
    });
  });

  describe('인증 미들웨어', () => {
    it('유효한 토큰으로 보호된 라우트에 접근할 수 있어야 합니다', async () => {
      const testUser = await createTestUser();
      const authToken = generateAuthToken(testUser);

      const response = await request(app)
        .get('/api/user/profile')
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('id', testUser.id);
    });

    it('토큰 없이 보호된 라우트에 접근하면 오류를 반환해야 합니다', async () => {
      const response = await request(app)
        .get('/api/user/profile');

      expect(response.status).toBe(401);
      expect(response.body.message).toBe('인증이 필요합니다');
    });

    it('만료된 토큰으로 접근하면 오류를 반환해야 합니다', async () => {
      const testUser = await createTestUser();
      const expiredToken = generateAuthToken(testUser, '-1h');

      const response = await request(app)
        .get('/api/user/profile')
        .set('Authorization', `Bearer ${expiredToken}`);

      expect(response.status).toBe(401);
      expect(response.body.message).toBe('토큰이 만료되었습니다');
    });
  });

  describe('권한 검사', () => {
    let adminUser: any;
    let regularUser: any;
    let adminToken: string;
    let userToken: string;

    beforeEach(async () => {
      adminUser = await createTestUser({ role: 'admin' });
      regularUser = await createTestUser({ role: 'user' });
      adminToken = generateAuthToken(adminUser);
      userToken = generateAuthToken(regularUser);
    });

    it('관리자는 관리자 전용 라우트에 접근할 수 있어야 합니다', async () => {
      const response = await request(app)
        .get('/api/admin/users')
        .set('Authorization', `Bearer ${adminToken}`);

      expect(response.status).toBe(200);
    });

    it('일반 사용자는 관리자 전용 라우트에 접근할 수 없어야 합니다', async () => {
      const response = await request(app)
        .get('/api/admin/users')
        .set('Authorization', `Bearer ${userToken}`);

      expect(response.status).toBe(403);
      expect(response.body.message).toBe('접근 권한이 없습니다');
    });
  });
}); 