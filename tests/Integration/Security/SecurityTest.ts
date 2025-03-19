import { describe, expect, it, beforeEach } from '@jest/globals';
import supertest from 'supertest';
import { app } from '@/app';
import { createTestUser, generateToken } from '@/tests/helpers/auth';
import { ProductRepository } from '@/repositories/ProductRepository';
import { db } from '@/database';
import { hash } from 'bcrypt';

describe('보안 테스트', () => {
  let request: supertest.SuperTest<supertest.Test>;
  let testUser: any;
  let authToken: string;
  let productRepository: ProductRepository;

  beforeEach(async () => {
    await db.migrate.latest();
    request = supertest(app);
    testUser = await createTestUser();
    authToken = generateToken(testUser);
    productRepository = new ProductRepository();
  });

  describe('인증 및 권한 검사', () => {
    it('보호된 라우트에 인증 없이 접근할 수 없어야 합니다', async () => {
      const protectedRoutes = [
        '/api/products',
        '/api/calculator/profit',
        '/api/user/profile',
        '/api/settings'
      ];

      for (const route of protectedRoutes) {
        const response = await request.get(route);
        expect(response.status).toBe(401);
      }
    });

    it('만료된 토큰으로 접근할 수 없어야 합니다', async () => {
      const expiredToken = generateToken(testUser, { expiresIn: '0s' });
      
      const response = await request
        .get('/api/products')
        .set('Authorization', `Bearer ${expiredToken}`);

      expect(response.status).toBe(401);
      expect(response.body.message).toMatch(/만료된 토큰/);
    });

    it('권한이 없는 리소스에 접근할 수 없어야 합니다', async () => {
      const otherUser = await createTestUser({ email: 'other@test.com' });
      const product = await productRepository.create({
        name: '테스트 상품',
        userId: otherUser.id
      });

      const response = await request
        .delete(`/api/products/${product.id}`)
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(403);
    });
  });

  describe('입력 검증 및 살균', () => {
    it('XSS 취약점이 없어야 합니다', async () => {
      const xssPayload = '<script>alert("xss")</script>';
      
      const response = await request
        .post('/api/products')
        .set('Authorization', `Bearer ${authToken}`)
        .send({
          name: xssPayload,
          description: xssPayload
        });

      expect(response.status).toBe(201);
      expect(response.body.name).not.toContain('<script>');
      expect(response.body.description).not.toContain('<script>');
    });

    it('SQL 인젝션 취약점이 없어야 합니다', async () => {
      const sqlInjectionPayload = "'; DROP TABLE users; --";
      
      const response = await request
        .get('/api/products/search')
        .set('Authorization', `Bearer ${authToken}`)
        .query({ name: sqlInjectionPayload });

      expect(response.status).toBe(200);
      
      // 데이터베이스가 여전히 존재하는지 확인
      const usersExist = await db.schema.hasTable('users');
      expect(usersExist).toBeTruthy();
    });

    it('파일 업로드 보안이 적절해야 합니다', async () => {
      const maliciousFile = {
        fieldname: 'file',
        originalname: 'malicious.exe',
        encoding: '7bit',
        mimetype: 'application/x-msdownload',
        buffer: Buffer.from('malicious content'),
        size: 100
      };

      const response = await request
        .post('/api/products/import')
        .set('Authorization', `Bearer ${authToken}`)
        .attach('file', maliciousFile.buffer, maliciousFile.originalname);

      expect(response.status).toBe(400);
      expect(response.body.message).toMatch(/허용되지 않는 파일 형식/);
    });
  });

  describe('데이터 보안', () => {
    it('비밀번호가 안전하게 해시되어야 합니다', async () => {
      const password = 'TestPassword123!';
      
      const response = await request
        .post('/api/auth/register')
        .send({
          email: 'test@example.com',
          password
        });

      const user = await db('users')
        .where('email', 'test@example.com')
        .first();

      expect(user.password).not.toBe(password);
      expect(user.password).toMatch(/^\$2[aby]\$\d{2}\$/); // bcrypt 해시 패턴
    });

    it('민감한 정보가 응답에 포함되지 않아야 합니다', async () => {
      const response = await request
        .get('/api/user/profile')
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.body).not.toHaveProperty('password');
      expect(response.body).not.toHaveProperty('resetToken');
      expect(response.body).not.toHaveProperty('apiKeys');
    });
  });

  describe('Rate Limiting', () => {
    it('API 요청 제한이 적용되어야 합니다', async () => {
      const MAX_REQUESTS = 100;
      const responses = [];

      for (let i = 0; i < MAX_REQUESTS + 10; i++) {
        const response = await request
          .get('/api/products')
          .set('Authorization', `Bearer ${authToken}`);
        responses.push(response.status);
      }

      const tooManyRequests = responses.filter(status => status === 429);
      expect(tooManyRequests.length).toBeGreaterThan(0);
    });
  });

  describe('CSRF 보호', () => {
    it('CSRF 토큰이 없는 요청이 거부되어야 합니다', async () => {
      const response = await request
        .post('/api/user/profile')
        .set('Authorization', `Bearer ${authToken}`)
        .send({ name: '새 이름' });

      expect(response.status).toBe(403);
      expect(response.body.message).toMatch(/CSRF 토큰/);
    });
  });

  describe('보안 헤더', () => {
    it('적절한 보안 헤더가 설정되어야 합니다', async () => {
      const response = await request.get('/');

      expect(response.headers).toMatchObject({
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block',
        'x-content-type-options': 'nosniff',
        'strict-transport-security': 'max-age=31536000; includeSubDomains',
        'content-security-policy': expect.any(String)
      });
    });
  });
}); 