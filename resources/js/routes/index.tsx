import { lazy } from 'react';
import { useRoutes } from 'react-router-dom';
import { createBrowserRouter } from 'react-router-dom';

import { MainLayout } from '@/layouts/MainLayout';
import { AuthLayout } from '@/layouts/AuthLayout';
import { ProtectedRoute } from '@/components/ProtectedRoute';
import ProductAnalysis from '@/pages/Analysis/ProductAnalysis';
import ProductDetail from '@/pages/Products/ProductDetail';
import ProductCompare from '@/pages/Products/ProductCompare';
import SavedResults from '@/pages/Calculator/SavedResults';
import { authRoutes } from './auth.routes';
import { productRoutes } from './product.routes';
import { calculatorRoutes } from './calculator.routes';
import { settingsRoutes } from './settings.routes';

const HomePage = lazy(() => import('@/pages/Home'));
const LoginPage = lazy(() => import('@/pages/Login'));
const RegisterPage = lazy(() => import('@/pages/Register'));
const DashboardPage = lazy(() => import('@/pages/Dashboard'));
const ProductsPage = lazy(() => import('@/pages/Products'));
const AnalyticsPage = lazy(() => import('@/pages/Analytics'));
const SettingsPage = lazy(() => import('@/pages/Settings'));
const NotFoundPage = lazy(() => import('@/pages/NotFound'));

export function AppRoutes() {
  const routes = useRoutes([
    {
      element: <AuthLayout />,
      children: [
        { path: '/login', element: <LoginPage /> },
        { path: '/register', element: <RegisterPage /> },
      ],
    },
    {
      element: <MainLayout />,
      children: [
        { path: '/', element: <HomePage /> },
        {
          path: '/dashboard',
          element: (
            <ProtectedRoute>
              <DashboardPage />
            </ProtectedRoute>
          ),
        },
        {
          path: '/products',
          element: (
            <ProtectedRoute>
              <ProductsPage />
            </ProtectedRoute>
          ),
        },
        {
          path: '/analytics',
          element: (
            <ProtectedRoute>
              <AnalyticsPage />
            </ProtectedRoute>
          ),
        },
        {
          path: '/settings',
          element: (
            <ProtectedRoute>
              <SettingsPage />
            </ProtectedRoute>
          ),
        },
        {
          path: '/products/:id/analysis',
          element: <ProductAnalysis />
        },
        {
          path: '/products/:id',
          element: (
            <ProtectedRoute>
              <ProductDetail />
            </ProtectedRoute>
          )
        },
        {
          path: '/products/compare',
          element: (
            <ProtectedRoute>
              <ProductCompare />
            </ProtectedRoute>
          )
        },
        {
          path: '/calculator/saved',
          element: (
            <ProtectedRoute>
              <SavedResults />
            </ProtectedRoute>
          )
        },
      ],
    },
    { path: '*', element: <NotFoundPage /> },
  ]);

  return routes;
}

export const router = createBrowserRouter([
  {
    element: <AuthLayout />,
    children: authRoutes
  },
  {
    element: <MainLayout />,
    children: [
      ...productRoutes,
      ...calculatorRoutes,
      ...settingsRoutes
    ]
  }
]);

export const navItems = [
  ...productRoutes.map(route => route.handle?.navItem).filter(Boolean),
  ...calculatorRoutes.map(route => route.handle?.navItem).filter(Boolean),
  ...settingsRoutes.map(route => route.handle?.navItem).filter(Boolean)
]; 