import { describe, expect, it, beforeAll, afterAll } from '@jest/globals';
import puppeteer, { Browser, Page } from 'puppeteer';
import { db } from '@/database';
import { createTestUser } from '@/tests/helpers/auth';

describe('E2E 테스트 - 사용자 여정', () => {
  let browser: Browser;
  let page: Page;
  let testUser: any;

  beforeAll(async () => {
    await db.migrate.latest();
    
    // 테스트 사용자 생성
    testUser = await createTestUser({
      email: 'e2e-test@example.com',
      password: 'TestPassword123!'
    });
    
    browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });
  });

  afterAll(async () => {
    await browser.close();
    await db.migrate.rollback();
  });

  describe('인증 흐름', () => {
    it('로그인 및 로그아웃이 정상적으로 동작해야 합니다', async () => {
      // 로그인 페이지 접속
      await page.goto('http://localhost:3000/login');
      
      // 로그인 폼 작성
      await page.type('input[name="email"]', testUser.email);
      await page.type('input[name="password"]', 'TestPassword123!');
      
      // 로그인 버튼 클릭
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
      
      // 대시보드로 리다이렉트 확인
      expect(page.url()).toContain('/dashboard');
      
      // 로그인 상태 확인
      const userInfo = await page.evaluate(() => {
        return document.querySelector('.user-info')?.textContent;
      });
      
      expect(userInfo).toContain(testUser.email);
      
      // 로그아웃
      await Promise.all([
        page.waitForNavigation(),
        page.click('.logout-button')
      ]);
      
      // 로그인 페이지로 리다이렉트 확인
      expect(page.url()).toContain('/login');
    });
    
    it('비밀번호 재설정 프로세스가 정상적으로 동작해야 합니다', async () => {
      // 비밀번호 재설정 페이지 접속
      await page.goto('http://localhost:3000/reset-password');
      
      // 이메일 입력
      await page.type('input[name="email"]', testUser.email);
      
      // 재설정 요청 버튼 클릭
      await page.click('button[type="submit"]');
      
      // 성공 메시지 확인
      await page.waitForSelector('.success-message');
      const successMessage = await page.evaluate(() => {
        return document.querySelector('.success-message')?.textContent;
      });
      
      expect(successMessage).toContain('이메일이 전송되었습니다');
      
      // 이메일에서 토큰 추출 (실제로는 이메일 서비스를 모킹해야 함)
      const resetToken = 'test-reset-token';
      
      // 토큰을 사용하여 새 비밀번호 설정 페이지 접속
      await page.goto(`http://localhost:3000/reset-password/${resetToken}`);
      
      // 새 비밀번호 입력
      await page.type('input[name="password"]', 'NewPassword123!');
      await page.type('input[name="passwordConfirmation"]', 'NewPassword123!');
      
      // 비밀번호 변경 버튼 클릭
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
      
      // 로그인 페이지로 리다이렉트 확인
      expect(page.url()).toContain('/login');
      
      // 새 비밀번호로 로그인
      await page.type('input[name="email"]', testUser.email);
      await page.type('input[name="password"]', 'NewPassword123!');
      
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
      
      // 로그인 성공 확인
      expect(page.url()).toContain('/dashboard');
    });
  });

  describe('제품 관리 흐름', () => {
    beforeEach(async () => {
      // 로그인
      await page.goto('http://localhost:3000/login');
      await page.type('input[name="email"]', testUser.email);
      await page.type('input[name="password"]', 'NewPassword123!');
      
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
    });
    
    it('제품 등록, 조회, 수정, 삭제 프로세스가 정상적으로 동작해야 합니다', async () => {
      // 제품 목록 페이지 접속
      await page.goto('http://localhost:3000/products');
      
      // 새 제품 추가 버튼 클릭
      await Promise.all([
        page.waitForNavigation(),
        page.click('.add-product-button')
      ]);
      
      // 제품 정보 입력
      await page.type('input[name="name"]', 'E2E 테스트 제품');
      await page.type('input[name="price"]', '50000');
      await page.type('input[name="cost"]', '30000');
      await page.select('select[name="category"]', 'electronics');
      
      // 제품 등록 버튼 클릭
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
      
      // 제품 목록 페이지로 리다이렉트 확인
      expect(page.url()).toContain('/products');
      
      // 등록한 제품이 목록에 표시되는지 확인
      const productList = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('.product-item')).map(item => {
          return item.textContent;
        });
      });
      
      expect(productList.some(item => item.includes('E2E 테스트 제품'))).toBeTruthy();
      
      // 제품 상세 페이지로 이동
      await page.click('.product-item:first-child .view-button');
      
      // 제품 정보 확인
      const productName = await page.evaluate(() => {
        return document.querySelector('.product-name')?.textContent;
      });
      
      expect(productName).toBe('E2E 테스트 제품');
      
      // 제품 수정 버튼 클릭
      await Promise.all([
        page.waitForNavigation(),
        page.click('.edit-product-button')
      ]);
      
      // 제품 정보 수정
      await page.evaluate(() => {
        (document.querySelector('input[name="name"]') as HTMLInputElement).value = '';
      });
      await page.type('input[name="name"]', '수정된 E2E 테스트 제품');
      
      // 수정 저장 버튼 클릭
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
      
      // 수정된 정보 확인
      const updatedProductName = await page.evaluate(() => {
        return document.querySelector('.product-name')?.textContent;
      });
      
      expect(updatedProductName).toBe('수정된 E2E 테스트 제품');
      
      // 제품 삭제 버튼 클릭
      await page.click('.delete-product-button');
      
      // 확인 다이얼로그 확인
      await page.waitForSelector('.confirmation-dialog');
      await Promise.all([
        page.waitForNavigation(),
        page.click('.confirm-delete-button')
      ]);
      
      // 제품 목록 페이지로 리다이렉트 확인
      expect(page.url()).toContain('/products');
      
      // 삭제된 제품이 목록에 없는지 확인
      const updatedProductList = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('.product-item')).map(item => {
          return item.textContent;
        });
      });
      
      expect(updatedProductList.some(item => item.includes('수정된 E2E 테스트 제품'))).toBeFalsy();
    });
  });

  describe('수익성 계산 흐름', () => {
    beforeEach(async () => {
      // 로그인
      await page.goto('http://localhost:3000/login');
      await page.type('input[name="email"]', testUser.email);
      await page.type('input[name="password"]', 'NewPassword123!');
      
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
    });
    
    it('수익성 계산 및 결과 저장 프로세스가 정상적으로 동작해야 합니다', async () => {
      // 수익성 계산기 페이지 접속
      await page.goto('http://localhost:3000/calculator');
      
      // 계산 정보 입력
      await page.type('input[name="productPrice"]', '50000');
      await page.type('input[name="productCost"]', '30000');
      await page.type('input[name="shippingCost"]', '3000');
      await page.type('input[name="marketplaceFee"]', '0.1');
      await page.type('input[name="marketingCost"]', '2000');
      await page.type('input[name="expectedSales"]', '100');
      
      // 계산 버튼 클릭
      await page.click('.calculate-button');
      
      // 계산 결과 확인
      await page.waitForSelector('.calculation-results');
      
      const monthlyProfit = await page.evaluate(() => {
        return document.querySelector('.monthly-profit')?.textContent;
      });
      
      expect(monthlyProfit).toContain('₩');
      
      // 결과 저장 버튼 클릭
      await page.click('.save-results-button');
      
      // 저장 확인 메시지 확인
      await page.waitForSelector('.success-message');
      
      const successMessage = await page.evaluate(() => {
        return document.querySelector('.success-message')?.textContent;
      });
      
      expect(successMessage).toContain('저장되었습니다');
      
      // 저장된 계산 결과 목록 페이지로 이동
      await page.goto('http://localhost:3000/calculations');
      
      // 저장된 결과가 목록에 표시되는지 확인
      const calculationList = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('.calculation-item')).map(item => {
          return item.textContent;
        });
      });
      
      expect(calculationList.length).toBeGreaterThan(0);
    });
  });

  describe('설정 관리 흐름', () => {
    beforeEach(async () => {
      // 로그인
      await page.goto('http://localhost:3000/login');
      await page.type('input[name="email"]', testUser.email);
      await page.type('input[name="password"]', 'NewPassword123!');
      
      await Promise.all([
        page.waitForNavigation(),
        page.click('button[type="submit"]')
      ]);
    });
    
    it('API 설정 변경이 정상적으로 동작해야 합니다', async () => {
      // 설정 페이지 접속
      await page.goto('http://localhost:3000/settings');
      
      // API 설정 탭 클릭
      await page.click('button[data-tab="api"]');
      
      // API 키 입력
      await page.evaluate(() => {
        (document.querySelector('input[name="coupangApiKey"]') as HTMLInputElement).value = '';
      });
      await page.type('input[name="coupangApiKey"]', 'test-api-key-123');
      
      // 저장 버튼 클릭
      await page.click('button[type="submit"]');
      
      // 저장 확인 메시지 확인
      await page.waitForSelector('.success-message');
      
      // 페이지 새로고침
      await page.reload();
      
      // API 설정 탭 클릭
      await page.click('button[data-tab="api"]');
      
      // 저장된 값 확인
      const apiKeyValue = await page.evaluate(() => {
        return (document.querySelector('input[name="coupangApiKey"]') as HTMLInputElement).value;
      });
      
      expect(apiKeyValue).toBe('test-api-key-123');
    });
    
    it('알림 설정 변경이 정상적으로 동작해야 합니다', async () => {
      // 설정 페이지 접속
      await page.goto('http://localhost:3000/settings');
      
      // 알림 설정 탭 클릭
      await page.click('button[data-tab="notifications"]');
      
      // 이메일 알림 토글
      await page.click('input[name="emailNotifications"]');
      
      // 저장 버튼 클릭
      await page.click('button[type="submit"]');
      
      // 저장 확인 메시지 확인
      await page.waitForSelector('.success-message');
      
      // 페이지 새로고침
      await page.reload();
      
      // 알림 설정 탭 클릭
      await page.click('button[data-tab="notifications"]');
      
      // 저장된 값 확인
      const isChecked = await page.evaluate(() => {
        return (document.querySelector('input[name="emailNotifications"]') as HTMLInputElement).checked;
      });
      
      expect(isChecked).toBeTruthy();
    });
  });
}); 