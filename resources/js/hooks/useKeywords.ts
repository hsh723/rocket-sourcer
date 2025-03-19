import { useQuery } from '@tanstack/react-query';
import { keywordService, KeywordParams } from '@/services/keywordService';

export function useKeywords(params?: KeywordParams) {
  const { data, isLoading, error } = useQuery({
    queryKey: ['keywords', params],
    queryFn: async () => {
      const response = await keywordService.getKeywords(params);
      return response.data;
    },
  });

  return {
    data,
    isLoading,
    error,
  };
} 