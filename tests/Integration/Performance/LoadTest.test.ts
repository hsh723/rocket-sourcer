import { describe, expect, it, beforeEach } from '@jest/globals';
import supertest from 'supertest';
import { app } from '@/app';
import { createTestUser, generateToken } from '@/tests/helpers/auth';
import { LoadTestService } from '@/services/LoadTestService';
import { MetricsService } from '@/services/MetricsService';
import { PerformanceMonitor } from '@/services/PerformanceMonitor';

describe('부하 테스트', () => {
  let request: supertest.SuperTest<supertest.Test>;
  let testUser: any;
  let authToken: string;
  let loadTestService: LoadTestService;
  let metricsService: MetricsService;
  let performanceMonitor: PerformanceMonitor;

  beforeEach(async () => {
    request = supertest(app);
    testUser = await createTestUser();
    authToken = generateToken(testUser);
    loadTestService = new LoadTestService();
    metricsService = new MetricsService();
    performanceMonitor = new PerformanceMonitor();
  });

  describe('기본 부하 테스트', () => {
    it('일정 수준의 동시 사용자를 처리할 수 있어야 합니다', async () => {
      const testConfig = {
        virtualUsers: 100,
        duration: 60, // 60초
        targetEndpoint: '/api/products'
      };

      const results = await loadTestService.runLoadTest(testConfig);

      expect(results).toMatchObject({
        totalRequests: expect.any(Number),
        successRate: expect.any(Number),
        avgResponseTime: expect.any(Number),
        errorRate: expect.any(Number)
      });

      expect(results.successRate).toBeGreaterThan(95);
      expect(results.avgResponseTime).toBeLessThan(1000);
      expect(results.errorRate).toBeLessThan(5);
    });

    it('검색 기능이 부하 상황에서도 정상 동작해야 합니다', async () => {
      const testConfig = {
        virtualUsers: 50,
        duration: 30,
        targetEndpoint: '/api/products/search',
        params: { category: 'A', minPrice: 1000, maxPrice: 50000 }
      };

      const results = await loadTestService.runLoadTest(testConfig);

      expect(results.successRate).toBeGreaterThan(95);
      expect(results.avgResponseTime).toBeLessThan(2000);
    });
  });

  describe('스트레스 테스트', () => {
    it('급격한 트래픽 증가를 처리할 수 있어야 합니다', async () => {
      const testConfig = {
        initialUsers: 10,
        maxUsers: 500,
        incrementUsers: 50,
        incrementInterval: 10, // 10초마다 사용자 증가
        targetEndpoint: '/api/products'
      };

      const results = await loadTestService.runStressTest(testConfig);

      expect(results).toMatchObject({
        breakingPoint: expect.any(Number),
        maxConcurrentUsers: expect.any(Number),
        errorRateByLoad: expect.any(Object)
      });

      // 최소 200명의 동시 사용자를 처리할 수 있어야 함
      expect(results.breakingPoint).toBeGreaterThan(200);
    });

    it('장시간 부하를 견딜 수 있어야 합니다', async () => {
      const testConfig = {
        virtualUsers: 100,
        duration: 300, // 5분
        targetEndpoint: '/api/products'
      };

      const results = await loadTestService.runEnduranceTest(testConfig);

      expect(results).toMatchObject({
        memoryLeaks: expect.any(Boolean),
        responseTimeDegradation: expect.any(Number),
        errorRateOverTime: expect.any(Array)
      });

      expect(results.memoryLeaks).toBeFalsy();
      expect(results.responseTimeDegradation).toBeLessThan(20); // 20% 이하의 성능 저하
    });
  });

  describe('데이터베이스 부하 테스트', () => {
    it('대량의 동시 쿼리를 처리할 수 있어야 합니다', async () => {
      const testConfig = {
        concurrentQueries: 100,
        queryType: 'search',
        duration: 30
      };

      const results = await loadTestService.runDatabaseLoadTest(testConfig);

      expect(results).toMatchObject({
        avgQueryTime: expect.any(Number),
        maxQueryTime: expect.any(Number),
        queryTimeoutRate: expect.any(Number),
        connectionErrors: expect.any(Number)
      });

      expect(results.queryTimeoutRate).toBeLessThan(1); // 1% 이하의 타임아웃
      expect(results.connectionErrors).toBe(0);
    });
  });

  describe('캐시 성능 테스트', () => {
    it('캐시가 부하를 효과적으로 감소시켜야 합니다', async () => {
      const testConfig = {
        virtualUsers: 200,
        duration: 60,
        targetEndpoint: '/api/products',
        withCache: true
      };

      const resultsWithCache = await loadTestService.runLoadTest(testConfig);
      
      testConfig.withCache = false;
      const resultsWithoutCache = await loadTestService.runLoadTest(testConfig);

      // 캐시 사용 시 응답 시간이 50% 이상 개선되어야 함
      expect(resultsWithCache.avgResponseTime).toBeLessThan(
        resultsWithoutCache.avgResponseTime * 0.5
      );
    });
  });

  describe('복구 테스트', () => {
    it('부하 후 시스템이 정상 상태로 복구되어야 합니다', async () => {
      // 높은 부하 생성
      await loadTestService.runStressTest({
        initialUsers: 10,
        maxUsers: 300,
        incrementUsers: 50,
        incrementInterval: 5,
        targetEndpoint: '/api/products'
      });

      // 복구 시간 측정
      const recoveryResults = await loadTestService.measureRecoveryTime();

      expect(recoveryResults).toMatchObject({
        recoveryTimeSeconds: expect.any(Number),
        memoryRecovered: expect.any(Boolean),
        responseTimeNormalized: expect.any(Boolean)
      });

      expect(recoveryResults.recoveryTimeSeconds).toBeLessThan(60); // 1분 이내 복구
      expect(recoveryResults.memoryRecovered).toBeTruthy();
      expect(recoveryResults.responseTimeNormalized).toBeTruthy();
    });
  });

  describe('성능 모니터링', () => {
    it('부하 테스트 중 시스템 메트릭을 수집해야 합니다', async () => {
      const testExecution = loadTestService.runLoadTest({
        virtualUsers: 100,
        duration: 30,
        targetEndpoint: '/api/products'
      });

      const metrics = await performanceMonitor.collectMetricsDuringTest(testExecution);

      expect(metrics).toMatchObject({
        cpuUsage: expect.any(Array),
        memoryUsage: expect.any(Array),
        responseTimes: expect.any(Array),
        errorRates: expect.any(Array)
      });

      // 리소스 사용량이 임계치를 넘지 않아야 함
      const maxCpuUsage = Math.max(...metrics.cpuUsage);
      const maxMemoryUsage = Math.max(...metrics.memoryUsage);

      expect(maxCpuUsage).toBeLessThan(90); // 90% CPU 사용률 이하
      expect(maxMemoryUsage).toBeLessThan(85); // 85% 메모리 사용률 이하
    });
  });
}); 