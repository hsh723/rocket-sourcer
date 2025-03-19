import { describe, expect, it, beforeEach, afterEach } from '@jest/globals';
import { db } from '@/database';
import { ProductRepository } from '@/repositories/ProductRepository';
import { createTestUser } from '@/tests/helpers/auth';

describe('ProductRepository Integration', () => {
  let repository: ProductRepository;
  let testUser: any;

  beforeEach(async () => {
    await db.migrate.latest();
    testUser = await createTestUser();
    repository = new ProductRepository();
  });

  afterEach(async () => {
    await db.migrate.rollback();
  });

  describe('제품 생성 및 조회', () => {
    const testProduct = {
      name: '테스트 상품',
      description: '테스트 상품 설명',
      price: 10000,
      cost: 5000,
      category: '전자기기',
      userId: null // testUser.id로 설정될 예정
    };

    beforeEach(() => {
      testProduct.userId = testUser.id;
    });

    it('새 제품을 생성할 수 있어야 합니다', async () => {
      const product = await repository.create(testProduct);

      expect(product).toHaveProperty('id');
      expect(product.name).toBe(testProduct.name);
      expect(product.price).toBe(testProduct.price);
    });

    it('ID로 제품을 조회할 수 있어야 합니다', async () => {
      const created = await repository.create(testProduct);
      const found = await repository.findById(created.id);

      expect(found).toMatchObject(testProduct);
    });

    it('사용자의 모든 제품을 조회할 수 있어야 합니다', async () => {
      await repository.create(testProduct);
      await repository.create({
        ...testProduct,
        name: '테스트 상품 2'
      });

      const products = await repository.findByUserId(testUser.id);
      expect(products).toHaveLength(2);
    });
  });

  describe('제품 업데이트 및 삭제', () => {
    let testProduct: any;

    beforeEach(async () => {
      testProduct = await repository.create({
        name: '테스트 상품',
        description: '테스트 상품 설명',
        price: 10000,
        cost: 5000,
        category: '전자기기',
        userId: testUser.id
      });
    });

    it('제품 정보를 업데이트할 수 있어야 합니다', async () => {
      const updates = {
        name: '수정된 상품명',
        price: 15000
      };

      const updated = await repository.update(testProduct.id, updates);
      expect(updated.name).toBe(updates.name);
      expect(updated.price).toBe(updates.price);
      expect(updated.description).toBe(testProduct.description);
    });

    it('제품을 삭제할 수 있어야 합니다', async () => {
      await repository.delete(testProduct.id);
      const found = await repository.findById(testProduct.id);
      expect(found).toBeNull();
    });
  });

  describe('제품 검색 및 필터링', () => {
    beforeEach(async () => {
      await repository.create({
        name: '노트북',
        description: '고성능 노트북',
        price: 1500000,
        cost: 1000000,
        category: '전자기기',
        userId: testUser.id
      });

      await repository.create({
        name: '스마트폰',
        description: '최신형 스마트폰',
        price: 1000000,
        cost: 700000,
        category: '전자기기',
        userId: testUser.id
      });

      await repository.create({
        name: '티셔츠',
        description: '면 티셔츠',
        price: 20000,
        cost: 8000,
        category: '의류',
        userId: testUser.id
      });
    });

    it('카테고리로 제품을 필터링할 수 있어야 합니다', async () => {
      const products = await repository.findByCategory('전자기기');
      expect(products).toHaveLength(2);
      expect(products[0].category).toBe('전자기기');
    });

    it('가격 범위로 제품을 필터링할 수 있어야 합니다', async () => {
      const products = await repository.findByPriceRange(500000, 1200000);
      expect(products).toHaveLength(1);
      expect(products[0].name).toBe('스마트폰');
    });

    it('키워드로 제품을 검색할 수 있어야 합니다', async () => {
      const products = await repository.search('노트북');
      expect(products).toHaveLength(1);
      expect(products[0].name).toBe('노트북');
    });

    it('여러 조건으로 제품을 필터링할 수 있어야 합니다', async () => {
      const products = await repository.findWithFilters({
        category: '전자기기',
        minPrice: 1000000,
        maxPrice: 2000000,
        keyword: '노트'
      });

      expect(products).toHaveLength(1);
      expect(products[0].name).toBe('노트북');
    });
  });

  describe('제품 통계', () => {
    beforeEach(async () => {
      await repository.create({
        name: '제품 1',
        price: 10000,
        cost: 5000,
        sales: 100,
        category: 'A',
        userId: testUser.id
      });

      await repository.create({
        name: '제품 2',
        price: 20000,
        cost: 10000,
        sales: 50,
        category: 'A',
        userId: testUser.id
      });

      await repository.create({
        name: '제품 3',
        price: 15000,
        cost: 7500,
        sales: 75,
        category: 'B',
        userId: testUser.id
      });
    });

    it('카테고리별 판매 통계를 계산할 수 있어야 합니다', async () => {
      const stats = await repository.getCategoryStats();
      expect(stats).toHaveLength(2);
      
      const categoryA = stats.find(s => s.category === 'A');
      expect(categoryA.totalSales).toBe(150);
      expect(categoryA.totalRevenue).toBe(2000000);
    });

    it('제품별 수익을 계산할 수 있어야 합니다', async () => {
      const profits = await repository.calculateProfits();
      expect(profits).toHaveLength(3);
      
      const product1 = profits.find(p => p.name === '제품 1');
      expect(product1.profit).toBe(500000);
    });
  });
}); 