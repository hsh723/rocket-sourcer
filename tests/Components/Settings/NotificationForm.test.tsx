import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import NotificationForm from '@/components/Settings/NotificationForm';

describe('NotificationForm', () => {
  const mockUpdateNotificationSettings = jest.fn();
  const mockGetNotificationSettings = jest.fn();
  const mockTestNotification = jest.fn();

  const defaultSettings = {
    emailNotifications: true,
    pushNotifications: false,
    slackNotifications: false,
    slackWebhookUrl: '',
    notificationFrequency: 'realtime',
    priceAlertThreshold: 10,
    stockAlertThreshold: 5,
    competitorAlertThreshold: 15
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockGetNotificationSettings.mockResolvedValue(defaultSettings);
  });

  const renderNotificationForm = () => {
    return render(
      <BrowserRouter>
        <NotificationForm />
      </BrowserRouter>
    );
  };

  it('알림 설정 폼이 올바르게 렌더링되어야 합니다', () => {
    renderNotificationForm();

    expect(screen.getByLabelText(/이메일 알림/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/푸시 알림/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Slack 알림/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/알림 빈도/i)).toBeInTheDocument();
  });

  it('이메일 알림을 토글할 수 있어야 합니다', async () => {
    renderNotificationForm();

    const emailSwitch = screen.getByRole('checkbox', { name: /이메일 알림/i });
    await userEvent.click(emailSwitch);

    await waitFor(() => {
      expect(emailSwitch).not.toBeChecked();
    });
  });

  it('Slack 알림을 활성화하면 웹훅 URL 입력 필드가 표시되어야 합니다', async () => {
    renderNotificationForm();

    const slackSwitch = screen.getByRole('checkbox', { name: /Slack 알림/i });
    await userEvent.click(slackSwitch);

    await waitFor(() => {
      expect(screen.getByLabelText(/Slack Webhook URL/i)).toBeInTheDocument();
    });
  });

  it('알림 빈도를 변경할 수 있어야 합니다', async () => {
    renderNotificationForm();

    const frequencySelect = screen.getByLabelText(/알림 빈도/i);
    await userEvent.selectOptions(frequencySelect, 'daily');

    await waitFor(() => {
      expect(frequencySelect).toHaveValue('daily');
    });
  });

  it('가격 알림 임계값을 설정할 수 있어야 합니다', async () => {
    renderNotificationForm();

    const priceThresholdInput = screen.getByLabelText(/가격 변동 알림 임계값/i);
    await userEvent.clear(priceThresholdInput);
    await userEvent.type(priceThresholdInput, '15');

    await waitFor(() => {
      expect(priceThresholdInput).toHaveValue('15');
    });
  });

  it('재고 알림 임계값을 설정할 수 있어야 합니다', async () => {
    renderNotificationForm();

    const stockThresholdInput = screen.getByLabelText(/재고 알림 임계값/i);
    await userEvent.clear(stockThresholdInput);
    await userEvent.type(stockThresholdInput, '10');

    await waitFor(() => {
      expect(stockThresholdInput).toHaveValue('10');
    });
  });

  it('테스트 알림을 전송할 수 있어야 합니다', async () => {
    renderNotificationForm();

    const testButton = screen.getByRole('button', { name: /테스트 알림 전송/i });
    await userEvent.click(testButton);

    await waitFor(() => {
      expect(mockTestNotification).toHaveBeenCalled();
      expect(screen.getByText(/테스트 알림이 전송되었습니다/i)).toBeInTheDocument();
    });
  });

  it('잘못된 Slack Webhook URL에 대해 오류를 표시해야 합니다', async () => {
    renderNotificationForm();

    const slackSwitch = screen.getByRole('checkbox', { name: /Slack 알림/i });
    await userEvent.click(slackSwitch);

    const webhookInput = screen.getByLabelText(/Slack Webhook URL/i);
    await userEvent.type(webhookInput, 'invalid-url');

    await waitFor(() => {
      expect(screen.getByText(/올바른 URL 형식이 아닙니다/i)).toBeInTheDocument();
    });
  });

  it('설정을 저장할 수 있어야 합니다', async () => {
    renderNotificationForm();

    const saveButton = screen.getByRole('button', { name: /저장/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(mockUpdateNotificationSettings).toHaveBeenCalled();
      expect(screen.getByText(/알림 설정이 저장되었습니다/i)).toBeInTheDocument();
    });
  });

  it('알림 설정을 초기화할 수 있어야 합니다', async () => {
    renderNotificationForm();

    const resetButton = screen.getByRole('button', { name: /초기화/i });
    await userEvent.click(resetButton);

    await waitFor(() => {
      expect(screen.getByLabelText(/이메일 알림/i)).toBeChecked();
      expect(screen.getByLabelText(/푸시 알림/i)).not.toBeChecked();
      expect(screen.getByLabelText(/Slack 알림/i)).not.toBeChecked();
      expect(screen.getByLabelText(/알림 빈도/i)).toHaveValue('realtime');
    });
  });
}); 