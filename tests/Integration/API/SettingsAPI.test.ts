import { describe, expect, it, beforeEach, afterEach } from '@jest/globals';
import request from 'supertest';
import { app } from '@/app';
import { db } from '@/database';
import { createTestUser, generateAuthToken } from '@/tests/helpers/auth';

describe('Settings API Integration', () => {
  let authToken: string;
  let testUser: any;

  beforeEach(async () => {
    await db.migrate.latest();
    testUser = await createTestUser();
    authToken = generateAuthToken(testUser);
  });

  afterEach(async () => {
    await db.migrate.rollback();
  });

  describe('테마 설정 API', () => {
    const themeSettings = {
      mode: 'dark',
      colorScheme: '퍼플',
      isDense: true,
      fontSize: 16,
      borderRadius: 8
    };

    it('테마 설정을 가져올 수 있어야 합니다', async () => {
      const response = await request(app)
        .get('/api/settings/theme')
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('mode');
      expect(response.body).toHaveProperty('colorScheme');
    });

    it('테마 설정을 업데이트할 수 있어야 합니다', async () => {
      const response = await request(app)
        .put('/api/settings/theme')
        .set('Authorization', `Bearer ${authToken}`)
        .send(themeSettings);

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('테마 설정이 업데이트되었습니다');

      // 변경된 설정 확인
      const getResponse = await request(app)
        .get('/api/settings/theme')
        .set('Authorization', `Bearer ${authToken}`);

      expect(getResponse.body).toMatchObject(themeSettings);
    });
  });

  describe('언어 설정 API', () => {
    const languageSettings = {
      language: 'en',
      dateFormat: 'MM/DD/YYYY',
      timeFormat: '12',
      timezone: 'America/New_York',
      numberFormat: 'en-US'
    };

    it('언어 설정을 가져올 수 있어야 합니다', async () => {
      const response = await request(app)
        .get('/api/settings/language')
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('language');
      expect(response.body).toHaveProperty('dateFormat');
    });

    it('언어 설정을 업데이트할 수 있어야 합니다', async () => {
      const response = await request(app)
        .put('/api/settings/language')
        .set('Authorization', `Bearer ${authToken}`)
        .send(languageSettings);

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('언어 설정이 업데이트되었습니다');

      const getResponse = await request(app)
        .get('/api/settings/language')
        .set('Authorization', `Bearer ${authToken}`);

      expect(getResponse.body).toMatchObject(languageSettings);
    });
  });

  describe('API 설정 API', () => {
    const apiSettings = {
      coupangApiKey: 'new-test-key',
      naverApiKey: 'new-test-key',
      enableApiLogging: true,
      requestTimeout: 60000
    };

    it('API 설정을 가져올 수 있어야 합니다', async () => {
      const response = await request(app)
        .get('/api/settings/api')
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('coupangApiKey');
      expect(response.body).toHaveProperty('naverApiKey');
    });

    it('API 설정을 업데이트할 수 있어야 합니다', async () => {
      const response = await request(app)
        .put('/api/settings/api')
        .set('Authorization', `Bearer ${authToken}`)
        .send(apiSettings);

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('API 설정이 업데이트되었습니다');
    });

    it('API 키를 재생성할 수 있어야 합니다', async () => {
      const response = await request(app)
        .post('/api/settings/api/regenerate-key')
        .set('Authorization', `Bearer ${authToken}`)
        .send({ type: 'coupang' });

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('newKey');
      expect(response.body.newKey).not.toBe(apiSettings.coupangApiKey);
    });
  });

  describe('백업 API', () => {
    it('백업 목록을 가져올 수 있어야 합니다', async () => {
      const response = await request(app)
        .get('/api/settings/backups')
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(200);
      expect(Array.isArray(response.body)).toBe(true);
    });

    it('새 백업을 생성할 수 있어야 합니다', async () => {
      const response = await request(app)
        .post('/api/settings/backups')
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('id');
      expect(response.body).toHaveProperty('filename');
    });

    it('백업을 복원할 수 있어야 합니다', async () => {
      // 먼저 백업 생성
      const createResponse = await request(app)
        .post('/api/settings/backups')
        .set('Authorization', `Bearer ${authToken}`);

      const backupId = createResponse.body.id;

      const response = await request(app)
        .post(`/api/settings/backups/${backupId}/restore`)
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('백업이 복원되었습니다');
    });
  });

  describe('데이터 가져오기/내보내기 API', () => {
    it('데이터를 내보낼 수 있어야 합니다', async () => {
      const response = await request(app)
        .post('/api/settings/export')
        .set('Authorization', `Bearer ${authToken}`)
        .send({
          format: 'excel',
          options: {
            products: true,
            calculations: true
          }
        });

      expect(response.status).toBe(200);
      expect(response.headers['content-type']).toBe('application/vnd.ms-excel');
    });

    it('데이터를 가져올 수 있어야 합니다', async () => {
      const response = await request(app)
        .post('/api/settings/import')
        .set('Authorization', `Bearer ${authToken}`)
        .attach('file', Buffer.from('test data'), {
          filename: 'test.xlsx',
          contentType: 'application/vnd.ms-excel'
        });

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('데이터가 성공적으로 가져와졌습니다');
    });
  });
}); 