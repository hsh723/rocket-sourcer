import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { dashboardService, DashboardParams } from '@/services/dashboardService';

export function useDashboard(params?: DashboardParams) {
  const queryClient = useQueryClient();

  const { data, isLoading, error } = useQuery({
    queryKey: ['dashboard', params],
    queryFn: async () => {
      const [summary, trends, recentKeywords, recentProducts, notifications] =
        await Promise.all([
          dashboardService.getSummary(params),
          dashboardService.getTrends(params),
          dashboardService.getRecentKeywords(),
          dashboardService.getRecentProducts(),
          dashboardService.getNotifications(),
        ]);

      return {
        summary: summary.data,
        trends: trends.data,
        recentKeywords: recentKeywords.data,
        recentProducts: recentProducts.data,
        notifications: notifications.data,
      };
    },
  });

  const dismissNotification = useMutation({
    mutationFn: dashboardService.dismissNotification,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    },
  });

  return {
    data,
    isLoading,
    error,
    dismissNotification: dismissNotification.mutate,
  };
} 