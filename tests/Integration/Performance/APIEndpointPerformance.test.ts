import { describe, expect, it, beforeEach, afterEach } from '@jest/globals';
import supertest from 'supertest';
import { app } from '@/app';
import { createTestUser, generateToken } from '@/tests/helpers/auth';
import { ProductRepository } from '@/repositories/ProductRepository';
import { db } from '@/database';

describe('API 엔드포인트 성능 테스트', () => {
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

  afterEach(async () => {
    await db.migrate.rollback();
  });

  describe('제품 API 성능', () => {
    beforeEach(async () => {
      // 테스트용 대량 데이터 생성
      const products = Array.from({ length: 500 }, (_, i) => ({
        name: `제품 ${i + 1}`,
        price: Math.floor(Math.random() * 100000) + 1000,
        cost: Math.floor(Math.random() * 50000) + 500,
        category: ['A', 'B', 'C'][Math.floor(Math.random() * 3)],
        userId: testUser.id
      }));

      await Promise.all(products.map(p => productRepository.create(p)));
    });

    it('제품 목록 조회가 빠르게 응답해야 합니다', async () => {
      const start = Date.now();

      const response = await request
        .get('/api/products')
        .query({ page: 1, pageSize: 50 })
        .set('Authorization', `Bearer ${authToken}`);

      const end = Date.now();
      const executionTime = end - start;

      expect(response.status).toBe(200);
      expect(response.body.products).toHaveLength(50);
      expect(executionTime).toBeLessThan(300); // 300ms 이내 응답
    });

    it('필터링된 제품 검색이 효율적으로 동작해야 합니다', async () => {
      const start = Date.now();

      const response = await request
        .get('/api/products/search')
        .query({
          category: 'A',
          minPrice: 10000,
          maxPrice: 50000,
          page: 1,
          pageSize: 20
        })
        .set('Authorization', `Bearer ${authToken}`);

      const end = Date.now();
      const executionTime = end - start;

      expect(response.status).toBe(200);
      expect(executionTime).toBeLessThan(500); // 500ms 이내 응답
    });

    it('제품 통계 API가 빠르게 응답해야 합니다', async () => {
      const start = Date.now();

      const response = await request
        .get('/api/products/stats')
        .set('Authorization', `Bearer ${authToken}`);

      const end = Date.now();
      const executionTime = end - start;

      expect(response.status).toBe(200);
      expect(executionTime).toBeLessThan(1000); // 1초 이내 응답
    });
  });

  describe('수익성 계산 API 성능', () => {
    it('수익성 계산이 효율적으로 수행되어야 합니다', async () => {
      const calculationData = {
        productPrice: 50000,
        productCost: 30000,
        shippingCost: 3000,
        marketplaceFee: 0.1,
        marketingCost: 2000,
        expectedSales: 100
      };

      const start = Date.now();

      const response = await request
        .post('/api/calculator/profit')
        .send(calculationData)
        .set('Authorization', `Bearer ${authToken}`);

      const end = Date.now();
      const executionTime = end - start;

      expect(response.status).toBe(200);
      expect(executionTime).toBeLessThan(200); // 200ms 이내 응답
    });

    it('대량 수익성 시뮬레이션이 적절한 시간 내에 완료되어야 합니다', async () => {
      const simulationData = {
        basePrice: 50000,
        baseCost: 30000,
        priceRange: { min: -5000, max: 5000, step: 1000 },
        salesRange: { min: 50, max: 200, step: 25 }
      };

      const start = Date.now();

      const response = await request
        .post('/api/calculator/simulation')
        .send(simulationData)
        .set('Authorization', `Bearer ${authToken}`);

      const end = Date.now();
      const executionTime = end - start;

      expect(response.status).toBe(200);
      expect(executionTime).toBeLessThan(1000); // 1초 이내 응답
    });
  });

  describe('동시성 및 부하 테스트', () => {
    it('다수의 동시 요청을 처리할 수 있어야 합니다', async () => {
      const CONCURRENT_REQUESTS = 20;
      const start = Date.now();

      const requests = Array.from({ length: CONCURRENT_REQUESTS }, () =>
        request
          .get('/api/products')
          .query({ page: 1, pageSize: 10 })
          .set('Authorization', `Bearer ${authToken}`)
      );

      const responses = await Promise.all(requests);
      const end = Date.now();
      const executionTime = end - start;

      expect(responses.every(r => r.status === 200)).toBeTruthy();
      expect(executionTime).toBeLessThan(2000); // 2초 이내 응답
    });

    it('대량의 연속적인 요청을 안정적으로 처리해야 합니다', async () => {
      const TOTAL_REQUESTS = 50;
      const results = [];
      const start = Date.now();

      for (let i = 0; i < TOTAL_REQUESTS; i++) {
        const response = await request
          .get('/api/products')
          .query({ page: 1, pageSize: 10 })
          .set('Authorization', `Bearer ${authToken}`);
        
        results.push(response.status);
      }

      const end = Date.now();
      const executionTime = end - start;
      const averageTime = executionTime / TOTAL_REQUESTS;

      expect(results.every(status => status === 200)).toBeTruthy();
      expect(averageTime).toBeLessThan(100); // 요청당 평균 100ms 이내
    });
  });

  describe('에러 처리 성능', () => {
    it('잘못된 요청에 대해 빠르게 응답해야 합니다', async () => {
      const start = Date.now();

      const response = await request
        .get('/api/products/-1')
        .set('Authorization', `Bearer ${authToken}`);

      const end = Date.now();
      const executionTime = end - start;

      expect(response.status).toBe(404);
      expect(executionTime).toBeLessThan(50); // 50ms 이내 응답
    });

    it('인증 실패를 빠르게 처리해야 합니다', async () => {
      const start = Date.now();

      const response = await request
        .get('/api/products')
        .set('Authorization', 'Bearer invalid-token');

      const end = Date.now();
      const executionTime = end - start;

      expect(response.status).toBe(401);
      expect(executionTime).toBeLessThan(50); // 50ms 이내 응답
    });
  });
}); 