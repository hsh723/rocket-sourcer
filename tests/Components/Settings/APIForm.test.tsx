import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import APIForm from '@/components/Settings/APIForm';

describe('APIForm', () => {
  const mockUpdateAPISettings = jest.fn();
  const mockGetAPISettings = jest.fn();
  const mockRegenerateKey = jest.fn();

  const defaultSettings = {
    coupangApiKey: 'test-coupang-key',
    naverApiKey: 'test-naver-key',
    enableApiLogging: true,
    requestTimeout: 30000
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockGetAPISettings.mockResolvedValue(defaultSettings);
  });

  const renderAPIForm = () => {
    return render(
      <BrowserRouter>
        <APIForm />
      </BrowserRouter>
    );
  };

  it('API 설정 폼이 올바르게 렌더링되어야 합니다', () => {
    renderAPIForm();

    expect(screen.getByLabelText(/Coupang API Key/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Naver API Key/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/API 로깅 활성화/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/요청 타임아웃/i)).toBeInTheDocument();
  });

  it('API 키를 마스킹/언마스킹할 수 있어야 합니다', async () => {
    renderAPIForm();

    const toggleVisibilityButton = screen.getAllByRole('button', { name: /표시/i })[0];
    await userEvent.click(toggleVisibilityButton);

    const apiKeyInput = screen.getByLabelText(/Coupang API Key/i);
    expect(apiKeyInput).toHaveAttribute('type', 'text');

    await userEvent.click(toggleVisibilityButton);
    expect(apiKeyInput).toHaveAttribute('type', 'password');
  });

  it('API 키를 클립보드에 복사할 수 있어야 합니다', async () => {
    const mockClipboard = {
      writeText: jest.fn()
    };
    Object.assign(navigator, {
      clipboard: mockClipboard
    });

    renderAPIForm();

    const copyButton = screen.getAllByRole('button', { name: /복사/i })[0];
    await userEvent.click(copyButton);

    await waitFor(() => {
      expect(mockClipboard.writeText).toHaveBeenCalledWith(defaultSettings.coupangApiKey);
      expect(screen.getByText(/클립보드에 복사되었습니다/i)).toBeInTheDocument();
    });
  });

  it('API 키를 재생성할 수 있어야 합니다', async () => {
    renderAPIForm();

    const regenerateButton = screen.getAllByRole('button', { name: /재생성/i })[0];
    await userEvent.click(regenerateButton);

    // 확인 대화상자
    const confirmButton = screen.getByRole('button', { name: /확인/i });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(mockRegenerateKey).toHaveBeenCalled();
      expect(screen.getByText(/API 키가 재생성되었습니다/i)).toBeInTheDocument();
    });
  });

  it('API 로깅을 토글할 수 있어야 합니다', async () => {
    renderAPIForm();

    const loggingSwitch = screen.getByRole('checkbox', { name: /API 로깅 활성화/i });
    await userEvent.click(loggingSwitch);

    await waitFor(() => {
      expect(loggingSwitch).not.toBeChecked();
    });
  });

  it('요청 타임아웃을 변경할 수 있어야 합니다', async () => {
    renderAPIForm();

    const timeoutInput = screen.getByLabelText(/요청 타임아웃/i);
    await userEvent.clear(timeoutInput);
    await userEvent.type(timeoutInput, '60000');

    await waitFor(() => {
      expect(timeoutInput).toHaveValue('60000');
    });
  });

  it('설정을 저장할 수 있어야 합니다', async () => {
    renderAPIForm();

    const saveButton = screen.getByRole('button', { name: /저장/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(mockUpdateAPISettings).toHaveBeenCalled();
      expect(screen.getByText(/API 설정이 저장되었습니다/i)).toBeInTheDocument();
    });
  });

  it('잘못된 타임아웃 값에 대해 오류를 표시해야 합니다', async () => {
    renderAPIForm();

    const timeoutInput = screen.getByLabelText(/요청 타임아웃/i);
    await userEvent.clear(timeoutInput);
    await userEvent.type(timeoutInput, '-1000');

    await waitFor(() => {
      expect(screen.getByText(/타임아웃은 0보다 커야 합니다/i)).toBeInTheDocument();
    });
  });
}); 