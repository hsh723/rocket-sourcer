import React from 'react';
import {
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  ListItemButton,
  Collapse,
  Divider
} from '@mui/material';
import { useLocation, useNavigate } from 'react-router-dom';
import { navItems } from '@/routes';
import { useAuth } from '@/hooks/useAuth';

const Navigation: React.FC = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { user } = useAuth();

  const handleNavigation = (path: string) => {
    navigate(path);
  };

  const filteredNavItems = navItems.filter(item => {
    if (item.requiresAuth && !user) return false;
    return true;
  });

  return (
    <List component="nav">
      {filteredNavItems.map((item, index) => (
        <React.Fragment key={item.path}>
          {index > 0 && index % 3 === 0 && <Divider sx={{ my: 1 }} />}
          <ListItem disablePadding>
            <ListItemButton
              selected={location.pathname === item.path}
              onClick={() => handleNavigation(item.path)}
            >
              <ListItemIcon>
                {item.icon}
              </ListItemIcon>
              <ListItemText primary={item.title} />
            </ListItemButton>
          </ListItem>
        </React.Fragment>
      ))}
    </List>
  );
};

export default Navigation; 