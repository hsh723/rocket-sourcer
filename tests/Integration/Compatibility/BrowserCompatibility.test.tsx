import { describe, expect, it, beforeEach } from '@jest/globals';
import { render, screen, fireEvent, act } from '@testing-library/react';
import puppeteer, { Browser, Page } from 'puppeteer';
import { ProductList } from '@/pages/Products/ProductList';
import { ProfitCalculator } from '@/pages/Calculator/ProfitCalculator';
import { createTestUser } from '@/tests/helpers/auth';

describe('브라우저 호환성 테스트', () => {
  let browser: Browser;
  let page: Page;
  let testUser: any;

  const SUPPORTED_BROWSERS = [
    { name: 'Chrome', version: '90' },
    { name: 'Firefox', version: '88' },
    { name: 'Safari', version: '14' },
    { name: 'Edge', version: '91' }
  ];

  beforeEach(async () => {
    testUser = await createTestUser();
    browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox']
    });
    page = await browser.newPage();
  });

  afterEach(async () => {
    await browser.close();
  });

  describe('레이아웃 호환성', () => {
    it.each(SUPPORTED_BROWSERS)('$name $version에서 제품 목록 페이지가 올바르게 렌더링되어야 합니다', async ({ name, version }) => {
      await page.emulate(puppeteer.devices[`${name} ${version}`]);
      await page.goto('http://localhost:3000/products');

      // 레이아웃 검증
      const layoutIssues = await page.evaluate(() => {
        const issues = [];
        const elements = document.querySelectorAll('*');
        
        elements.forEach(el => {
          const style = window.getComputedStyle(el);
          const rect = el.getBoundingClientRect();
          
          // 요소가 화면을 벗어나는지 확인
          if (rect.right > window.innerWidth) {
            issues.push(`요소가 화면 너비를 벗어남: ${el.tagName}`);
          }
          
          // 텍스트 오버플로우 확인
          if (style.overflow === 'hidden' && el.scrollWidth > el.clientWidth) {
            issues.push(`텍스트 오버플로우 발생: ${el.tagName}`);
          }
        });
        
        return issues;
      });

      expect(layoutIssues).toHaveLength(0);
    });

    it.each(SUPPORTED_BROWSERS)('$name $version에서 수익성 계산기가 올바르게 렌더링되어야 합니다', async ({ name, version }) => {
      await page.emulate(puppeteer.devices[`${name} ${version}`]);
      await page.goto('http://localhost:3000/calculator');

      const visualRegressionIssues = await page.evaluate(() => {
        return document.querySelector('.calculator-container')?.getBoundingClientRect();
      });

      expect(visualRegressionIssues).toBeDefined();
    });
  });

  describe('기능 호환성', () => {
    it.each(SUPPORTED_BROWSERS)('$name $version에서 제품 검색이 정상 작동해야 합니다', async ({ name, version }) => {
      await page.emulate(puppeteer.devices[`${name} ${version}`]);
      await page.goto('http://localhost:3000/products');

      await page.type('input[name="search"]', '테스트 제품');
      await page.waitForSelector('.product-item');

      const searchResults = await page.$$('.product-item');
      expect(searchResults.length).toBeGreaterThan(0);
    });

    it.each(SUPPORTED_BROWSERS)('$name $version에서 수익성 계산이 정상 작동해야 합니다', async ({ name, version }) => {
      await page.emulate(puppeteer.devices[`${name} ${version}`]);
      await page.goto('http://localhost:3000/calculator');

      await page.type('input[name="productPrice"]', '50000');
      await page.type('input[name="productCost"]', '30000');
      await page.click('button[type="submit"]');

      const results = await page.waitForSelector('.calculation-results');
      expect(results).toBeTruthy();
    });
  });

  describe('성능 호환성', () => {
    it.each(SUPPORTED_BROWSERS)('$name $version에서 페이지 로딩 성능이 acceptable해야 합니다', async ({ name, version }) => {
      await page.emulate(puppeteer.devices[`${name} ${version}`]);
      
      const metrics = await page.goto('http://localhost:3000/products');
      const timing = JSON.parse(await page.evaluate(() => JSON.stringify(window.performance.timing)));
      
      const loadTime = timing.loadEventEnd - timing.navigationStart;
      expect(loadTime).toBeLessThan(3000); // 3초 이내
    });

    it.each(SUPPORTED_BROWSERS)('$name $version에서 인터랙션이 부드럽게 동작해야 합니다', async ({ name, version }) => {
      await page.emulate(puppeteer.devices[`${name} ${version}`]);
      await page.goto('http://localhost:3000/products');

      const metrics = await page.metrics();
      expect(metrics.JSHeapUsedSize).toBeLessThan(50 * 1024 * 1024); // 50MB 이내
    });
  });

  describe('폴리필 및 기능 지원', () => {
    it.each(SUPPORTED_BROWSERS)('$name $version에서 필수 JavaScript API들이 지원되어야 합니다', async ({ name, version }) => {
      await page.emulate(puppeteer.devices[`${name} ${version}`]);
      
      const apiSupport = await page.evaluate(() => {
        return {
          promiseSupport: typeof Promise !== 'undefined',
          fetchSupport: typeof fetch !== 'undefined',
          arrayMethodsSupport: typeof Array.prototype.find !== 'undefined' &&
                              typeof Array.prototype.includes !== 'undefined',
          objectMethodsSupport: typeof Object.entries !== 'undefined' &&
                               typeof Object.values !== 'undefined'
        };
      });

      expect(apiSupport).toEqual({
        promiseSupport: true,
        fetchSupport: true,
        arrayMethodsSupport: true,
        objectMethodsSupport: true
      });
    });

    it.each(SUPPORTED_BROWSERS)('$name $version에서 CSS 기능이 지원되어야 합니다', async ({ name, version }) => {
      await page.emulate(puppeteer.devices[`${name} ${version}`]);
      
      const cssSupport = await page.evaluate(() => {
        const div = document.createElement('div');
        return {
          flexboxSupport: 'flexBasis' in div.style,
          gridSupport: 'grid' in div.style,
          cssVariablesSupport: CSS.supports('--test', '0')
        };
      });

      expect(cssSupport).toEqual({
        flexboxSupport: true,
        gridSupport: true,
        cssVariablesSupport: true
      });
    });
  });
}); 