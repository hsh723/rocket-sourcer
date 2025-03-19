import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import LoginForm from '@/components/Auth/LoginForm';
import { AuthContext } from '@/context/AuthContext';

describe('LoginForm', () => {
  const mockLogin = jest.fn();
  const mockNavigate = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  const renderLoginForm = () => {
    return render(
      <BrowserRouter>
        <AuthContext.Provider value={{ login: mockLogin, user: null }}>
          <LoginForm />
        </AuthContext.Provider>
      </BrowserRouter>
    );
  };

  it('모든 필수 필드가 렌더링되어야 합니다', () => {
    renderLoginForm();

    expect(screen.getByLabelText(/이메일/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/비밀번호/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /로그인/i })).toBeInTheDocument();
  });

  it('유효하지 않은 이메일 형식에 대해 오류를 표시해야 합니다', async () => {
    renderLoginForm();

    const emailInput = screen.getByLabelText(/이메일/i);
    await userEvent.type(emailInput, 'invalid-email');
    fireEvent.blur(emailInput);

    expect(await screen.findByText(/올바른 이메일 형식이 아닙니다/i)).toBeInTheDocument();
  });

  it('비밀번호가 비어있을 때 오류를 표시해야 합니다', async () => {
    renderLoginForm();

    const passwordInput = screen.getByLabelText(/비밀번호/i);
    await userEvent.type(passwordInput, ' ');
    fireEvent.blur(passwordInput);
    await userEvent.clear(passwordInput);

    expect(await screen.findByText(/비밀번호를 입력해주세요/i)).toBeInTheDocument();
  });

  it('유효한 자격 증명으로 로그인을 시도해야 합니다', async () => {
    renderLoginForm();

    const emailInput = screen.getByLabelText(/이메일/i);
    const passwordInput = screen.getByLabelText(/비밀번호/i);
    const submitButton = screen.getByRole('button', { name: /로그인/i });

    await userEvent.type(emailInput, 'test@example.com');
    await userEvent.type(passwordInput, 'password123');
    await userEvent.click(submitButton);

    await waitFor(() => {
      expect(mockLogin).toHaveBeenCalledWith({
        email: 'test@example.com',
        password: 'password123'
      });
    });
  });

  it('로그인 실패 시 오류 메시지를 표시해야 합니다', async () => {
    mockLogin.mockRejectedValueOnce(new Error('잘못된 자격 증명'));
    renderLoginForm();

    const emailInput = screen.getByLabelText(/이메일/i);
    const passwordInput = screen.getByLabelText(/비밀번호/i);
    const submitButton = screen.getByRole('button', { name: /로그인/i });

    await userEvent.type(emailInput, 'wrong@example.com');
    await userEvent.type(passwordInput, 'wrongpass');
    await userEvent.click(submitButton);

    expect(await screen.findByText(/잘못된 자격 증명/i)).toBeInTheDocument();
  });
}); 