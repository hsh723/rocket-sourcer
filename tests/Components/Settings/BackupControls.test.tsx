import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import BackupControls from '@/components/Settings/BackupControls';

describe('BackupControls', () => {
  const mockCreateBackup = jest.fn();
  const mockRestoreBackup = jest.fn();
  const mockDeleteBackup = jest.fn();
  const mockDownloadBackup = jest.fn();
  const mockGetBackups = jest.fn();

  const mockBackups = [
    {
      id: '1',
      filename: 'backup-2024-01-01.zip',
      size: 1024,
      created_at: '2024-01-01T00:00:00Z',
      type: 'manual',
      status: 'completed'
    },
    {
      id: '2',
      filename: 'backup-2024-01-02.zip',
      size: 2048,
      created_at: '2024-01-02T00:00:00Z',
      type: 'automatic',
      status: 'completed'
    }
  ];

  beforeEach(() => {
    jest.clearAllMocks();
    mockGetBackups.mockResolvedValue(mockBackups);
  });

  const renderBackupControls = () => {
    return render(
      <BrowserRouter>
        <BackupControls />
      </BrowserRouter>
    );
  };

  it('백업 컨트롤이 올바르게 렌더링되어야 합니다', async () => {
    renderBackupControls();

    await waitFor(() => {
      expect(screen.getByText(/백업 관리/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /새 백업 생성/i })).toBeInTheDocument();
      expect(screen.getByText(/backup-2024-01-01.zip/)).toBeInTheDocument();
      expect(screen.getByText(/backup-2024-01-02.zip/)).toBeInTheDocument();
    });
  });

  it('새 백업을 생성할 수 있어야 합니다', async () => {
    renderBackupControls();

    const createButton = screen.getByRole('button', { name: /새 백업 생성/i });
    await userEvent.click(createButton);

    await waitFor(() => {
      expect(mockCreateBackup).toHaveBeenCalled();
      expect(screen.getByText(/백업이 생성되었습니다/i)).toBeInTheDocument();
    });
  });

  it('백업을 복원할 수 있어야 합니다', async () => {
    renderBackupControls();

    await waitFor(() => {
      const restoreButtons = screen.getAllByRole('button', { name: /복원/i });
      return userEvent.click(restoreButtons[0]);
    });

    // 확인 대화상자
    const confirmButton = screen.getByRole('button', { name: /확인/i });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(mockRestoreBackup).toHaveBeenCalledWith('1');
      expect(screen.getByText(/백업이 복원되었습니다/i)).toBeInTheDocument();
    });
  });

  it('백업을 삭제할 수 있어야 합니다', async () => {
    renderBackupControls();

    await waitFor(() => {
      const deleteButtons = screen.getAllByRole('button', { name: /삭제/i });
      return userEvent.click(deleteButtons[0]);
    });

    // 확인 대화상자
    const confirmButton = screen.getByRole('button', { name: /확인/i });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(mockDeleteBackup).toHaveBeenCalledWith('1');
      expect(screen.getByText(/백업이 삭제되었습니다/i)).toBeInTheDocument();
    });
  });

  it('백업을 다운로드할 수 있어야 합니다', async () => {
    renderBackupControls();

    await waitFor(() => {
      const downloadButtons = screen.getAllByRole('button', { name: /다운로드/i });
      return userEvent.click(downloadButtons[0]);
    });

    await waitFor(() => {
      expect(mockDownloadBackup).toHaveBeenCalledWith('1');
    });
  });

  it('백업 목록을 필터링할 수 있어야 합니다', async () => {
    renderBackupControls();

    const filterSelect = screen.getByLabelText(/백업 유형/i);
    await userEvent.selectOptions(filterSelect, 'manual');

    await waitFor(() => {
      expect(screen.getByText(/backup-2024-01-01.zip/)).toBeInTheDocument();
      expect(screen.queryByText(/backup-2024-01-02.zip/)).not.toBeInTheDocument();
    });
  });

  it('백업 목록을 정렬할 수 있어야 합니다', async () => {
    renderBackupControls();

    const sortSelect = screen.getByLabelText(/정렬 기준/i);
    await userEvent.selectOptions(sortSelect, 'size');

    await waitFor(() => {
      const backups = screen.getAllByRole('row');
      expect(backups[1]).toHaveTextContent('2048');
      expect(backups[2]).toHaveTextContent('1024');
    });
  });

  it('백업 생성 중 로딩 상태를 표시해야 합니다', async () => {
    mockCreateBackup.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 1000)));
    renderBackupControls();

    const createButton = screen.getByRole('button', { name: /새 백업 생성/i });
    await userEvent.click(createButton);

    await waitFor(() => {
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });
  });

  it('백업 복원 실패 시 오류를 표시해야 합니다', async () => {
    mockRestoreBackup.mockRejectedValue(new Error('복원 실패'));
    renderBackupControls();

    await waitFor(() => {
      const restoreButtons = screen.getAllByRole('button', { name: /복원/i });
      return userEvent.click(restoreButtons[0]);
    });

    const confirmButton = screen.getByRole('button', { name: /확인/i });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(screen.getByText(/백업 복원에 실패했습니다/i)).toBeInTheDocument();
    });
  });
}); 