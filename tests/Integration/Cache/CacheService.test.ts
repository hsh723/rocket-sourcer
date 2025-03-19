import { describe, expect, it, beforeEach, afterEach } from '@jest/globals';
import { Redis } from 'ioredis';
import { CacheService } from '@/services/CacheService';
import { ProductRepository } from '@/repositories/ProductRepository';
import { createTestUser } from '@/tests/helpers/auth';
import { db } from '@/database';

describe('CacheService Integration', () => {
  let cacheService: CacheService;
  let redis: Redis;
  let productRepository: ProductRepository;
  let testUser: any;

  beforeEach(async () => {
    await db.migrate.latest();
    testUser = await createTestUser();
    redis = new Redis(process.env.REDIS_URL);
    cacheService = new CacheService(redis);
    productRepository = new ProductRepository();
    await redis.flushall();
  });

  afterEach(async () => {
    await redis.flushall();
    await redis.quit();
    await db.migrate.rollback();
  });

  describe('제품 캐싱', () => {
    const testProduct = {
      name: '캐시 테스트 상품',
      description: '캐시 테스트용 상품입니다',
      price: 10000,
      cost: 5000,
      category: '전자기기',
      userId: null
    };

    beforeEach(() => {
      testProduct.userId = testUser.id;
    });

    it('제품 정보를 캐시에 저장하고 조회할 수 있어야 합니다', async () => {
      const product = await productRepository.create(testProduct);
      const cacheKey = `product:${product.id}`;

      // 캐시에 저장
      await cacheService.set(cacheKey, product, 3600);

      // 캐시에서 조회
      const cachedProduct = await cacheService.get(cacheKey);
      expect(cachedProduct).toMatchObject(product);
    });

    it('캐시 만료 시간이 지나면 데이터가 삭제되어야 합니다', async () => {
      const product = await productRepository.create(testProduct);
      const cacheKey = `product:${product.id}`;

      // 1초 만료시간으로 캐시에 저장
      await cacheService.set(cacheKey, product, 1);

      // 2초 대기
      await new Promise(resolve => setTimeout(resolve, 2000));

      // 캐시에서 조회
      const cachedProduct = await cacheService.get(cacheKey);
      expect(cachedProduct).toBeNull();
    });

    it('캐시를 수동으로 삭제할 수 있어야 합니다', async () => {
      const product = await productRepository.create(testProduct);
      const cacheKey = `product:${product.id}`;

      await cacheService.set(cacheKey, product, 3600);
      await cacheService.delete(cacheKey);

      const cachedProduct = await cacheService.get(cacheKey);
      expect(cachedProduct).toBeNull();
    });
  });

  describe('제품 목록 캐싱', () => {
    beforeEach(async () => {
      await productRepository.create({
        name: '제품 1',
        price: 10000,
        cost: 5000,
        category: 'A',
        userId: testUser.id
      });

      await productRepository.create({
        name: '제품 2',
        price: 20000,
        cost: 10000,
        category: 'A',
        userId: testUser.id
      });
    });

    it('제품 목록을 캐시에 저장하고 조회할 수 있어야 합니다', async () => {
      const products = await productRepository.findByUserId(testUser.id);
      const cacheKey = `products:user:${testUser.id}`;

      await cacheService.set(cacheKey, products, 3600);
      const cachedProducts = await cacheService.get(cacheKey);

      expect(cachedProducts).toHaveLength(2);
      expect(cachedProducts).toEqual(products);
    });

    it('패턴으로 여러 캐시 키를 삭제할 수 있어야 합니다', async () => {
      const cacheKeys = [
        `products:user:${testUser.id}:category:A`,
        `products:user:${testUser.id}:category:B`
      ];

      for (const key of cacheKeys) {
        await cacheService.set(key, ['test'], 3600);
      }

      await cacheService.deletePattern(`products:user:${testUser.id}:*`);

      for (const key of cacheKeys) {
        const cached = await cacheService.get(key);
        expect(cached).toBeNull();
      }
    });
  });

  describe('캐시 갱신', () => {
    let product: any;
    let cacheKey: string;

    beforeEach(async () => {
      product = await productRepository.create({
        name: '캐시 갱신 테스트',
        price: 10000,
        cost: 5000,
        category: 'TEST',
        userId: testUser.id
      });
      cacheKey = `product:${product.id}`;
    });

    it('제품 업데이트 시 캐시가 갱신되어야 합니다', async () => {
      // 초기 캐시 설정
      await cacheService.set(cacheKey, product, 3600);

      // 제품 업데이트
      const updates = { name: '수정된 상품명', price: 15000 };
      const updated = await productRepository.update(product.id, updates);

      // 캐시 갱신
      await cacheService.set(cacheKey, updated, 3600);

      // 캐시 확인
      const cachedProduct = await cacheService.get(cacheKey);
      expect(cachedProduct.name).toBe(updates.name);
      expect(cachedProduct.price).toBe(updates.price);
    });

    it('캐시 데이터가 DB와 일치하는지 확인할 수 있어야 합니다', async () => {
      await cacheService.set(cacheKey, product, 3600);

      const dbProduct = await productRepository.findById(product.id);
      const cachedProduct = await cacheService.get(cacheKey);

      expect(cachedProduct).toEqual(dbProduct);
    });
  });

  describe('캐시 성능', () => {
    it('캐시된 데이터 조회가 DB 조회보다 빨라야 합니다', async () => {
      const product = await productRepository.create({
        name: '성능 테스트',
        price: 10000,
        cost: 5000,
        category: 'TEST',
        userId: testUser.id
      });

      const cacheKey = `product:${product.id}`;
      await cacheService.set(cacheKey, product, 3600);

      // DB 조회 시간 측정
      const dbStart = Date.now();
      await productRepository.findById(product.id);
      const dbTime = Date.now() - dbStart;

      // 캐시 조회 시간 측정
      const cacheStart = Date.now();
      await cacheService.get(cacheKey);
      const cacheTime = Date.now() - cacheStart;

      expect(cacheTime).toBeLessThan(dbTime);
    });
  });
}); 