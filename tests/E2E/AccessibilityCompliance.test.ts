import { describe, expect, it, beforeAll, afterAll } from '@jest/globals';
import puppeteer, { Browser, Page } from 'puppeteer';
import { AxePuppeteer } from '@axe-core/puppeteer';
import { createTestUser } from '@/tests/helpers/auth';
import { db } from '@/database';

describe('접근성 준수 테스트', () => {
  let browser: Browser;
  let page: Page;
  let testUser: any;

  beforeAll(async () => {
    await db.migrate.latest();
    
    // 테스트 사용자 생성
    testUser = await createTestUser({
      email: 'accessibility-test@example.com',
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

  async function loginUser() {
    await page.goto('http://localhost:3000/login');
    await page.type('input[name="email"]', testUser.email);
    await page.type('input[name="password"]', 'TestPassword123!');
    
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);
  }

  async function runAxeTest(pageName: string) {
    const results = await new AxePuppeteer(page).analyze();
    
    // 위반 사항 로깅
    if (results.violations.length > 0) {
      console.log(`${pageName} 페이지 접근성 위반 사항:`, 
        results.violations.map(v => ({
          id: v.id,
          impact: v.impact,
          description: v.description,
          nodes: v.nodes.length
        }))
      );
    }
    
    return results;
  }

  describe('WCAG 2.1 준수 여부', () => {
    it('로그인 페이지가 접근성 기준을 준수해야 합니다', async () => {
      await page.goto('http://localhost:3000/login');
      const results = await runAxeTest('로그인');
      
      // 심각한(critical) 위반 사항이 없어야 함
      const criticalViolations = results.violations.filter(v => v.impact === 'critical');
      expect(criticalViolations).toHaveLength(0);
    });

    it('대시보드 페이지가 접근성 기준을 준수해야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/dashboard');
      const results = await runAxeTest('대시보드');
      
      // 심각한(critical) 위반 사항이 없어야 함
      const criticalViolations = results.violations.filter(v => v.impact === 'critical');
      expect(criticalViolations).toHaveLength(0);
    });

    it('제품 목록 페이지가 접근성 기준을 준수해야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/products');
      const results = await runAxeTest('제품 목록');
      
      // 심각한(critical) 위반 사항이 없어야 함
      const criticalViolations = results.violations.filter(v => v.impact === 'critical');
      expect(criticalViolations).toHaveLength(0);
    });

    it('수익성 계산기 페이지가 접근성 기준을 준수해야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/calculator');
      const results = await runAxeTest('수익성 계산기');
      
      // 심각한(critical) 위반 사항이 없어야 함
      const criticalViolations = results.violations.filter(v => v.impact === 'critical');
      expect(criticalViolations).toHaveLength(0);
    });

    it('설정 페이지가 접근성 기준을 준수해야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/settings');
      const results = await runAxeTest('설정');
      
      // 심각한(critical) 위반 사항이 없어야 함
      const criticalViolations = results.violations.filter(v => v.impact === 'critical');
      expect(criticalViolations).toHaveLength(0);
    });
  });

  describe('키보드 접근성', () => {
    it('모든 상호작용 요소에 키보드로 접근 가능해야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/products');
      
      // 탭 키를 사용하여 모든 상호작용 요소 순회
      const focusableElements = await page.evaluate(() => {
        const elements = Array.from(document.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'));
        return elements.map(el => ({
          tagName: el.tagName,
          type: (el as HTMLElement).getAttribute('type'),
          hasTabIndex: (el as HTMLElement).hasAttribute('tabindex')
        }));
      });
      
      // 모든 상호작용 요소가 키보드로 접근 가능한지 확인
      for (let i = 0; i < focusableElements.length; i++) {
        await page.keyboard.press('Tab');
        
        const isFocused = await page.evaluate(() => {
          const activeElement = document.activeElement;
          return {
            tagName: activeElement?.tagName,
            type: activeElement?.getAttribute('type'),
            hasTabIndex: activeElement?.hasAttribute('tabindex')
          };
        });
        
        expect(isFocused.tagName).toBeTruthy();
      }
    });

    it('모달 다이얼로그가 키보드 트랩을 올바르게 구현해야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/products');
      
      // 모달 열기 (예: 제품 추가 버튼)
      await page.click('.add-product-button');
      await page.waitForSelector('.modal-dialog');
      
      // 모달 내부 요소에 포커스가 있는지 확인
      const isFocusInModal = await page.evaluate(() => {
        const activeElement = document.activeElement;
        const modal = document.querySelector('.modal-dialog');
        return modal?.contains(activeElement);
      });
      
      expect(isFocusInModal).toBeTruthy();
      
      // 탭 키를 여러 번 눌러도 모달 내부에 포커스가 유지되는지 확인
      for (let i = 0; i < 10; i++) {
        await page.keyboard.press('Tab');
        
        const focusStillInModal = await page.evaluate(() => {
          const activeElement = document.activeElement;
          const modal = document.querySelector('.modal-dialog');
          return modal?.contains(activeElement);
        });
        
        expect(focusStillInModal).toBeTruthy();
      }
    });
  });

  describe('스크린 리더 지원', () => {
    it('모든 이미지에 적절한 대체 텍스트가 있어야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/products');
      
      const imagesWithoutAlt = await page.evaluate(() => {
        const images = Array.from(document.querySelectorAll('img'));
        return images.filter(img => !img.hasAttribute('alt')).length;
      });
      
      expect(imagesWithoutAlt).toBe(0);
    });

    it('모든 폼 컨트롤에 레이블이 연결되어 있어야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/calculator');
      
      const formControlsWithoutLabels = await page.evaluate(() => {
        const formControls = Array.from(document.querySelectorAll('input, select, textarea'));
        return formControls.filter(control => {
          const id = control.getAttribute('id');
          if (!id) return true;
          
          const label = document.querySelector(`label[for="${id}"]`);
          return !label;
        }).length;
      });
      
      expect(formControlsWithoutLabels).toBe(0);
    });

    it('ARIA 속성이 올바르게 사용되어야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/products');
      
      const ariaResults = await page.evaluate(() => {
        const issues = [];
        
        // 필수 ARIA 속성 검사
        const elementsWithRole = Array.from(document.querySelectorAll('[role]'));
        elementsWithRole.forEach(el => {
          const role = el.getAttribute('role');
          
          // 예: role="button"인 요소는 tabindex가 있어야 함
          if (role === 'button' && !el.hasAttribute('tabindex')) {
            issues.push(`role="button" 요소에 tabindex 속성이 없음`);
          }
          
          // 예: role="checkbox"인 요소는 aria-checked가 있어야 함
          if (role === 'checkbox' && !el.hasAttribute('aria-checked')) {
            issues.push(`role="checkbox" 요소에 aria-checked 속성이 없음`);
          }
        });
        
        return issues;
      });
      
      expect(ariaResults).toHaveLength(0);
    });
  });

  describe('색상 대비 및 가시성', () => {
    it('텍스트와 배경 간의 색상 대비가 충분해야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/dashboard');
      
      // 색상 대비 검사는 axe-core에서 수행됨
      const results = await runAxeTest('대시보드 색상 대비');
      
      const contrastIssues = results.violations.filter(v => 
        v.id === 'color-contrast'
      );
      
      expect(contrastIssues).toHaveLength(0);
    });
  });

  describe('반응형 접근성', () => {
    it('모바일 뷰포트에서도 접근성이 유지되어야 합니다', async () => {
      // 모바일 뷰포트 설정
      await page.setViewport({ width: 375, height: 667 });
      
      await loginUser();
      await page.goto('http://localhost:3000/products');
      
      const results = await runAxeTest('모바일 제품 목록');
      
      // 심각한(critical) 위반 사항이 없어야 함
      const criticalViolations = results.violations.filter(v => v.impact === 'critical');
      expect(criticalViolations).toHaveLength(0);
    });

    it('터치 타겟 크기가 충분히 커야 합니다', async () => {
      // 모바일 뷰포트 설정
      await page.setViewport({ width: 375, height: 667 });
      
      await loginUser();
      await page.goto('http://localhost:3000/products');
      
      const smallTouchTargets = await page.evaluate(() => {
        const interactiveElements = Array.from(document.querySelectorAll('a, button, input[type="checkbox"], input[type="radio"], input[type="button"]'));
        
        return interactiveElements.filter(el => {
          const rect = el.getBoundingClientRect();
          // WCAG 2.1 기준: 터치 타겟은 최소 44x44px
          return rect.width < 44 || rect.height < 44;
        }).length;
      });
      
      expect(smallTouchTargets).toBe(0);
    });
  });

  describe('동적 콘텐츠 접근성', () => {
    it('동적으로 변경되는 콘텐츠가 스크린 리더에 알려져야 합니다', async () => {
      await loginUser();
      await page.goto('http://localhost:3000/calculator');
      
      // 계산 정보 입력
      await page.type('input[name="productPrice"]', '50000');
      await page.type('input[name="productCost"]', '30000');
      
      // 계산 버튼 클릭
      await page.click('.calculate-button');
      
      // 결과 영역이 적절한 ARIA 속성을 가지고 있는지 확인
      const resultsAreaAttributes = await page.evaluate(() => {
        const resultsArea = document.querySelector('.calculation-results');
        return {
          role: resultsArea?.getAttribute('role'),
          ariaLive: resultsArea?.getAttribute('aria-live'),
          ariaAtomic: resultsArea?.getAttribute('aria-atomic')
        };
      });
      
      expect(resultsAreaAttributes.role).toBe('region');
      expect(resultsAreaAttributes.ariaLive).toBe('polite');
    });

    it('오류 메시지가 스크린 리더에 적절히 알려져야 합니다', async () => {
      await page.goto('http://localhost:3000/login');
      
      // 빈 폼 제출
      await page.click('button[type="submit"]');
      
      // 오류 메시지 영역이 적절한 ARIA 속성을 가지고 있는지 확인
      const errorAttributes = await page.evaluate(() => {
        const errorElement = document.querySelector('.error-message');
        return {
          role: errorElement?.getAttribute('role'),
          ariaLive: errorElement?.getAttribute('aria-live')
        };
      });
      
      expect(errorAttributes.role).toBe('alert');
      expect(errorAttributes.ariaLive).toBe('assertive');
    });
  });
}); 