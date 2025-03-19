import api from './api';

export interface DashboardParams {
  period?: 'day' | 'week' | 'month' | 'year';
}

export const dashboardService = {
  getSummary: (params?: DashboardParams) =>
    api.get('/dashboard/summary', { params }),

  getTrends: (params?: DashboardParams) =>
    api.get('/dashboard/trends', { params }),

  getRecentKeywords: () =>
    api.get('/dashboard/recent-keywords'),

  getRecentProducts: () =>
    api.get('/dashboard/recent-products'),

  getNotifications: () =>
    api.get('/dashboard/notifications'),

  dismissNotification: (id: number) =>
    api.delete(`/dashboard/notifications/${id}`),

  getStats: (params?: DashboardParams) =>
    api.get('/dashboard/stats', { params }),
}; 