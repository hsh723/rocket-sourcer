import { describe, expect, it, beforeEach } from '@jest/globals';
import supertest from 'supertest';
import { app } from '@/app';
import { createTestUser, generateToken } from '@/tests/helpers/auth';
import { ProductRepository } from '@/repositories/ProductRepository';
import { CalculatorService } from '@/services/CalculatorService';
import { PerformanceBenchmark } from '@/services/PerformanceBenchmark';
import { db } from '@/database';
import { redis } from '@/cache';

describe('성능 벤치마크 테스트', () => {
  let request: supertest.SuperTest<supertest.Test>;
  let testUser: any;
  let authToken: string;
  let productRepository: ProductRepository;
  let calculatorService: CalculatorService;
  let performanceBenchmark: PerformanceBenchmark;

  beforeEach(async () => {
    await db.migrate.latest();
    request = supertest(app);
    testUser = await createTestUser();
    authToken = generateToken(testUser);
    productRepository = new ProductRepository();
    calculatorService = new CalculatorService();
    performanceBenchmark = new PerformanceBenchmark();
    await redis.flushall();
    
    // 테스트 데이터 생성
    await Promise.all(
      Array.from({ length: 100 }, (_, i) => 
        productRepository.create({
          name: `벤치마크 제품 ${i + 1}`,
          price: Math.floor(Math.random() * 100000) + 1000,
          cost: Math.floor(Math.random() * 50000) + 500,
          category: ['A', 'B', 'C'][Math.floor(Math.random() * 3)],
          userId: testUser.id
        })
      )
    );
  });

  describe('API 엔드포인트 벤치마크', () => {
    it('제품 목록 조회 성능이 기준을 충족해야 합니다', async () => {
      const benchmark = await performanceBenchmark.measureEndpoint({
        method: 'GET',
        url: '/api/products',
        headers: { Authorization: `Bearer ${authToken}` },
        iterations: 50
      });
      
      expect(benchmark.avgResponseTime).toBeLessThan(100); // 100ms 이하
      expect(benchmark.p95ResponseTime).toBeLessThan(200); // 200ms 이하
      expect(benchmark.successRate).toBe(1.0); // 100% 성공률
    });

    it('제품 검색 성능이 기준을 충족해야 합니다', async () => {
      const benchmark = await performanceBenchmark.measureEndpoint({
        method: 'GET',
        url: '/api/products/search?category=A',
        headers: { Authorization: `Bearer ${authToken}` },
        iterations: 30
      });
      
      expect(benchmark.avgResponseTime).toBeLessThan(150); // 150ms 이하
      expect(benchmark.p95ResponseTime).toBeLessThan(250); // 250ms 이하
      expect(benchmark.successRate).toBe(1.0); // 100% 성공률
    });

    it('수익성 계산 성능이 기준을 충족해야 합니다', async () => {
      const calculationData = {
        productPrice: 50000,
        productCost: 30000,
        shippingCost: 3000,
        marketplaceFee: 0.1,
        marketingCost: 2000,
        expectedSales: 100
      };
      
      const benchmark = await performanceBenchmark.measureEndpoint({
        method: 'POST',
        url: '/api/calculator/profit',
        headers: { Authorization: `Bearer ${authToken}` },
        body: calculationData,
        iterations: 20
      });
      
      expect(benchmark.avgResponseTime).toBeLessThan(100); // 100ms 이하
      expect(benchmark.p95ResponseTime).toBeLessThan(150); // 150ms 이하
      expect(benchmark.successRate).toBe(1.0); // 100% 성공률
    });
  });

  describe('데이터베이스 쿼리 벤치마크', () => {
    it('제품 조회 쿼리 성능이 기준을 충족해야 합니다', async () => {
      const benchmark = await performanceBenchmark.measureDatabaseQuery({
        queryFn: () => productRepository.findAll(),
        iterations: 50
      });
      
      expect(benchmark.avgExecutionTime).toBeLessThan(50); // 50ms 이하
      expect(benchmark.p95ExecutionTime).toBeLessThan(100); // 100ms 이하
    });

    it('필터링 쿼리 성능이 기준을 충족해야 합니다', async () => {
      const benchmark = await performanceBenchmark.measureDatabaseQuery({
        queryFn: () => productRepository.findWithFilters({
          category: 'A',
          minPrice: 10000,
          maxPrice: 50000
        }),
        iterations: 30
      });
      
      expect(benchmark.avgExecutionTime).toBeLessThan(80); // 80ms 이하
      expect(benchmark.p95ExecutionTime).toBeLessThan(120); // 120ms 이하
    });

    it('통계 쿼리 성능이 기준을 충족해야 합니다', async () => {
      const benchmark = await performanceBenchmark.measureDatabaseQuery({
        queryFn: () => productRepository.getCategoryStats(),
        iterations: 20
      });
      
      expect(benchmark.avgExecutionTime).toBeLessThan(100); // 100ms 이하
      expect(benchmark.p95ExecutionTime).toBeLessThan(150); // 150ms 이하
    });
  });

  describe('캐시 성능 벤치마크', () => {
    it('캐시 읽기 성능이 기준을 충족해야 합니다', async () => {
      // 캐시 데이터 설정
      const cacheKey = 'benchmark:test:key';
      const cacheData = { id: 1, name: '테스트 데이터', value: Array(1000).fill('x').join('') };
      await redis.set(cacheKey, JSON.stringify(cacheData));
      
      const benchmark = await performanceBenchmark.measureCacheOperation({
        operation: 'read',
        key: cacheKey,
        iterations: 1000
      });
      
      expect(benchmark.avgExecutionTime).toBeLessThan(5); // 5ms 이하
      expect(benchmark.p95ExecutionTime).toBeLessThan(10); // 10ms 이하
      expect(benchmark.operationsPerSecond).toBeGreaterThan(5000); // 초당 5000회 이상
    });

    it('캐시 쓰기 성능이 기준을 충족해야 합니다', async () => {
      const cacheKey = 'benchmark:test:write';
      const cacheData = { id: 1, name: '테스트 데이터', value: Array(1000).fill('x').join('') };
      
      const benchmark = await performanceBenchmark.measureCacheOperation({
        operation: 'write',
        key: cacheKey,
        value: cacheData,
        iterations: 500
      });
      
      expect(benchmark.avgExecutionTime).toBeLessThan(10); // 10ms 이하
      expect(benchmark.p95ExecutionTime).toBeLessThan(20); // 20ms 이하
      expect(benchmark.operationsPerSecond).toBeGreaterThan(1000); // 초당 1000회 이상
    });

    it('캐시 히트율이 기준을 충족해야 합니다', async () => {
      // 제품 데이터 캐싱
      const products = await productRepository.findAll();
      await Promise.all(
        products.map(product => 
          redis.set(`product:${product.id}`, JSON.stringify(product))
        )
      );
      
      // 캐시 히트율 측정
      const cacheHitRate = await performanceBenchmark.measureCacheHitRate({
        keyPattern: 'product:*',
        iterations: 1000
      });
      
      expect(cacheHitRate).toBeGreaterThan(0.95); // 95% 이상
    });
  });

  describe('비즈니스 로직 벤치마크', () => {
    it('수익성 계산 로직 성능이 기준을 충족해야 합니다', async () => {
      const calculationData = {
        productPrice: 50000,
        productCost: 30000,
        shippingCost: 3000,
        marketplaceFee: 0.1,
        marketingCost: 2000,
        expectedSales: 100
      };
      
      const benchmark = await performanceBenchmark.measureFunction({
        fn: () => calculatorService.calculateProfit(calculationData),
        iterations: 1000
      });
      
      expect(benchmark.avgExecutionTime).toBeLessThan(5); // 5ms 이하
      expect(benchmark.p95ExecutionTime).toBeLessThan(10); // 10ms 이하
      expect(benchmark.operationsPerSecond).toBeGreaterThan(10000); // 초당 10000회 이상
    });

    it('ROI 시뮬레이션 성능이 기준을 충족해야 합니다', async () => {
      const simulationData = {
        initialInvestment: 1000000,
        monthlyProfit: 200000,
        growthRate: 0.05,
        months: 24
      };
      
      const benchmark = await performanceBenchmark.measureFunction({
        fn: () => calculatorService.simulateROI(simulationData),
        iterations: 500
      });
      
      expect(benchmark.avgExecutionTime).toBeLessThan(10); // 10ms 이하
      expect(benchmark.p95ExecutionTime).toBeLessThan(20); // 20ms 이하
      expect(benchmark.operationsPerSecond).toBeGreaterThan(5000); // 초당 5000회 이상
    });

    it('대량 시뮬레이션 성능이 기준을 충족해야 합니다', async () => {
      const simulationConfig = {
        basePrice: 50000,
        baseCost: 30000,
        priceRange: { min: -5000, max: 5000, step: 1000 },
        salesRange: { min: 50, max: 200, step: 25 }
      };
      
      const benchmark = await performanceBenchmark.measureFunction({
        fn: () => calculatorService.runBulkSimulation(simulationConfig),
        iterations: 10
      });
      
      expect(benchmark.avgExecutionTime).toBeLessThan(500); // 500ms 이하
      expect(benchmark.p95ExecutionTime).toBeLessThan(800); // 800ms 이하
    });
  });

  describe('메모리 사용량 벤치마크', () => {
    it('대량 데이터 처리 시 메모리 사용량이 기준을 충족해야 합니다', async () => {
      const memoryBenchmark = await performanceBenchmark.measureMemoryUsage({
        fn: async () => {
          // 대량의 제품 데이터 생성 및 처리
          const products = await Promise.all(
            Array.from({ length: 1000 }, (_, i) => 
              productRepository.create({
                name: `메모리 테스트 제품 ${i + 1}`,
                price: Math.floor(Math.random() * 100000) + 1000,
                cost: Math.floor(Math.random() * 50000) + 500,
                category: ['A', 'B', 'C'][Math.floor(Math.random() * 3)],
                userId: testUser.id
              })
            )
          );
          
          // 데이터 처리
          const results = await Promise.all(
            products.map(product => 
              calculatorService.calculateProfit({
                productPrice: product.price,
                productCost: product.cost,
                shippingCost: 3000,
                marketplaceFee: 0.1,
                marketingCost: 2000,
                expectedSales: 100
              })
            )
          );
          
          return results;
        }
      });
      
      expect(memoryBenchmark.peakMemoryUsage).toBeLessThan(100 * 1024 * 1024); // 100MB 이하
      expect(memoryBenchmark.memoryLeaked).toBeFalsy();
    });
  });

  describe('CPU 사용량 벤치마크', () => {
    it('복잡한 계산 시 CPU 사용량이 기준을 충족해야 합니다', async () => {
      const cpuBenchmark = await performanceBenchmark.measureCPUUsage({
        fn: async () => {
          // CPU 집약적인 작업 수행
          const results = [];
          for (let i = 0; i < 100; i++) {
            const simulationConfig = {
              basePrice: 50000 + (i * 100),
              baseCost: 30000 + (i * 50),
              priceRange: { min: -5000, max: 5000, step: 500 },
              salesRange: { min: 50, max: 200, step: 10 }
            };
            
            results.push(await calculatorService.runBulkSimulation(simulationConfig));
          }
          return results;
        }
      });
      
      expect(cpuBenchmark.avgCPUUsage).toBeLessThan(80); // 80% 이하
      expect(cpuBenchmark.peakCPUUsage).toBeLessThan(95); // 95% 이하
    });
  });

  describe('확장성 벤치마크', () => {
    it('데이터 크기에 따른 성능 변화가 선형적이어야 합니다', async () => {
      const dataSizes = [10, 100, 1000];
      const results = [];
      
      for (const size of dataSizes) {
        const benchmark = await performanceBenchmark.measureScalability({
          fn: async () => {
            const products = await Promise.all(
              Array.from({ length: size }, (_, i) => 
                productRepository.create({
                  name: `확장성 테스트 제품 ${i + 1}`,
                  price: Math.floor(Math.random() * 100000) + 1000,
                  cost: Math.floor(Math.random() * 50000) + 500,
                  category: ['A', 'B', 'C'][Math.floor(Math.random() * 3)],
                  userId: testUser.id
                })
              )
            );
            
            return productRepository.findAll();
          },
          dataSize: size
        });
        
        results.push(benchmark);
      }
      
      // 성능이 데이터 크기에 비례하여 선형적으로 증가하는지 확인
      const scalabilityFactor = results[2].avgExecutionTime / results[0].avgExecutionTime;
      const expectedFactor = dataSizes[2] / dataSizes[0];
      
      // 성능 저하가 데이터 크기 증가보다 작아야 함 (서브리니어 확장성)
      expect(scalabilityFactor).toBeLessThan(expectedFactor);
    });
  });

  describe('비교 벤치마크', () => {
    it('캐시 사용 시 성능 향상이 기준을 충족해야 합니다', async () => {
      // 캐시 없이 실행
      const withoutCacheBenchmark = await performanceBenchmark.measureEndpoint({
        method: 'GET',
        url: '/api/products?useCache=false',
        headers: { Authorization: `Bearer ${authToken}` },
        iterations: 30
      });
      
      // 캐시 사용하여 실행
      const withCacheBenchmark = await performanceBenchmark.measureEndpoint({
        method: 'GET',
        url: '/api/products?useCache=true',
        headers: { Authorization: `Bearer ${authToken}` },
        iterations: 30
      });
      
      // 캐시 사용 시 성능 향상 계산
      const performanceImprovement = 1 - (withCacheBenchmark.avgResponseTime / withoutCacheBenchmark.avgResponseTime);
      
      expect(performanceImprovement).toBeGreaterThan(0.7); // 70% 이상 성능 향상
    });

    it('인덱스 사용 시 성능 향상이 기준을 충족해야 합니다', async () => {
      // 인덱스 없이 실행 (EXPLAIN 쿼리 사용)
      const withoutIndexBenchmark = await performanceBenchmark.measureDatabaseQuery({
        queryFn: () => db.raw(`
          EXPLAIN ANALYZE
          SELECT * FROM products
          WHERE category = 'A' AND price BETWEEN 10000 AND 50000
          AND name LIKE '%벤치마크%'
        `),
        iterations: 10
      });
      
      // 인덱스 생성
      await db.schema.table('products', table => {
        table.index(['category', 'price']);
      });
      
      // 인덱스 사용하여 실행
      const withIndexBenchmark = await performanceBenchmark.measureDatabaseQuery({
        queryFn: () => db.raw(`
          EXPLAIN ANALYZE
          SELECT * FROM products
          WHERE category = 'A' AND price BETWEEN 10000 AND 50000
          AND name LIKE '%벤치마크%'
        `),
        iterations: 10
      });
      
      // 인덱스 사용 시 성능 향상 계산
      const performanceImprovement = 1 - (withIndexBenchmark.avgExecutionTime / withoutIndexBenchmark.avgExecutionTime);
      
      expect(performanceImprovement).toBeGreaterThan(0.5); // 50% 이상 성능 향상
    });
  });
}); 