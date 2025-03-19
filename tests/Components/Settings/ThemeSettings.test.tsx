import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import ThemeSettings from '@/components/Settings/ThemeSettings';
import { ThemeContext } from '@/context/ThemeContext';

describe('ThemeSettings', () => {
  const mockUpdateTheme = jest.fn();
  const mockGetThemeSettings = jest.fn();
  const mockSaveThemeSettings = jest.fn();

  const defaultTheme = {
    mode: 'light',
    colorScheme: '기본',
    isDense: false,
    fontSize: 14,
    borderRadius: 4
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockGetThemeSettings.mockResolvedValue(defaultTheme);
  });

  const renderThemeSettings = () => {
    return render(
      <BrowserRouter>
        <ThemeContext.Provider value={{
          theme: defaultTheme,
          updateTheme: mockUpdateTheme
        }}>
          <ThemeSettings />
        </ThemeContext.Provider>
      </BrowserRouter>
    );
  };

  it('테마 설정 컴포넌트가 올바르게 렌더링되어야 합니다', () => {
    renderThemeSettings();

    expect(screen.getByText(/테마 설정/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/테마 모드/i)).toBeInTheDocument();
    expect(screen.getByText(/색상 테마/i)).toBeInTheDocument();
    expect(screen.getByText(/조밀한 레이아웃 사용/i)).toBeInTheDocument();
  });

  it('테마 모드를 변경할 수 있어야 합니다', async () => {
    renderThemeSettings();

    const darkModeRadio = screen.getByLabelText(/다크 모드/i);
    await userEvent.click(darkModeRadio);

    await waitFor(() => {
      expect(darkModeRadio).toBeChecked();
    });
  });

  it('색상 테마를 변경할 수 있어야 합니다', async () => {
    renderThemeSettings();

    const purpleTheme = screen.getByRole('button', { name: /퍼플/i });
    await userEvent.click(purpleTheme);

    await waitFor(() => {
      expect(mockUpdateTheme).toHaveBeenCalledWith(expect.objectContaining({
        colorScheme: '퍼플'
      }));
    });
  });

  it('글자 크기를 조절할 수 있어야 합니다', async () => {
    renderThemeSettings();

    const fontSizeSlider = screen.getByRole('slider', { name: /기본 글자 크기/i });
    await userEvent.type(fontSizeSlider, '16');

    await waitFor(() => {
      expect(fontSizeSlider).toHaveValue('16');
    });
  });

  it('설정을 저장할 수 있어야 합니다', async () => {
    renderThemeSettings();

    const saveButton = screen.getByRole('button', { name: /저장/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(mockSaveThemeSettings).toHaveBeenCalled();
      expect(screen.getByText(/테마 설정이 저장되었습니다/i)).toBeInTheDocument();
    });
  });

  it('설정을 초기화할 수 있어야 합니다', async () => {
    renderThemeSettings();

    const resetButton = screen.getByRole('button', { name: /초기화/i });
    await userEvent.click(resetButton);

    await waitFor(() => {
      expect(mockUpdateTheme).toHaveBeenCalledWith(defaultTheme);
    });
  });
}); 