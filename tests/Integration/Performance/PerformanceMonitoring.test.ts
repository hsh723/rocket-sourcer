import { describe, expect, it, beforeEach } from '@jest/globals';
import supertest from 'supertest';
import { app } from '@/app';
import { createTestUser, generateToken } from '@/tests/helpers/auth';
import { PerformanceMonitor } from '@/services/PerformanceMonitor';
import { MetricsService } from '@/services/MetricsService';
import { AlertService } from '@/services/AlertService';

describe('성능 모니터링 테스트', () => {
  let request: supertest.SuperTest<supertest.Test>;
  let testUser: any;
  let authToken: string;
  let performanceMonitor: PerformanceMonitor;
  let metricsService: MetricsService;
  let alertService: AlertService;

  beforeEach(async () => {
    request = supertest(app);
    testUser = await createTestUser();
    authToken = generateToken(testUser);
    performanceMonitor = new PerformanceMonitor();
    metricsService = new MetricsService();
    alertService = new AlertService();
  });

  describe('API 성능 모니터링', () => {
    it('API 엔드포인트 응답 시간을 측정해야 합니다', async () => {
      const endpoints = [
        '/api/products',
        '/api/products/search',
        '/api/calculator/profit'
      ];

      for (const endpoint of endpoints) {
        const start = Date.now();
        
        await request
          .get(endpoint)
          .set('Authorization', `Bearer ${authToken}`);
        
        const responseTime = Date.now() - start;
        
        const metrics = await metricsService.getEndpointMetrics(endpoint);
        expect(metrics).toMatchObject({
          avgResponseTime: expect.any(Number),
          p95ResponseTime: expect.any(Number),
          p99ResponseTime: expect.any(Number)
        });

        // 응답 시간이 허용 범위 내인지 확인
        expect(responseTime).toBeLessThan(1000); // 1초 이내
      }
    });

    it('동시 요청 처리 성능을 측정해야 합니다', async () => {
      const CONCURRENT_REQUESTS = 50;
      const requests = Array.from({ length: CONCURRENT_REQUESTS }, () =>
        request
          .get('/api/products')
          .set('Authorization', `Bearer ${authToken}`)
      );

      const start = Date.now();
      await Promise.all(requests);
      const totalTime = Date.now() - start;

      const metrics = await metricsService.getConcurrencyMetrics();
      expect(metrics).toMatchObject({
        avgConcurrentRequests: expect.any(Number),
        maxConcurrentRequests: expect.any(Number),
        successRate: expect.any(Number)
      });

      // 총 처리 시간이 허용 범위 내인지 확인
      expect(totalTime).toBeLessThan(5000); // 5초 이내
    });
  });

  describe('리소스 사용량 모니터링', () => {
    it('메모리 사용량을 모니터링해야 합니다', async () => {
      const memoryMetrics = await performanceMonitor.getMemoryMetrics();
      
      expect(memoryMetrics).toMatchObject({
        heapUsed: expect.any(Number),
        heapTotal: expect.any(Number),
        external: expect.any(Number),
        rss: expect.any(Number)
      });

      // 메모리 누수 검사
      const initialMemory = process.memoryUsage().heapUsed;
      await request
        .get('/api/products')
        .set('Authorization', `Bearer ${authToken}`);
      const finalMemory = process.memoryUsage().heapUsed;
      
      // 메모리 증가가 허용 범위 내인지 확인
      expect(finalMemory - initialMemory).toBeLessThan(1024 * 1024); // 1MB 이내
    });

    it('CPU 사용량을 모니터링해야 합니다', async () => {
      const cpuMetrics = await performanceMonitor.getCPUMetrics();
      
      expect(cpuMetrics).toMatchObject({
        usage: expect.any(Number),
        loadAverage: expect.any(Array)
      });

      // CPU 사용량이 허용 범위 내인지 확인
      expect(cpuMetrics.usage).toBeLessThan(80); // 80% 이하
    });
  });

  describe('데이터베이스 성능 모니터링', () => {
    it('쿼리 실행 시간을 모니터링해야 합니다', async () => {
      await request
        .get('/api/products/search')
        .query({ category: 'A' })
        .set('Authorization', `Bearer ${authToken}`);

      const queryMetrics = await performanceMonitor.getQueryMetrics();
      
      expect(queryMetrics).toMatchObject({
        avgExecutionTime: expect.any(Number),
        slowQueries: expect.any(Array)
      });

      // 느린 쿼리가 없는지 확인
      expect(queryMetrics.slowQueries).toHaveLength(0);
    });

    it('커넥션 풀 상태를 모니터링해야 합니다', async () => {
      const poolMetrics = await performanceMonitor.getConnectionPoolMetrics();
      
      expect(poolMetrics).toMatchObject({
        active: expect.any(Number),
        idle: expect.any(Number),
        waiting: expect.any(Number)
      });

      // 커넥션 풀이 정상 범위 내인지 확인
      expect(poolMetrics.waiting).toBe(0);
    });
  });

  describe('성능 알림', () => {
    it('성능 임계값 초과 시 알림이 발생해야 합니다', async () => {
      // 높은 응답 시간 시뮬레이션
      await metricsService.recordMetric('api_response_time', {
        value: 5000,
        endpoint: '/api/products'
      });

      const alerts = await alertService.getActiveAlerts();
      const performanceAlert = alerts.find(a => 
        a.type === 'performance' &&
        a.message.includes('응답 시간 임계값 초과')
      );

      expect(performanceAlert).toBeTruthy();
    });

    it('리소스 사용량 임계값 초과 시 알림이 발생해야 합니다', async () => {
      // 높은 메모리 사용량 시뮬레이션
      await metricsService.recordMetric('memory_usage', {
        value: 90,
        type: 'heap'
      });

      const alerts = await alertService.getActiveAlerts();
      const resourceAlert = alerts.find(a => 
        a.type === 'resource' &&
        a.message.includes('메모리 사용량 임계값 초과')
      );

      expect(resourceAlert).toBeTruthy();
    });
  });

  describe('성능 보고서', () => {
    it('성능 지표 보고서를 생성해야 합니다', async () => {
      const report = await performanceMonitor.generatePerformanceReport();
      
      expect(report).toMatchObject({
        apiPerformance: expect.any(Object),
        resourceUsage: expect.any(Object),
        databaseMetrics: expect.any(Object),
        alerts: expect.any(Array),
        recommendations: expect.any(Array)
      });
    });

    it('성능 추세를 분석해야 합니다', async () => {
      const trends = await performanceMonitor.analyzePerformanceTrends({
        startDate: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000), // 7일 전
        endDate: new Date()
      });

      expect(trends).toMatchObject({
        responseTimeTrend: expect.any(Array),
        resourceUsageTrend: expect.any(Array),
        anomalies: expect.any(Array)
      });
    });
  });
}); 