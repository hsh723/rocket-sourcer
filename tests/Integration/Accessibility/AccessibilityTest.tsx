import { describe, expect, it, beforeEach } from '@jest/globals';
import { render, screen, fireEvent, act } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import puppeteer from 'puppeteer';
import { ProductList } from '@/pages/Products/ProductList';
import { ProfitCalculator } from '@/pages/Calculator/ProfitCalculator';
import { createTestUser } from '@/tests/helpers/auth';

expect.extend(toHaveNoViolations);

describe('접근성 테스트', () => {
  let testUser: any;

  beforeEach(async () => {
    testUser = await createTestUser();
  });

  describe('WCAG 준수 여부', () => {
    it('제품 목록 페이지가 WCAG 기준을 준수해야 합니다', async () => {
      const { container } = render(
        <ProductList />
      );

      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it('수익성 계산기 페이지가 WCAG 기준을 준수해야 합니다', async () => {
      const { container } = render(
        <ProfitCalculator />
      );

      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
  });

  describe('키보드 접근성', () => {
    it('모든 상호작용 요소에 키보드로 접근 가능해야 합니다', async () => {
      render(<ProductList />);

      const interactiveElements = screen.getAllByRole('button');
      interactiveElements.push(...screen.getAllByRole('link'));
      interactiveElements.push(...screen.getAllByRole('textbox'));

      for (const element of interactiveElements) {
        element.focus();
        expect(document.activeElement).toBe(element);
      }
    });

    it('키보드 탐색 순서가 논리적이어야 합니다', async () => {
      render(<ProfitCalculator />);

      const tabOrder = [];
      const elements = document.querySelectorAll('button, a, input, select');
      
      elements.forEach(el => {
        const tabIndex = el.getAttribute('tabindex');
        if (tabIndex !== '-1') {
          tabOrder.push({
            element: el,
            tabIndex: tabIndex ? parseInt(tabIndex) : 0
          });
        }
      });

      // 탭 순서 검증
      tabOrder.sort((a, b) => a.tabIndex - b.tabIndex);
      for (let i = 1; i < tabOrder.length; i++) {
        const prev = tabOrder[i - 1].element.getBoundingClientRect();
        const current = tabOrder[i].element.getBoundingClientRect();
        
        // 위에서 아래로, 왼쪽에서 오른쪽으로 진행되어야 함
        expect(
          current.top >= prev.top || current.left >= prev.left
        ).toBeTruthy();
      }
    });
  });

  describe('스크린 리더 지원', () => {
    it('모든 이미지에 적절한 대체 텍스트가 있어야 합니다', async () => {
      render(<ProductList />);

      const images = screen.getAllByRole('img');
      images.forEach(img => {
        expect(img).toHaveAttribute('alt');
        expect(img.getAttribute('alt')).not.toBe('');
      });
    });

    it('폼 컨트롤에 적절한 레이블이 있어야 합니다', async () => {
      render(<ProfitCalculator />);

      const formControls = screen.getAllByRole('textbox');
      formControls.push(...screen.getAllByRole('combobox'));
      
      formControls.forEach(control => {
        expect(control).toHaveAccessibleName();
      });
    });

    it('동적 콘텐츠 변경이 스크린 리더에 알려져야 합니다', async () => {
      render(<ProfitCalculator />);

      const calculateButton = screen.getByRole('button', { name: /계산/i });
      
      await act(async () => {
        fireEvent.click(calculateButton);
      });

      const results = screen.getByRole('region', { name: /계산 결과/i });
      expect(results).toHaveAttribute('aria-live', 'polite');
    });
  });

  describe('색상 대비 및 가시성', () => {
    it('모든 텍스트가 충분한 색상 대비를 가져야 합니다', async () => {
      const browser = await puppeteer.launch();
      const page = await browser.newPage();
      
      await page.goto('http://localhost:3000/products');
      
      const contrastIssues = await page.evaluate(() => {
        const issues = [];
        const elements = document.querySelectorAll('*');
        
        elements.forEach(el => {
          if (el.innerText) {
            const style = window.getComputedStyle(el);
            const backgroundColor = style.backgroundColor;
            const color = style.color;
            
            // 색상 대비 계산 (실제로는 더 복잡한 계산이 필요)
            // 여기서는 간단한 예시만 구현
            const contrast = calculateContrast(color, backgroundColor);
            if (contrast < 4.5) { // WCAG AA 기준
              issues.push(`낮은 색상 대비: ${el.tagName} - ${contrast}`);
            }
          }
        });
        
        return issues;
      });

      expect(contrastIssues).toHaveLength(0);
      await browser.close();
    });
  });

  describe('반응형 접근성', () => {
    it('모든 뷰포트 크기에서 콘텐츠가 접근 가능해야 합니다', async () => {
      const browser = await puppeteer.launch();
      const page = await browser.newPage();
      
      const viewports = [
        { width: 320, height: 568 }, // 모바일
        { width: 768, height: 1024 }, // 태블릿
        { width: 1024, height: 768 }, // 데스크톱
      ];

      for (const viewport of viewports) {
        await page.setViewport(viewport);
        await page.goto('http://localhost:3000/products');

        const accessibilityIssues = await page.evaluate(async () => {
          const issues = [];
          
          // 터치 타겟 크기 확인
          const clickableElements = document.querySelectorAll('button, a, input, select');
          clickableElements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.width < 44 || rect.height < 44) { // WCAG 터치 타겟 크기 기준
              issues.push(`작은 터치 타겟: ${el.tagName} - ${rect.width}x${rect.height}`);
            }
          });

          // 텍스트 크기 확인
          const textElements = document.querySelectorAll('p, span, h1, h2, h3, h4, h5, h6');
          textElements.forEach(el => {
            const fontSize = window.getComputedStyle(el).fontSize;
            if (parseFloat(fontSize) < 16) { // 최소 텍스트 크기
              issues.push(`작은 텍스트 크기: ${el.tagName} - ${fontSize}`);
            }
          });

          return issues;
        });

        expect(accessibilityIssues).toHaveLength(0);
      }

      await browser.close();
    });
  });
}); 