import { useQuery } from '@tanstack/react-query';
import { keywordService } from '@/services/keywordService';

export function useKeywordDetail(id: number) {
  const { data, isLoading, error } = useQuery({
    queryKey: ['keyword', id],
    queryFn: async () => {
      const [keyword, history, competitors, products] = await Promise.all([
        keywordService.getKeyword(id),
        keywordService.getKeywordHistory(id),
        keywordService.getKeywordCompetitors(id),
        keywordService.getKeywordProducts(id),
      ]);

      return {
        ...keyword.data,
        history: history.data,
        competitors: competitors.data,
        products: products.data,
      };
    },
    enabled: Boolean(id),
  });

  return {
    data,
    isLoading,
    error,
  };
} 