import { describe, expect, it, beforeEach } from '@jest/globals';
import { CalculatorService } from '@/services/CalculatorService';
import { ProfitCalculation } from '@/types/Calculation';

describe('CalculatorService', () => {
  let service: CalculatorService;
  
  beforeEach(() => {
    service = new CalculatorService();
  });

  describe('calculateProfit', () => {
    it('올바른 수익 계산 결과를 반환해야 합니다', () => {
      const input: ProfitCalculation = {
        purchasePrice: 10000,
        sellingPrice: 15000,
        shippingCost: 2000,
        marketplaceFee: 0.1,
        marketingCost: 1000,
        quantity: 100
      };

      const result = service.calculateProfit(input);

      expect(result).toHaveProperty('totalRevenue');
      expect(result).toHaveProperty('totalCost');
      expect(result).toHaveProperty('grossProfit');
      expect(result).toHaveProperty('netProfit');
      expect(result).toHaveProperty('roi');
      expect(result.netProfit).toBeGreaterThan(0);
    });

    it('손실이 발생하는 경우 음수 수익을 반환해야 합니다', () => {
      const input: ProfitCalculation = {
        purchasePrice: 15000,
        sellingPrice: 10000,
        shippingCost: 2000,
        marketplaceFee: 0.1,
        marketingCost: 1000,
        quantity: 100
      };

      const result = service.calculateProfit(input);
      expect(result.netProfit).toBeLessThan(0);
    });
  });

  describe('calculateBreakEven', () => {
    it('손익분기점을 올바르게 계산해야 합니다', () => {
      const input = {
        fixedCosts: 100000,
        variableCostPerUnit: 5000,
        sellingPricePerUnit: 10000
      };

      const result = service.calculateBreakEven(input);
      expect(result).toHaveProperty('breakEvenUnits');
      expect(result).toHaveProperty('breakEvenRevenue');
      expect(result.breakEvenUnits).toBeGreaterThan(0);
    });
  });
}); 