import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { ProductRecommendationService } from '@/services/ProductRecommendationService';
import { Product } from '@/types/Product';

describe('ProductRecommendationService', () => {
  let service: ProductRecommendationService;
  
  beforeEach(() => {
    service = new ProductRecommendationService();
  });

  describe('getRecommendations', () => {
    it('상품 추천 목록을 반환해야 합니다', async () => {
      const mockProducts: Product[] = [
        {
          id: '1',
          name: '테스트 상품 1',
          price: 10000,
          marketPrice: 15000,
          salesVolume: 100,
          rating: 4.5,
          reviewCount: 50
        },
        {
          id: '2',
          name: '테스트 상품 2',
          price: 20000,
          marketPrice: 25000,
          salesVolume: 200,
          rating: 4.8,
          reviewCount: 100
        }
      ];

      jest.spyOn(service as any, 'searchProducts').mockResolvedValue(mockProducts);
      jest.spyOn(service as any, 'analyzeAndScoreProducts').mockImplementation(
        (products) => products.map(p => ({ ...p, score: 0.8 }))
      );

      const result = await service.getRecommendations({
        minPrice: 5000,
        maxPrice: 30000,
        category: '전자기기'
      });

      expect(result).toHaveLength(2);
      expect(result[0]).toHaveProperty('score');
      expect(result[1]).toHaveProperty('score');
    });
  });

  describe('calculateProfitScore', () => {
    it('수익성 점수를 올바르게 계산해야 합니다', () => {
      const product = {
        price: 10000,
        marketPrice: 15000,
        salesVolume: 100
      };

      const score = service['calculateProfitScore'](product);
      expect(score).toBeGreaterThanOrEqual(0);
      expect(score).toBeLessThanOrEqual(1);
    });
  });

  describe('calculateCompetitionScore', () => {
    it('경쟁력 점수를 올바르게 계산해야 합니다', () => {
      const product = {
        competitorCount: 5,
        marketShare: 0.2,
        priceCompetitiveness: 0.8
      };

      const score = service['calculateCompetitionScore'](product);
      expect(score).toBeGreaterThanOrEqual(0);
      expect(score).toBeLessThanOrEqual(1);
    });
  });
}); 