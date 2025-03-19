import { describe, expect, it, beforeEach } from '@jest/globals';
import { render, screen, fireEvent, act } from '@testing-library/react';
import { ProductList } from '@/pages/Products/ProductList';
import { ProfitCalculator } from '@/pages/Calculator/ProfitCalculator';
import { createTestUser } from '@/tests/helpers/auth';
import { ProductProvider } from '@/context/ProductContext';
import { CalculatorProvider } from '@/context/CalculatorContext';
import { measurePerformance, getFirstContentfulPaint } from '@/tests/helpers/performance';

describe('프론트엔드 성능 테스트', () => {
  let testUser: any;

  beforeEach(async () => {
    testUser = await createTestUser();
  });

  describe('페이지 로딩 성능', () => {
    it('제품 목록 페이지가 빠르게 로드되어야 합니다', async () => {
      const { timeToFirstByte, firstContentfulPaint } = await measurePerformance(() => {
        render(
          <ProductProvider>
            <ProductList />
          </ProductProvider>
        );
      });

      expect(timeToFirstByte).toBeLessThan(100); // 100ms 이내
      expect(firstContentfulPaint).toBeLessThan(300); // 300ms 이내
    });

    it('수익성 계산기 페이지가 빠르게 로드되어야 합니다', async () => {
      const { timeToFirstByte, firstContentfulPaint } = await measurePerformance(() => {
        render(
          <CalculatorProvider>
            <ProfitCalculator />
          </CalculatorProvider>
        );
      });

      expect(timeToFirstByte).toBeLessThan(100);
      expect(firstContentfulPaint).toBeLessThan(300);
    });
  });

  describe('사용자 인터랙션 성능', () => {
    it('제품 검색이 지연 없이 동작해야 합니다', async () => {
      render(
        <ProductProvider>
          <ProductList />
        </ProductProvider>
      );

      const searchInput = screen.getByRole('textbox', { name: /검색/i });
      const start = performance.now();

      await act(async () => {
        fireEvent.change(searchInput, { target: { value: '테스트 제품' } });
        // 디바운스 대기
        await new Promise(resolve => setTimeout(resolve, 300));
      });

      const end = performance.now();
      const responseTime = end - start - 300; // 디바운스 시간 제외

      expect(responseTime).toBeLessThan(200); // 200ms 이내 응답
    });

    it('필터 적용이 빠르게 처리되어야 합니다', async () => {
      render(
        <ProductProvider>
          <ProductList />
        </ProductProvider>
      );

      const filterButton = screen.getByRole('button', { name: /필터/i });
      const start = performance.now();

      await act(async () => {
        fireEvent.click(filterButton);
        const categorySelect = screen.getByRole('combobox', { name: /카테고리/i });
        fireEvent.change(categorySelect, { target: { value: 'A' } });
      });

      const end = performance.now();
      expect(end - start).toBeLessThan(100); // 100ms 이내 처리
    });
  });

  describe('데이터 렌더링 성능', () => {
    it('대량의 제품 데이터를 효율적으로 렌더링해야 합니다', async () => {
      const products = Array.from({ length: 100 }, (_, i) => ({
        id: i + 1,
        name: `제품 ${i + 1}`,
        price: 10000,
        category: 'A'
      }));

      const start = performance.now();

      render(
        <ProductProvider initialProducts={products}>
          <ProductList />
        </ProductProvider>
      );

      const end = performance.now();
      expect(end - start).toBeLessThan(500); // 500ms 이내 렌더링
    });

    it('차트 데이터를 빠르게 렌더링해야 합니다', async () => {
      const calculationResults = {
        monthlyProfit: 1000000,
        profitMargin: 0.3,
        roi: 0.5,
        breakEvenPoint: 100,
        monthlyData: Array.from({ length: 12 }, (_, i) => ({
          month: i + 1,
          profit: Math.random() * 1000000
        }))
      };

      const start = performance.now();

      render(
        <CalculatorProvider initialResults={calculationResults}>
          <ProfitCalculator />
        </CalculatorProvider>
      );

      const end = performance.now();
      expect(end - start).toBeLessThan(300); // 300ms 이내 렌더링
    });
  });

  describe('메모리 사용량', () => {
    it('제품 목록 페이지의 메모리 사용량이 적절해야 합니다', async () => {
      const memoryBefore = performance.memory?.usedJSHeapSize;
      
      render(
        <ProductProvider>
          <ProductList />
        </ProductProvider>
      );

      const memoryAfter = performance.memory?.usedJSHeapSize;
      const memoryUsage = memoryAfter - memoryBefore;

      expect(memoryUsage).toBeLessThan(5 * 1024 * 1024); // 5MB 이내
    });

    it('수익성 계산 시뮬레이션의 메모리 사용량이 적절해야 합니다', async () => {
      const memoryBefore = performance.memory?.usedJSHeapSize;

      render(
        <CalculatorProvider>
          <ProfitCalculator />
        </CalculatorProvider>
      );

      // 시뮬레이션 실행
      const calculateButton = screen.getByRole('button', { name: /계산/i });
      await act(async () => {
        fireEvent.click(calculateButton);
      });

      const memoryAfter = performance.memory?.usedJSHeapSize;
      const memoryUsage = memoryAfter - memoryBefore;

      expect(memoryUsage).toBeLessThan(10 * 1024 * 1024); // 10MB 이내
    });
  });

  describe('리렌더링 최적화', () => {
    it('불필요한 리렌더링이 발생하지 않아야 합니다', async () => {
      let renderCount = 0;
      const TestComponent = () => {
        renderCount++;
        return null;
      };

      render(
        <ProductProvider>
          <ProductList />
          <TestComponent />
        </ProductProvider>
      );

      // 검색어 변경
      const searchInput = screen.getByRole('textbox', { name: /검색/i });
      await act(async () => {
        fireEvent.change(searchInput, { target: { value: '테스트' } });
      });

      expect(renderCount).toBeLessThan(3); // 초기 렌더링 + 검색어 변경 시 1회
    });
  });
}); 