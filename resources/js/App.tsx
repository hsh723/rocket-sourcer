import React, { Suspense } from 'react';
import { Routes, Route } from 'react-router-dom';
import { CircularProgress } from '@mui/material';
import MainLayout from '@layouts/MainLayout';
import AuthLayout from '@layouts/AuthLayout';
import ProtectedRoute from '@components/ProtectedRoute';
import { useAuth } from '@hooks/useAuth';

// Lazy loaded pages
const Dashboard = React.lazy(() => import('@pages/Dashboard'));
const Products = React.lazy(() => import('@pages/Products'));
const Keywords = React.lazy(() => import('@pages/Keywords'));
const Analysis = React.lazy(() => import('@pages/Analysis'));
const Login = React.lazy(() => import('@pages/Login'));
const Register = React.lazy(() => import('@pages/Register'));

const App: React.FC = () => {
  const { isAuthenticated } = useAuth();

  return (
    <Suspense fallback={<CircularProgress />}>
      <Routes>
        {/* Public routes */}
        <Route element={<AuthLayout />}>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
        </Route>

        {/* Protected routes */}
        <Route element={<ProtectedRoute isAuthenticated={isAuthenticated} />}>
          <Route element={<MainLayout />}>
            <Route path="/" element={<Dashboard />} />
            <Route path="/products" element={<Products />} />
            <Route path="/keywords" element={<Keywords />} />
            <Route path="/analysis" element={<Analysis />} />
          </Route>
        </Route>
      </Routes>
    </Suspense>
  );
};

export default App; 