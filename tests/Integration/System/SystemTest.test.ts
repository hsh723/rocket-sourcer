import { describe, expect, it, beforeEach, afterAll, beforeAll } from '@jest/globals';
import supertest from 'supertest';
import puppeteer, { Browser, Page } from 'puppeteer';
import { app } from '@/app';
import { createTestUser, generateToken } from '@/tests/helpers/auth';
import { ProductRepository } from '@/repositories/ProductRepository';
import { CalculatorService } from '@/services/CalculatorService';
import { BackupService } from '@/services/BackupService';
import { CacheService } from '@/services/CacheService';
import { db } from '@/database';
import { redis } from '@/cache';
import { existsSync, unlinkSync } from 'fs';

describe('종합 시스템 테스트', () => {
  let request: supertest.SuperTest<supertest.Test>;
  let browser: Browser;
  let page: Page;
  let testUser: any;
  let adminUser: any;
  let userToken: string;
  let adminToken: string;
  let productRepository: ProductRepository;
  let calculatorService: CalculatorService;
  let backupService: BackupService;
  let cacheService: CacheService;
  let backupFiles: string[] = [];

  beforeAll(async () => {
    browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
  });

  afterAll(async () => {
    await browser.close();
    
    // 생성된 백업 파일 정리
    for (const file of backupFiles) {
      if (existsSync(file)) {
        unlinkSync(file);
      }
    }
    
    await db.migrate.rollback();
    await redis.quit();
  });

  beforeEach(async () => {
    await db.migrate.latest();
    request = supertest(app);
    testUser = await createTestUser({ role: 'user' });
    adminUser = await createTestUser({ 
      email: 'admin@example.com',
      role: 'admin' 
    });
    userToken = generateToken(testUser);
    adminToken = generateToken(adminUser);
    productRepository = new ProductRepository();
    calculatorService = new CalculatorService();
    backupService = new BackupService();
    cacheService = new CacheService(redis);
    await redis.flushall();
    
    page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });
    
    // 테스트 데이터 생성
    await Promise.all(
      Array.from({ length: 10 }, (_, i) => 
        productRepository.create({
          name: `시스템 테스트 제품 ${i + 1}`,
          price: Math.floor(Math.random() * 100000) + 1000,
          cost: Math.floor(Math.random() * 50000) + 500,
          category: ['A', 'B', 'C'][Math.floor(Math.random() * 3)],
          userId: testUser.id
        })
      )
    );
  });

  async function loginUser() {
    await page.goto('http://localhost:3000/login');
    await page.type('input[name="email"]', testUser.email);
    await page.type('input[name="password"]', 'TestPassword123!');
    
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);
  }

  describe('전체 사용자 흐름', () => {
    it('사용자 등록부터 제품 분석까지 전체 흐름이 정상 동작해야 합니다', async () => {
      // 1. 새 사용자 등록
      const newUserEmail = `test-${Date.now()}@example.com`;
      const newUserPassword = 'NewPassword123!';
      
      await page.goto('http://localhost:3000/register');
      await page.type('input[name="email"]', newUserEmail);
      await page.type('input[name="password"]', newUserPassword);
      await page.type('input[name="passwordConfirmation"]', newUserPassword);
      await page.type('input[name="name"]', '테스트 사용자');
      
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
      
      // 대시보드로 리다이렉트 확인
      expect(page.url()).toContain('/dashboard');
      
      // 2. 제품 등록
      await page.goto('http://localhost:3000/products/new');
      await page.type('input[name="name"]', '종합 테스트 제품');
      await page.type('input[name="price"]', '50000');
      await page.type('input[name="cost"]', '30000');
      await page.select('select[name="category"]', 'A');
      
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
      
      // 제품 목록 페이지로 리다이렉트 확인
      expect(page.url()).toContain('/products');
      
      // 3. 제품 검색
      await page.type('input[name="search"]', '종합 테스트');
      await page.waitForSelector('.product-item');
      
      const productName = await page.evaluate(() => {
        return document.querySelector('.product-item .product-name')?.textContent;
      });
      
      expect(productName).toContain('종합 테스트 제품');
      
      // 4. 수익성 계산
      await page.goto('http://localhost:3000/calculator');
      await page.type('input[name="productPrice"]', '50000');
      await page.type('input[name="productCost"]', '30000');
      await page.type('input[name="shippingCost"]', '3000');
      await page.type('input[name="marketplaceFee"]', '0.1');
      await page.type('input[name="marketingCost"]', '2000');
      await page.type('input[name="expectedSales"]', '100');
      
      await page.click('.calculate-button');
      await page.waitForSelector('.calculation-results');
      
      const monthlyProfit = await page.evaluate(() => {
        return document.querySelector('.monthly-profit')?.textContent;
      });
      
      expect(monthlyProfit).toContain('₩');
      
      // 5. 결과 저장
      await page.click('.save-results-button');
      await page.waitForSelector('.success-message');
      
      // 6. 저장된 계산 결과 확인
      await page.goto('http://localhost:3000/calculations');
      
      const calculationList = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('.calculation-item')).map(item => {
          return item.textContent;
        });
      });
      
      expect(calculationList.length).toBeGreaterThan(0);
      
      // 7. 로그아웃
      await page.click('.logout-button');
      await page.waitForNavigation();
      
      // 로그인 페이지로 리다이렉트 확인
      expect(page.url()).toContain('/login');
    });
  });

  describe('시스템 통합 기능', () => {
    it('백업 및 복구 프로세스가 전체 시스템에 영향을 주지 않아야 합니다', async () => {
      // 1. 초기 제품 수 확인
      const initialProducts = await productRepository.findAll();
      const initialCount = initialProducts.length;
      
      // 2. 백업 생성
      const backup = await backupService.createFullBackup();
      backupFiles.push(backup.filePath);
      
      // 3. 새 제품 추가
      const newProduct = await productRepository.create({
        name: '백업 테스트 제품',
        price: 99999,
        cost: 55555,
        category: 'A',
        userId: testUser.id
      });
      
      // 4. 백업에서 복구
      const restoreResult = await backupService.restoreFromBackup(backup.filePath);
      expect(restoreResult.success).toBeTruthy();
      
      // 5. 복구 후 제품 수 확인
      const restoredProducts = await productRepository.findAll();
      expect(restoredProducts.length).toBe(initialCount);
      
      // 6. API가 정상 동작하는지 확인
      const response = await request
        .get('/api/products')
        .set('Authorization', `Bearer ${userToken}`);
      
      expect(response.status).toBe(200);
      expect(response.body.products).toHaveLength(initialCount);
      
      // 7. 웹 인터페이스가 정상 동작하는지 확인
      await loginUser();
      await page.goto('http://localhost:3000/products');
      
      const productItems = await page.evaluate(() => {
        return document.querySelectorAll('.product-item').length;
      });
      
      expect(productItems).toBe(initialCount);
    });

    it('캐시 시스템이 데이터베이스와 동기화되어야 합니다', async () => {
      // 1. 제품 생성
      const product = await productRepository.create({
        name: '캐시 동기화 테스트 제품',
        price: 88888,
        cost: 44444,
        category: 'B',
        userId: testUser.id
      });
      
      // 2. 캐시에 저장
      await cacheService.set(`product:${product.id}`, product, 3600);
      
      // 3. 제품 업데이트
      const updatedProduct = await productRepository.update(product.id, {
        price: 99999,
        name: '업데이트된 제품'
      });
      
      // 4. 캐시 업데이트
      await cacheService.set(`product:${product.id}`, updatedProduct, 3600);
      
      // 5. API를 통해 제품 조회
      const response = await request
        .get(`/api/products/${product.id}`)
        .set('Authorization', `Bearer ${userToken}`);
      
      expect(response.status).toBe(200);
      expect(response.body.name).toBe('업데이트된 제품');
      expect(response.body.price).toBe(99999);
      
      // 6. 캐시에서 제품 조회
      const cachedProduct = await cacheService.get(`product:${product.id}`);
      expect(cachedProduct.name).toBe('업데이트된 제품');
      expect(cachedProduct.price).toBe(99999);
      
      // 7. 웹 인터페이스에서 제품 확인
      await loginUser();
      await page.goto(`http://localhost:3000/products/${product.id}`);
      
      const productName = await page.evaluate(() => {
        return document.querySelector('.product-name')?.textContent;
      });
      
      expect(productName).toBe('업데이트된 제품');
    });
  });

  describe('시스템 장애 복구', () => {
    it('데이터베이스 연결 장애 후 시스템이 복구되어야 합니다', async () => {
      // 1. 초기 상태 확인
      const initialResponse = await request
        .get('/api/products')
        .set('Authorization', `Bearer ${userToken}`);
      
      expect(initialResponse.status).toBe(200);
      
      // 2. 데이터베이스 연결 끊기 (시뮬레이션)
      const originalPool = db.client.pool;
      db.client.pool = null;
      
      // 3. API 요청 시도 (실패 예상)
      const failedResponse = await request
        .get('/api/products')
        .set('Authorization', `Bearer ${userToken}`);
      
      expect(failedResponse.status).toBe(500);
      
      // 4. 데이터베이스 연결 복구
      db.client.pool = originalPool;
      
      // 5. 시스템 복구 확인
      const recoveredResponse = await request
        .get('/api/products')
        .set('Authorization', `Bearer ${userToken}`);
      
      expect(recoveredResponse.status).toBe(200);
    });

    it('캐시 서버 장애 후 시스템이 복구되어야 합니다', async () => {
      // 1. 제품 생성 및 캐싱
      const product = await productRepository.create({
        name: '장애 복구 테스트 제품',
        price: 77777,
        cost: 33333,
        category: 'C',
        userId: testUser.id
      });
      
      await cacheService.set(`product:${product.id}`, product, 3600);
      
      // 2. 캐시 서버 연결 끊기 (시뮬레이션)
      const originalRedisClient = redis.connector.options;
      redis.connector.options = null;
      
      // 3. API 요청 (캐시 미스, 데이터베이스에서 조회)
      const response = await request
        .get(`/api/products/${product.id}`)
        .set('Authorization', `Bearer ${userToken}`);
      
      // 캐시 장애에도 불구하고 API는 정상 동작해야 함
      expect(response.status).toBe(200);
      expect(response.body.name).toBe('장애 복구 테스트 제품');
      
      // 4. 캐시 서버 연결 복구
      redis.connector.options = originalRedisClient;
      
      // 5. 캐시 재구축
      await cacheService.set(`product:${product.id}`, product, 3600);
      
      // 6. 캐시 복구 확인
      const cachedProduct = await cacheService.get(`product:${product.id}`);
      expect(cachedProduct).toBeDefined();
      expect(cachedProduct.name).toBe('장애 복구 테스트 제품');
    });
  });

  describe('동시성 및 부하 처리', () => {
    it('다수의 동시 요청을 처리할 수 있어야 합니다', async () => {
      const CONCURRENT_REQUESTS = 20;
      const requests = Array.from({ length: CONCURRENT_REQUESTS }, () =>
        request
          .get('/api/products')
          .set('Authorization', `Bearer ${userToken}`)
      );
      
      const responses = await Promise.all(requests);
      
      // 모든 요청이 성공해야 함
      expect(responses.every(r => r.status === 200)).toBeTruthy();
      
      // 모든 응답이 동일한 데이터를 반환해야 함
      const productCounts = responses.map(r => r.body.products.length);
      const allEqual = productCounts.every(count => count === productCounts[0]);
      expect(allEqual).toBeTruthy();
    });

    it('대량의 데이터 처리 요청을 처리할 수 있어야 합니다', async () => {
      // 대량의 계산 요청 생성
      const calculations = Array.from({ length: 50 }, (_, i) => ({
        productPrice: 50000 + (i * 100),
        productCost: 30000 + (i * 50),
        shippingCost: 3000,
        marketplaceFee: 0.1,
        marketingCost: 2000,
        expectedSales: 100 + i
      }));
      
      // 순차적으로 계산 요청 실행
      const start = Date.now();
      
      const results = await Promise.all(
        calculations.map(calc => 
          request
            .post('/api/calculator/profit')
            .set('Authorization', `Bearer ${userToken}`)
            .send(calc)
        )
      );
      
      const end = Date.now();
      const totalTime = end - start;
      
      // 모든 요청이 성공해야 함
      expect(results.every(r => r.status === 200)).toBeTruthy();
      
      // 총 처리 시간이 합리적이어야 함 (요청당 평균 100ms 이하)
      const avgTimePerRequest = totalTime / calculations.length;
      expect(avgTimePerRequest).toBeLessThan(100);
    });
  });

  describe('보안 및 권한', () => {
    it('권한에 따라 기능 접근이 제한되어야 합니다', async () => {
      // 1. 일반 사용자로 관리자 기능 접근 시도
      const userAdminResponse = await request
        .get('/api/admin/users')
        .set('Authorization', `Bearer ${userToken}`);
      
      expect(userAdminResponse.status).toBe(403);
      
      // 2. 관리자로 관리자 기능 접근
      const adminResponse = await request
        .get('/api/admin/users')
        .set('Authorization', `Bearer ${adminToken}`);
      
      expect(adminResponse.status).toBe(200);
      
      // 3. 다른 사용자의 제품 수정 시도
      const otherUserProduct = await productRepository.create({
        name: '다른 사용자 제품',
        price: 12345,
        cost: 6789,
        category: 'A',
        userId: adminUser.id
      });
      
      const updateResponse = await request
        .put(`/api/products/${otherUserProduct.id}`)
        .set('Authorization', `Bearer ${userToken}`)
        .send({ name: '수정된 제품' });
      
      expect(updateResponse.status).toBe(403);
    });

    it('인증 토큰 만료 시 적절히 처리되어야 합니다', async () => {
      // 1. 만료된 토큰 생성
      const expiredToken = generateToken(testUser, { expiresIn: '0s' });
      
      // 2. 만료된 토큰으로 API 요청
      const response = await request
        .get('/api/products')
        .set('Authorization', `Bearer ${expiredToken}`);
      
      expect(response.status).toBe(401);
      
      // 3. 웹 인터페이스에서 만료된 토큰 처리 확인
      await page.goto('http://localhost:3000/login');
      
      // 만료된 토큰 설정
      await page.evaluate((token) => {
        localStorage.setItem('authToken', token);
      }, expiredToken);
      
      // 보호된 페이지 접근 시도
      await page.goto('http://localhost:3000/products');
      
      // 로그인 페이지로 리다이렉트 확인
      expect(page.url()).toContain('/login');
    });
  });

  describe('국제화 및 지역화', () => {
    it('다국어 지원이 전체 시스템에서 일관되게 적용되어야 합니다', async () => {
      // 1. 한국어 설정으로 페이지 접근
      await page.goto('http://localhost:3000/login?lang=ko');
      
      const koLoginButton = await page.evaluate(() => {
        return document.querySelector('button[type="submit"]')?.textContent;
      });
      
      expect(koLoginButton).toBe('로그인');
      
      // 2. 영어 설정으로 페이지 접근
      await page.goto('http://localhost:3000/login?lang=en');
      
      const enLoginButton = await page.evaluate(() => {
        return document.querySelector('button[type="submit"]')?.textContent;
      });
      
      expect(enLoginButton).toBe('Login');
      
      // 3. API 응답의 언어 설정 확인
      const koResponse = await request
        .get('/api/products/999999')
        .set('Accept-Language', 'ko')
        .set('Authorization', `Bearer ${userToken}`);
      
      expect(koResponse.body.message).toContain('찾을 수 없습니다');
      
      const enResponse = await request
        .get('/api/products/999999')
        .set('Accept-Language', 'en')
        .set('Authorization', `Bearer ${userToken}`);
      
      expect(enResponse.body.message).toContain('not found');
    });
  });

  describe('로깅 및 모니터링', () => {
    it('시스템 이벤트가 올바르게 로깅되어야 합니다', async () => {
      // 1. API 요청 실행
      await request
        .get('/api/products')
        .set('Authorization', `Bearer ${userToken}`);
      
      // 2. 에러 발생 요청 실행
      await request
        .get('/api/products/999999')
        .set('Authorization', `Bearer ${userToken}`);
      
      // 3. 로그 확인 (로그 서비스를 통해)
      const logs = await db('logs').select().orderBy('created_at', 'desc').limit(10);
      
      // 액세스 로그 확인
      const accessLog = logs.find(log => log.type === 'access' && log.path === '/api/products');
      expect(accessLog).toBeDefined();
      
      // 에러 로그 확인
      const errorLog = logs.find(log => log.level === 'error' && log.path === '/api/products/999999');
      expect(errorLog).toBeDefined();
    });
  });

  describe('성능 및 확장성', () => {
    it('캐시를 통한 성능 최적화가 적용되어야 합니다', async () => {
      // 1. 캐시 없이 첫 요청 실행
      const start1 = Date.now();
      
      await request
        .get('/api/products')
        .set('Authorization', `Bearer ${userToken}`);
      
      const time1 = Date.now() - start1;
      
      // 2. 캐시된 두 번째 요청 실행
      const start2 = Date.now();
      
      await request
        .get('/api/products')
        .set('Authorization', `Bearer ${userToken}`);
      
      const time2 = Date.now() - start2;
      
      // 두 번째 요청이 더 빨라야 함
      expect(time2).toBeLessThan(time1);
    });
  });
}); 