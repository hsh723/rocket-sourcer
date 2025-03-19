import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import ImportExportControls from '@/components/Settings/ImportExportControls';

describe('ImportExportControls', () => {
  const mockExportData = jest.fn();
  const mockImportData = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    // URL.createObjectURL 모킹
    global.URL.createObjectURL = jest.fn();
    global.URL.revokeObjectURL = jest.fn();
  });

  const renderImportExportControls = () => {
    return render(
      <BrowserRouter>
        <ImportExportControls />
      </BrowserRouter>
    );
  };

  it('가져오기/내보내기 컨트롤이 올바르게 렌더링되어야 합니다', () => {
    renderImportExportControls();

    expect(screen.getByText(/데이터 내보내기\/가져오기/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/내보내기 형식/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /내보내기/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /가져오기/i })).toBeInTheDocument();
  });

  it('내보내기 형식을 선택할 수 있어야 합니다', async () => {
    renderImportExportControls();

    const formatSelect = screen.getByLabelText(/내보내기 형식/i);
    await userEvent.selectOptions(formatSelect, 'csv');

    expect(formatSelect).toHaveValue('csv');
  });

  it('데이터 옵션을 선택할 수 있어야 합니다', async () => {
    renderImportExportControls();

    const productsCheckbox = screen.getByLabelText(/제품 데이터/i);
    const calculationsCheckbox = screen.getByLabelText(/계산 결과/i);

    await userEvent.click(productsCheckbox);
    await userEvent.click(calculationsCheckbox);

    expect(productsCheckbox).toBeChecked();
    expect(calculationsCheckbox).toBeChecked();
  });

  it('데이터를 내보낼 수 있어야 합니다', async () => {
    mockExportData.mockResolvedValue(new Blob(['test data']));
    renderImportExportControls();

    // 옵션 선택
    await userEvent.click(screen.getByLabelText(/제품 데이터/i));
    await userEvent.selectOptions(screen.getByLabelText(/내보내기 형식/i), 'excel');

    // 내보내기 버튼 클릭
    const exportButton = screen.getByRole('button', { name: /내보내기/i });
    await userEvent.click(exportButton);

    await waitFor(() => {
      expect(mockExportData).toHaveBeenCalledWith({
        format: 'excel',
        options: expect.objectContaining({
          products: true
        })
      });
    });
  });

  it('데이터를 가져올 수 있어야 합니다', async () => {
    renderImportExportControls();

    // 파일 선택
    const file = new File(['test data'], 'test.xlsx', { type: 'application/vnd.ms-excel' });
    const input = screen.getByTestId('file-input');
    await userEvent.upload(input, file);

    // 확인 대화상자
    const confirmButton = screen.getByRole('button', { name: /가져오기/i });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(mockImportData).toHaveBeenCalled();
      expect(screen.getByText(/데이터가 성공적으로 가져와졌습니다/i)).toBeInTheDocument();
    });
  });

  it('옵션을 선택하지 않으면 내보내기 버튼이 비활성화되어야 합니다', () => {
    renderImportExportControls();

    const exportButton = screen.getByRole('button', { name: /내보내기/i });
    expect(exportButton).toBeDisabled();
  });

  it('잘못된 파일 형식에 대해 오류를 표시해야 합니다', async () => {
    renderImportExportControls();

    const file = new File(['test data'], 'test.txt', { type: 'text/plain' });
    const input = screen.getByTestId('file-input');
    await userEvent.upload(input, file);

    expect(screen.getByText(/지원되지 않는 파일 형식입니다/i)).toBeInTheDocument();
  });

  it('가져오기 중 로딩 상태를 표시해야 합니다', async () => {
    mockImportData.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 1000)));
    renderImportExportControls();

    const file = new File(['test data'], 'test.xlsx', { type: 'application/vnd.ms-excel' });
    const input = screen.getByTestId('file-input');
    await userEvent.upload(input, file);

    const importButton = screen.getByRole('button', { name: /가져오기/i });
    await userEvent.click(importButton);

    await waitFor(() => {
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });
  });

  it('가져오기 실패 시 오류를 표시해야 합니다', async () => {
    mockImportData.mockRejectedValue(new Error('가져오기 실패'));
    renderImportExportControls();

    const file = new File(['test data'], 'test.xlsx', { type: 'application/vnd.ms-excel' });
    const input = screen.getByTestId('file-input');
    await userEvent.upload(input, file);

    const importButton = screen.getByRole('button', { name: /가져오기/i });
    await userEvent.click(importButton);

    await waitFor(() => {
      expect(screen.getByText(/데이터 가져오기에 실패했습니다/i)).toBeInTheDocument();
    });
  });

  it('큰 파일에 대한 경고를 표시해야 합니다', async () => {
    renderImportExportControls();

    const largeFile = new File(['test data'.repeat(1000000)], 'large.xlsx', {
      type: 'application/vnd.ms-excel'
    });
    const input = screen.getByTestId('file-input');
    await userEvent.upload(input, largeFile);

    expect(screen.getByText(/파일 크기가 너무 큽니다/i)).toBeInTheDocument();
  });
}); 