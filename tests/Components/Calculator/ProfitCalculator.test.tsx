import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import ProfitCalculator from '@/components/Calculator/ProfitCalculator';
import { CalculatorContext } from '@/context/CalculatorContext';

describe('ProfitCalculator', () => {
  const mockCalculateProfit = jest.fn();
  const mockSaveCalculation = jest.fn();

  const mockResult = {
    totalRevenue: 1500000,
    totalCost: 1000000,
    grossProfit: 500000,
    netProfit: 400000,
    roi: 0.4,
    marginRate: 0.267,
    breakEvenPoint: 80
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockCalculateProfit.mockResolvedValue(mockResult);
  });

  const renderCalculator = () => {
    return render(
      <BrowserRouter>
        <CalculatorContext.Provider value={{
          calculateProfit: mockCalculateProfit,
          saveCalculation: mockSaveCalculation
        }}>
          <ProfitCalculator />
        </CalculatorContext.Provider>
      </BrowserRouter>
    );
  };

  it('모든 입력 필드가 렌더링되어야 합니다', () => {
    renderCalculator();

    expect(screen.getByLabelText(/구매 가격/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/판매 가격/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/배송비/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/마켓플레이스 수수료/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/마케팅 비용/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/예상 판매량/i)).toBeInTheDocument();
  });

  it('유효하지 않은 입력값에 대해 오류를 표시해야 합니다', async () => {
    renderCalculator();

    const purchasePriceInput = screen.getByLabelText(/구매 가격/i);
    await userEvent.type(purchasePriceInput, '-1000');
    fireEvent.blur(purchasePriceInput);

    expect(await screen.findByText(/0보다 큰 값을 입력해주세요/i)).toBeInTheDocument();
  });

  it('계산 결과가 올바르게 표시되어야 합니다', async () => {
    renderCalculator();

    // 입력값 설정
    await userEvent.type(screen.getByLabelText(/구매 가격/i), '10000');
    await userEvent.type(screen.getByLabelText(/판매 가격/i), '15000');
    await userEvent.type(screen.getByLabelText(/배송비/i), '2000');
    await userEvent.type(screen.getByLabelText(/마켓플레이스 수수료/i), '10');
    await userEvent.type(screen.getByLabelText(/마케팅 비용/i), '1000');
    await userEvent.type(screen.getByLabelText(/예상 판매량/i), '100');

    const calculateButton = screen.getByRole('button', { name: /계산하기/i });
    await userEvent.click(calculateButton);

    await waitFor(() => {
      expect(screen.getByText(/총 매출액/i)).toBeInTheDocument();
      expect(screen.getByText(/1,500,000원/)).toBeInTheDocument();
      expect(screen.getByText(/순이익/i)).toBeInTheDocument();
      expect(screen.getByText(/400,000원/)).toBeInTheDocument();
      expect(screen.getByText(/ROI/i)).toBeInTheDocument();
      expect(screen.getByText(/40%/)).toBeInTheDocument();
    });
  });

  it('계산 결과를 저장할 수 있어야 합니다', async () => {
    renderCalculator();

    // 계산 수행
    await userEvent.type(screen.getByLabelText(/구매 가격/i), '10000');
    await userEvent.type(screen.getByLabelText(/판매 가격/i), '15000');
    await userEvent.click(screen.getByRole('button', { name: /계산하기/i }));

    // 저장 버튼 클릭
    const saveButton = screen.getByRole('button', { name: /저장/i });
    await userEvent.click(saveButton);

    await waitFor(() => {
      expect(mockSaveCalculation).toHaveBeenCalled();
    });
  });

  it('손익분기점 분석이 표시되어야 합니다', async () => {
    renderCalculator();

    // 계산 수행
    await userEvent.type(screen.getByLabelText(/구매 가격/i), '10000');
    await userEvent.type(screen.getByLabelText(/판매 가격/i), '15000');
    await userEvent.click(screen.getByRole('button', { name: /계산하기/i }));

    await waitFor(() => {
      expect(screen.getByText(/손익분기점/i)).toBeInTheDocument();
      expect(screen.getByText(/80개/)).toBeInTheDocument();
    });
  });

  it('민감도 분석이 표시되어야 합니다', async () => {
    renderCalculator();

    // 계산 수행 후 민감도 분석 탭 클릭
    await userEvent.type(screen.getByLabelText(/구매 가격/i), '10000');
    await userEvent.type(screen.getByLabelText(/판매 가격/i), '15000');
    await userEvent.click(screen.getByRole('button', { name: /계산하기/i }));
    await userEvent.click(screen.getByRole('tab', { name: /민감도 분석/i }));

    await waitFor(() => {
      expect(screen.getByText(/가격 변동에 따른 수익성/i)).toBeInTheDocument();
    });
  });
}); 