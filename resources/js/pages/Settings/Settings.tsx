import React from 'react';
import {
  Box,
  Container,
  Tabs,
  Tab,
  Typography,
  Breadcrumbs,
  Link,
  Paper
} from '@mui/material';
import {
  Settings as SettingsIcon,
  Palette as PaletteIcon,
  Language as LanguageIcon,
  Backup as BackupIcon,
  ImportExport as ImportExportIcon,
  Api as ApiIcon,
  Notifications as NotificationsIcon,
  Home as HomeIcon
} from '@mui/icons-material';
import { Link as RouterLink } from 'react-router-dom';

import ThemeSettings from '@/components/Settings/ThemeSettings';
import LanguageSettings from '@/components/Settings/LanguageSettings';
import BackupControls from '@/components/Settings/BackupControls';
import ImportExportControls from '@/components/Settings/ImportExportControls';
import APIForm from '@/components/Settings/APIForm';
import NotificationForm from '@/components/Settings/NotificationForm';

interface TabPanelProps {
  children?: React.ReactNode;
  index: number;
  value: number;
}

const TabPanel: React.FC<TabPanelProps> = (props) => {
  const { children, value, index, ...other } = props;

  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`settings-tabpanel-${index}`}
      aria-labelledby={`settings-tab-${index}`}
      {...other}
    >
      {value === index && (
        <Box sx={{ py: 3 }}>
          {children}
        </Box>
      )}
    </div>
  );
};

const Settings: React.FC = () => {
  const [activeTab, setActiveTab] = React.useState(0);

  const handleTabChange = (event: React.SyntheticEvent, newValue: number) => {
    setActiveTab(newValue);
  };

  return (
    <Container maxWidth="lg">
      <Box sx={{ mb: 4 }}>
        <Breadcrumbs aria-label="breadcrumb">
          <Link
            component={RouterLink}
            to="/"
            color="inherit"
            sx={{ display: 'flex', alignItems: 'center' }}
          >
            <HomeIcon sx={{ mr: 0.5 }} fontSize="inherit" />
            홈
          </Link>
          <Typography
            sx={{ display: 'flex', alignItems: 'center' }}
            color="text.primary"
          >
            <SettingsIcon sx={{ mr: 0.5 }} fontSize="inherit" />
            설정
          </Typography>
        </Breadcrumbs>
      </Box>

      <Paper sx={{ mb: 3 }}>
        <Tabs
          value={activeTab}
          onChange={handleTabChange}
          variant="scrollable"
          scrollButtons="auto"
          aria-label="설정 탭"
          sx={{ borderBottom: 1, borderColor: 'divider' }}
        >
          <Tab
            icon={<PaletteIcon />}
            label="테마"
            iconPosition="start"
          />
          <Tab
            icon={<LanguageIcon />}
            label="언어 및 지역"
            iconPosition="start"
          />
          <Tab
            icon={<ApiIcon />}
            label="API 설정"
            iconPosition="start"
          />
          <Tab
            icon={<NotificationsIcon />}
            label="알림"
            iconPosition="start"
          />
          <Tab
            icon={<BackupIcon />}
            label="백업"
            iconPosition="start"
          />
          <Tab
            icon={<ImportExportIcon />}
            label="가져오기/내보내기"
            iconPosition="start"
          />
        </Tabs>
      </Paper>

      <TabPanel value={activeTab} index={0}>
        <ThemeSettings />
      </TabPanel>
      <TabPanel value={activeTab} index={1}>
        <LanguageSettings />
      </TabPanel>
      <TabPanel value={activeTab} index={2}>
        <APIForm />
      </TabPanel>
      <TabPanel value={activeTab} index={3}>
        <NotificationForm />
      </TabPanel>
      <TabPanel value={activeTab} index={4}>
        <BackupControls />
      </TabPanel>
      <TabPanel value={activeTab} index={5}>
        <ImportExportControls />
      </TabPanel>
    </Container>
  );
};

export default Settings; 