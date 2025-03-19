import { describe, expect, it, beforeEach } from '@jest/globals';
import supertest from 'supertest';
import { app } from '@/app';
import { createTestUser, generateToken } from '@/tests/helpers/auth';
import { LogService } from '@/services/LogService';
import { MetricsService } from '@/services/MetricsService';
import { db } from '@/database';
import { readFileSync } from 'fs';
import { resolve } from 'path';

describe('로깅 및 모니터링 테스트', () => {
  let request: supertest.SuperTest<supertest.Test>;
  let testUser: any;
  let authToken: string;
  let logService: LogService;
  let metricsService: MetricsService;

  beforeEach(async () => {
    await db.migrate.latest();
    request = supertest(app);
    testUser = await createTestUser();
    authToken = generateToken(testUser);
    logService = new LogService();
    metricsService = new MetricsService();
  });

  describe('에러 로깅', () => {
    it('API 에러가 올바르게 로깅되어야 합니다', async () => {
      const response = await request
        .get('/api/products/999999')
        .set('Authorization', `Bearer ${authToken}`);

      expect(response.status).toBe(404);

      const logs = await logService.getRecentLogs('error');
      const lastLog = logs[0];

      expect(lastLog).toMatchObject({
        level: 'error',
        message: expect.stringContaining('Product not found'),
        timestamp: expect.any(String),
        path: '/api/products/999999',
        userId: testUser.id
      });
    });

    it('예외가 스택 트레이스와 함께 로깅되어야 합니다', async () => {
      const response = await request
        .post('/api/products')
        .set('Authorization', `Bearer ${authToken}`)
        .send({ invalidData: true });

      expect(response.status).toBe(400);

      const logs = await logService.getRecentLogs('error');
      const lastLog = logs[0];

      expect(lastLog).toMatchObject({
        level: 'error',
        stack: expect.stringContaining('Error'),
        metadata: expect.objectContaining({
          requestBody: expect.any(Object)
        })
      });
    });
  });

  describe('액세스 로깅', () => {
    it('API 요청이 올바르게 로깅되어야 합니다', async () => {
      await request
        .get('/api/products')
        .set('Authorization', `Bearer ${authToken}`);

      const logs = await logService.getRecentLogs('info');
      const accessLog = logs.find(log => 
        log.type === 'access' && 
        log.path === '/api/products'
      );

      expect(accessLog).toMatchObject({
        method: 'GET',
        statusCode: 200,
        responseTime: expect.any(Number),
        userAgent: expect.any(String),
        ip: expect.any(String)
      });
    });

    it('인증 실패가 로깅되어야 합니다', async () => {
      await request
        .get('/api/products')
        .set('Authorization', 'Bearer invalid-token');

      const logs = await logService.getRecentLogs('warn');
      const authFailLog = logs.find(log => 
        log.type === 'auth' && 
        log.message.includes('Authentication failed')
      );

      expect(authFailLog).toBeTruthy();
    });
  });

  describe('성능 메트릭', () => {
    it('API 응답 시간이 측정되어야 합니다', async () => {
      await request
        .get('/api/products')
        .set('Authorization', `Bearer ${authToken}`);

      const metrics = await metricsService.getMetrics('api_response_time');
      const productListMetric = metrics.find(m => 
        m.path === '/api/products' && 
        m.method === 'GET'
      );

      expect(productListMetric).toMatchObject({
        count: expect.any(Number),
        avg: expect.any(Number),
        max: expect.any(Number),
        min: expect.any(Number)
      });
    });

    it('데이터베이스 쿼리 성능이 측정되어야 합니다', async () => {
      await request
        .get('/api/products/search')
        .query({ category: 'A' })
        .set('Authorization', `Bearer ${authToken}`);

      const metrics = await metricsService.getMetrics('db_query_time');
      const searchQueryMetric = metrics.find(m => 
        m.query.includes('SELECT') && 
        m.query.includes('category')
      );

      expect(searchQueryMetric).toMatchObject({
        executionTime: expect.any(Number),
        timestamp: expect.any(String)
      });
    });
  });

  describe('시스템 모니터링', () => {
    it('메모리 사용량이 모니터링되어야 합니다', async () => {
      const metrics = await metricsService.getSystemMetrics();
      
      expect(metrics).toMatchObject({
        memory: {
          used: expect.any(Number),
          total: expect.any(Number),
          percentage: expect.any(Number)
        }
      });
    });

    it('CPU 사용량이 모니터링되어야 합니다', async () => {
      const metrics = await metricsService.getSystemMetrics();
      
      expect(metrics).toMatchObject({
        cpu: {
          usage: expect.any(Number),
          loadAverage: expect.any(Array)
        }
      });
    });
  });

  describe('알림', () => {
    it('심각한 에러 발생 시 알림이 전송되어야 합니다', async () => {
      const criticalError = new Error('Critical system error');
      await logService.logError(criticalError, { severity: 'critical' });

      const notifications = await logService.getNotifications();
      const errorNotification = notifications.find(n => 
        n.type === 'error' && 
        n.severity === 'critical'
      );

      expect(errorNotification).toBeTruthy();
    });

    it('성능 임계값 초과 시 알림이 전송되어야 합니다', async () => {
      // 높은 응답 시간 시뮬레이션
      await metricsService.recordMetric('api_response_time', {
        value: 5000, // 5초
        path: '/api/products',
        method: 'GET'
      });

      const notifications = await logService.getNotifications();
      const performanceNotification = notifications.find(n => 
        n.type === 'performance' && 
        n.message.includes('응답 시간 임계값 초과')
      );

      expect(performanceNotification).toBeTruthy();
    });
  });

  describe('로그 보존', () => {
    it('로그가 지정된 기간 동안 보존되어야 합니다', async () => {
      const oldDate = new Date();
      oldDate.setDate(oldDate.getDate() - 31); // 31일 전

      const oldLogs = await logService.getLogsByDate(oldDate);
      expect(oldLogs).toHaveLength(0); // 30일 이상된 로그는 삭제되어야 함
    });

    it('로그 파일이 적절한 크기로 유지되어야 합니다', async () => {
      const logFile = readFileSync(resolve(__dirname, '../../../logs/app.log'));
      const fileSizeInMB = logFile.length / (1024 * 1024);

      expect(fileSizeInMB).toBeLessThan(10); // 10MB 이하
    });
  });
}); 