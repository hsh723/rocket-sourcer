import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import { ThemeProvider } from '@/context/ThemeContext';
import { AuthProvider } from '@/context/AuthContext';
import Settings from '@/pages/Settings/Settings';

describe('Settings Integration', () => {
  const mockSettingsService = {
    getThemeSettings: jest.fn(),
    updateThemeSettings: jest.fn(),
    getLanguageSettings: jest.fn(),
    updateLanguageSettings: jest.fn(),
    getAPISettings: jest.fn(),
    updateAPISettings: jest.fn(),
    getNotificationSettings: jest.fn(),
    updateNotificationSettings: jest.fn(),
    getBackups: jest.fn(),
    createBackup: jest.fn(),
    restoreBackup: jest.fn(),
    exportData: jest.fn(),
    importData: jest.fn()
  };

  beforeEach(() => {
    jest.clearAllMocks();
    // 기본 설정값으로 모킹
    mockSettingsService.getThemeSettings.mockResolvedValue({
      mode: 'light',
      colorScheme: '기본',
      isDense: false,
      fontSize: 14,
      borderRadius: 4
    });
    mockSettingsService.getLanguageSettings.mockResolvedValue({
      language: 'ko',
      dateFormat: 'YYYY-MM-DD',
      timeFormat: '24',
      timezone: 'Asia/Seoul'
    });
  });

  const renderSettings = () => {
    return render(
      <BrowserRouter>
        <AuthProvider>
          <ThemeProvider>
            <Settings />
          </ThemeProvider>
        </AuthProvider>
      </BrowserRouter>
    );
  };

  it('모든 설정 탭이 올바르게 작동해야 합니다', async () => {
    renderSettings();

    // 테마 설정 탭
    expect(screen.getByText(/테마 설정/i)).toBeInTheDocument();
    
    // 언어 설정 탭으로 이동
    const languageTab = screen.getByRole('tab', { name: /언어 및 지역/i });
    await userEvent.click(languageTab);
    expect(screen.getByText(/언어 및 지역 설정/i)).toBeInTheDocument();

    // API 설정 탭으로 이동
    const apiTab = screen.getByRole('tab', { name: /API 설정/i });
    await userEvent.click(apiTab);
    expect(screen.getByText(/API 설정/i)).toBeInTheDocument();

    // 알림 설정 탭으로 이동
    const notificationTab = screen.getByRole('tab', { name: /알림/i });
    await userEvent.click(notificationTab);
    expect(screen.getByText(/알림 설정/i)).toBeInTheDocument();
  });

  it('테마 설정이 전체 애플리케이션에 적용되어야 합니다', async () => {
    renderSettings();

    // 다크 모드로 변경
    const darkModeRadio = screen.getByLabelText(/다크 모드/i);
    await userEvent.click(darkModeRadio);
    
    // 설정 저장
    const saveButton = screen.getByRole('button', { name: /저장/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(document.body).toHaveClass('dark-mode');
      expect(screen.getByText(/테마 설정이 저장되었습니다/i)).toBeInTheDocument();
    });
  });

  it('언어 설정 변경이 즉시 적용되어야 합니다', async () => {
    renderSettings();

    // 언어 설정 탭으로 이동
    const languageTab = screen.getByRole('tab', { name: /언어 및 지역/i });
    await userEvent.click(languageTab);

    // 언어를 영어로 변경
    const languageSelect = screen.getByLabelText(/언어/i);
    await userEvent.selectOptions(languageSelect, 'en');

    // 설정 저장
    const saveButton = screen.getByRole('button', { name: /저장/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(screen.getByText(/Language settings saved/i)).toBeInTheDocument();
    });
  });

  it('백업 및 복원 프로세스가 올바르게 작동해야 합니다', async () => {
    mockSettingsService.getBackups.mockResolvedValue([
      {
        id: '1',
        filename: 'backup-2024-01-01.zip',
        created_at: '2024-01-01T00:00:00Z'
      }
    ]);

    renderSettings();

    // 백업 탭으로 이동
    const backupTab = screen.getByRole('tab', { name: /백업/i });
    await userEvent.click(backupTab);

    // 새 백업 생성
    const createButton = screen.getByRole('button', { name: /새 백업 생성/i });
    await userEvent.click(createButton);

    await waitFor(() => {
      expect(mockSettingsService.createBackup).toHaveBeenCalled();
      expect(screen.getByText(/백업이 생성되었습니다/i)).toBeInTheDocument();
    });

    // 백업 복원
    const restoreButton = screen.getByRole('button', { name: /복원/i });
    await userEvent.click(restoreButton);

    // 확인 대화상자
    const confirmButton = screen.getByRole('button', { name: /확인/i });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(mockSettingsService.restoreBackup).toHaveBeenCalled();
      expect(screen.getByText(/백업이 복원되었습니다/i)).toBeInTheDocument();
    });
  });

  it('데이터 가져오기/내보내기가 올바르게 작동해야 합니다', async () => {
    renderSettings();

    // 가져오기/내보내기 탭으로 이동
    const importExportTab = screen.getByRole('tab', { name: /가져오기\/내보내기/i });
    await userEvent.click(importExportTab);

    // 내보내기 옵션 선택
    await userEvent.click(screen.getByLabelText(/제품 데이터/i));
    await userEvent.selectOptions(screen.getByLabelText(/내보내기 형식/i), 'excel');

    // 내보내기 실행
    const exportButton = screen.getByRole('button', { name: /내보내기/i });
    await userEvent.click(exportButton);

    await waitFor(() => {
      expect(mockSettingsService.exportData).toHaveBeenCalled();
    });

    // 파일 가져오기
    const file = new File(['test data'], 'test.xlsx', { type: 'application/vnd.ms-excel' });
    const input = screen.getByTestId('file-input');
    await userEvent.upload(input, file);

    const importButton = screen.getByRole('button', { name: /가져오기/i });
    await userEvent.click(importButton);

    await waitFor(() => {
      expect(mockSettingsService.importData).toHaveBeenCalled();
      expect(screen.getByText(/데이터가 성공적으로 가져와졌습니다/i)).toBeInTheDocument();
    });
  });
}); 