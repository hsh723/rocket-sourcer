import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import LanguageSettings from '@/components/Settings/LanguageSettings';
import { useTranslation } from 'react-i18next';

jest.mock('react-i18next', () => ({
  useTranslation: () => ({
    i18n: {
      changeLanguage: jest.fn(),
      language: 'ko'
    },
    t: (str: string) => str
  })
}));

describe('LanguageSettings', () => {
  const mockUpdateLanguageSettings = jest.fn();
  const mockGetLanguageSettings = jest.fn();

  const defaultSettings = {
    language: 'ko',
    dateFormat: 'YYYY-MM-DD',
    timeFormat: '24',
    timezone: 'Asia/Seoul',
    numberFormat: 'ko-KR'
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockGetLanguageSettings.mockResolvedValue(defaultSettings);
  });

  const renderLanguageSettings = () => {
    return render(
      <BrowserRouter>
        <LanguageSettings />
      </BrowserRouter>
    );
  };

  it('언어 설정 컴포넌트가 올바르게 렌더링되어야 합니다', () => {
    renderLanguageSettings();

    expect(screen.getByText(/언어 및 지역 설정/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/언어/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/날짜 형식/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/시간 형식/i)).toBeInTheDocument();
  });

  it('언어를 변경할 수 있어야 합니다', async () => {
    renderLanguageSettings();

    const languageSelect = screen.getByLabelText(/언어/i);
    await userEvent.selectOptions(languageSelect, 'en');

    await waitFor(() => {
      expect(languageSelect).toHaveValue('en');
    });
  });

  it('날짜 형식을 변경할 수 있어야 합니다', async () => {
    renderLanguageSettings();

    const dateFormatSelect = screen.getByLabelText(/날짜 형식/i);
    await userEvent.selectOptions(dateFormatSelect, 'DD/MM/YYYY');

    await waitFor(() => {
      expect(dateFormatSelect).toHaveValue('DD/MM/YYYY');
    });
  });

  it('설정을 저장할 수 있어야 합니다', async () => {
    renderLanguageSettings();

    const saveButton = screen.getByRole('button', { name: /저장/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(mockUpdateLanguageSettings).toHaveBeenCalled();
      expect(screen.getByText(/언어 설정이 저장되었습니다/i)).toBeInTheDocument();
    });
  });
}); 