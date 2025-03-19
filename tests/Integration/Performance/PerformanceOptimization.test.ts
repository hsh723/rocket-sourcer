import { describe, expect, it, beforeEach, afterEach } from '@jest/globals';
import { ProductRepository } from '@/repositories/ProductRepository';
import { CacheService } from '@/services/CacheService';
import { createTestUser } from '@/tests/helpers/auth';
import { Redis } from 'ioredis';
import { db } from '@/database';

describe('성능 최적화 테스트', () => {
  let productRepository: ProductRepository;
  let cacheService: CacheService;
  let redis: Redis;
  let testUser: any;

  beforeEach(async () => {
    await db.migrate.latest();
    testUser = await createTestUser();
    redis = new Redis(process.env.REDIS_URL);
    cacheService = new CacheService(redis);
    productRepository = new ProductRepository();
  });

  afterEach(async () => {
    await redis.flushall();
    await redis.quit();
    await db.migrate.rollback();
  });

  describe('대량 데이터 처리', () => {
    const BATCH_SIZE = 1000;

    beforeEach(async () => {
      // 테스트용 대량 데이터 생성
      const products = Array.from({ length: BATCH_SIZE }, (_, i) => ({
        name: `제품 ${i + 1}`,
        price: Math.floor(Math.random() * 100000) + 1000,
        cost: Math.floor(Math.random() * 50000) + 500,
        category: ['A', 'B', 'C'][Math.floor(Math.random() * 3)],
        userId: testUser.id
      }));

      await Promise.all(products.map(p => productRepository.create(p)));
    });

    it('페이지네이션이 효율적으로 동작해야 합니다', async () => {
      const PAGE_SIZE = 50;
      const start = Date.now();

      for (let page = 1; page <= 3; page++) {
        const products = await productRepository.findWithPagination({
          page,
          pageSize: PAGE_SIZE,
          userId: testUser.id
        });

        expect(products).toHaveLength(PAGE_SIZE);
        expect(products[0].name).toBe(`제품 ${(page - 1) * PAGE_SIZE + 1}`);
      }

      const end = Date.now();
      const executionTime = end - start;
      
      // 페이지네이션 처리가 1초 이내여야 함
      expect(executionTime).toBeLessThan(1000);
    });

    it('대량 필터링 성능이 최적화되어야 합니다', async () => {
      const start = Date.now();

      const results = await productRepository.findWithFilters({
        category: 'A',
        minPrice: 10000,
        maxPrice: 50000,
        userId: testUser.id
      });

      const end = Date.now();
      const executionTime = end - start;

      // 필터링 처리가 500ms 이내여야 함
      expect(executionTime).toBeLessThan(500);
      expect(results.length).toBeGreaterThan(0);
    });

    it('대량 통계 계산이 효율적으로 수행되어야 합니다', async () => {
      const start = Date.now();

      const stats = await productRepository.getCategoryStats();
      
      const end = Date.now();
      const executionTime = end - start;

      // 통계 계산이 1초 이내여야 함
      expect(executionTime).toBeLessThan(1000);
      expect(stats).toHaveLength(3); // A, B, C 카테고리
    });
  });

  describe('캐시 최적화', () => {
    it('벌크 캐시 작업이 효율적으로 수행되어야 합니다', async () => {
      const products = await Promise.all(
        Array.from({ length: 100 }, (_, i) => 
          productRepository.create({
            name: `캐시 테스트 ${i}`,
            price: 10000,
            cost: 5000,
            category: 'TEST',
            userId: testUser.id
          })
        )
      );

      const start = Date.now();

      // 벌크 캐시 저장
      await Promise.all(
        products.map(product => 
          cacheService.set(`product:${product.id}`, product, 3600)
        )
      );

      const end = Date.now();
      const executionTime = end - start;

      // 100개 캐시 저장이 200ms 이내여야 함
      expect(executionTime).toBeLessThan(200);
    });

    it('캐시 히트율이 높아야 합니다', async () => {
      const product = await productRepository.create({
        name: '히트율 테스트',
        price: 10000,
        cost: 5000,
        category: 'TEST',
        userId: testUser.id
      });

      const cacheKey = `product:${product.id}`;
      await cacheService.set(cacheKey, product, 3600);

      let cacheHits = 0;
      const TOTAL_REQUESTS = 100;

      for (let i = 0; i < TOTAL_REQUESTS; i++) {
        const result = await cacheService.get(cacheKey);
        if (result) cacheHits++;
      }

      const hitRate = (cacheHits / TOTAL_REQUESTS) * 100;
      expect(hitRate).toBeGreaterThan(95); // 95% 이상의 히트율
    });
  });

  describe('동시성 처리', () => {
    it('동시 요청을 효율적으로 처리해야 합니다', async () => {
      const CONCURRENT_REQUESTS = 50;
      const product = await productRepository.create({
        name: '동시성 테스트',
        price: 10000,
        cost: 5000,
        category: 'TEST',
        userId: testUser.id
      });

      const start = Date.now();

      // 동시 요청 시뮬레이션
      await Promise.all(
        Array.from({ length: CONCURRENT_REQUESTS }, () =>
          productRepository.findById(product.id)
        )
      );

      const end = Date.now();
      const executionTime = end - start;

      // 50개 동시 요청이 1초 이내여야 함
      expect(executionTime).toBeLessThan(1000);
    });

    it('동시 업데이트를 안전하게 처리해야 합니다', async () => {
      const product = await productRepository.create({
        name: '동시 업데이트 테스트',
        price: 10000,
        cost: 5000,
        category: 'TEST',
        userId: testUser.id
      });

      const updates = Array.from({ length: 10 }, (_, i) => ({
        name: `업데이트 ${i}`,
        price: 10000 + (i * 1000)
      }));

      // 동시 업데이트 실행
      await Promise.all(
        updates.map(update => 
          productRepository.update(product.id, update)
        )
      );

      // 최종 상태 확인
      const finalProduct = await productRepository.findById(product.id);
      expect(finalProduct).toBeDefined();
      expect(updates.some(u => u.name === finalProduct.name)).toBeTruthy();
    });
  });
}); 