import React from 'react';
import { RouteObject } from 'react-router-dom';
import { Settings as SettingsIcon } from '@mui/icons-material';
import Settings from '@/pages/Settings/Settings';

export const settingsRoutes: RouteObject[] = [
  {
    path: '/settings',
    element: <Settings />,
  }
];

export const settingsNavItems = [
  {
    title: '설정',
    path: '/settings',
    icon: <SettingsIcon />,
    requiresAuth: true
  }
]; 