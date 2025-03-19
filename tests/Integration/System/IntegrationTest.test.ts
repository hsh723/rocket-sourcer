import { describe, expect, it, beforeEach } from '@jest/globals';
import supertest from 'supertest';
import { app } from '@/app';
import { createTestUser, generateToken } from '@/tests/helpers/auth';
import { ProductService } from '@/services/ProductService';
import { CalculatorService } from '@/services/CalculatorService';
import { NotificationService } from '@/services/NotificationService';
import { db } from '@/database';
import { redis } from '@/cache';

describe('시스템 통합 테스트', () => {
  let request: supertest.SuperTest<supertest.Test>;
  let testUser: any;
  let authToken: string;
  let productService: ProductService;
  let calculatorService: CalculatorService;
  let notificationService: NotificationService;

  beforeEach(async () => {
    await db.migrate.latest();
    request = supertest(app);
    testUser = await createTestUser();
    authToken = generateToken(testUser);
    productService = new ProductService();
    calculatorService = new CalculatorService();
    notificationService = new NotificationService();
    await redis.flushall();
  });

  describe('제품 분석 워크플로우', () => {
    it('전체 제품 분석 프로세스가 정상적으로 동작해야 합니다', async () => {
      // 1. 제품 등록
      const productData = {
        name: '테스트 제품',
        price: 50000,
        cost: 30000,
        category: 'electronics'
      };

      const createResponse = await request
        .post('/api/products')
        .set('Authorization', `Bearer ${authToken}`)
        .send(productData);

      expect(createResponse.status).toBe(201);
      const product = createResponse.body;

      // 2. 수익성 계산
      const calculationData = {
        productId: product.id,
        marketplaceFee: 0.1,
        shippingCost: 3000,
        marketingCost: 2000,
        expectedSales: 100
      };

      const calculationResponse = await request
        .post('/api/calculator/profit')
        .set('Authorization', `Bearer ${authToken}`)
        .send(calculationData);

      expect(calculationResponse.status).toBe(200);
      const calculation = calculationResponse.body;
      expect(calculation.monthlyProfit).toBeGreaterThan(0);

      // 3. 분석 결과 저장
      const analysisData = {
        productId: product.id,
        calculationId: calculation.id,
        notes: '테스트 분석'
      };

      const analysisResponse = await request
        .post('/api/products/analysis')
        .set('Authorization', `Bearer ${authToken}`)
        .send(analysisData);

      expect(analysisResponse.status).toBe(200);
    });

    it('제품 검색부터 비교 분석까지 프로세스가 동작해야 합니다', async () => {
      // 1. 여러 제품 등록
      const products = await Promise.all([
        productService.create({
          name: '제품 A',
          price: 50000,
          cost: 30000,
          category: 'A'
        }),
        productService.create({
          name: '제품 B',
          price: 60000,
          cost: 35000,
          category: 'A'
        })
      ]);

      // 2. 제품 검색
      const searchResponse = await request
        .get('/api/products/search')
        .query({ category: 'A' })
        .set('Authorization', `Bearer ${authToken}`);

      expect(searchResponse.status).toBe(200);
      expect(searchResponse.body.products).toHaveLength(2);

      // 3. 제품 비교 분석
      const comparisonResponse = await request
        .post('/api/products/compare')
        .set('Authorization', `Bearer ${authToken}`)
        .send({
          productIds: products.map(p => p.id)
        });

      expect(comparisonResponse.status).toBe(200);
      expect(comparisonResponse.body.comparison).toBeDefined();
    });
  });

  describe('알림 및 모니터링 통합', () => {
    it('중요 이벤트가 알림을 트리거해야 합니다', async () => {
      // 1. 수익성 임계값 설정
      await request
        .post('/api/settings/notifications')
        .set('Authorization', `Bearer ${authToken}`)
        .send({
          profitThreshold: 100000,
          notifyOnThreshold: true
        });

      // 2. 높은 수익성의 계산 실행
      const calculationResponse = await request
        .post('/api/calculator/profit')
        .set('Authorization', `Bearer ${authToken}`)
        .send({
          productPrice: 500000,
          productCost: 200000,
          expectedSales: 100
        });

      // 3. 알림 확인
      const notifications = await notificationService.getNotifications(testUser.id);
      expect(notifications).toContainEqual(
        expect.objectContaining({
          type: 'profit_threshold',
          read: false
        })
      );
    });
  });

  describe('데이터 동기화', () => {
    it('캐시와 데이터베이스가 동기화되어야 합니다', async () => {
      // 1. 제품 생성
      const product = await productService.create({
        name: '동기화 테스트 제품',
        price: 50000,
        cost: 30000
      });

      // 2. 캐시에서 조회
      const cachedProduct = await redis.get(`product:${product.id}`);
      expect(JSON.parse(cachedProduct)).toMatchObject({
        id: product.id,
        name: product.name
      });

      // 3. 제품 업데이트
      const updateResponse = await request
        .put(`/api/products/${product.id}`)
        .set('Authorization', `Bearer ${authToken}`)
        .send({ price: 55000 });

      expect(updateResponse.status).toBe(200);

      // 4. 캐시 업데이트 확인
      const updatedCache = await redis.get(`product:${product.id}`);
      expect(JSON.parse(updatedCache).price).toBe(55000);
    });
  });

  describe('에러 처리', () => {
    it('트랜잭션이 롤백되어야 합니다', async () => {
      const initialCount = await productService.count();

      try {
        await db.transaction(async (trx) => {
          // 1. 정상적인 제품 생성
          await productService.create({
            name: '트랜잭션 테스트 1',
            price: 50000,
            cost: 30000
          }, trx);

          // 2. 에러를 발생시키는 제품 생성
          await productService.create({
            name: null, // 유효하지 않은 데이터
            price: -1000
          }, trx);
        });
      } catch (error) {
        // 에러 발생 예상
      }

      // 트랜잭션이 롤백되어 제품 수가 변하지 않아야 함
      const finalCount = await productService.count();
      expect(finalCount).toBe(initialCount);
    });
  });

  describe('권한 및 정책', () => {
    it('사용자 역할에 따라 기능 접근이 제한되어야 합니다', async () => {
      // 일반 사용자 생성
      const regularUser = await createTestUser({ role: 'user' });
      const regularToken = generateToken(regularUser);

      // 관리자 사용자 생성
      const adminUser = await createTestUser({ role: 'admin' });
      const adminToken = generateToken(adminUser);

      // 일반 사용자 접근 테스트
      const regularUserResponse = await request
        .get('/api/admin/users')
        .set('Authorization', `Bearer ${regularToken}`);

      expect(regularUserResponse.status).toBe(403);

      // 관리자 접근 테스트
      const adminResponse = await request
        .get('/api/admin/users')
        .set('Authorization', `Bearer ${adminToken}`);

      expect(adminResponse.status).toBe(200);
    });
  });
}); 