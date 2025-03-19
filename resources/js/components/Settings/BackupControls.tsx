import React from 'react';
import {
  Box,
  Paper,
  Typography,
  Button,
  Alert,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  IconButton,
  Chip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  LinearProgress
} from '@mui/material';
import {
  CloudDownload as DownloadIcon,
  Delete as DeleteIcon,
  Restore as RestoreIcon,
  Backup as BackupIcon
} from '@mui/icons-material';
import { settingsService } from '@/services/settingsService';

interface Backup {
  id: string;
  filename: string;
  size: number;
  created_at: string;
  type: 'manual' | 'automatic';
  status: 'completed' | 'failed';
}

const BackupControls: React.FC = () => {
  const [backups, setBackups] = React.useState<Backup[]>([]);
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  const [restoreDialogOpen, setRestoreDialogOpen] = React.useState(false);
  const [selectedBackup, setSelectedBackup] = React.useState<Backup | null>(null);
  const [backupInProgress, setBackupInProgress] = React.useState(false);

  const loadBackups = async () => {
    try {
      setLoading(true);
      const response = await settingsService.getBackups();
      setBackups(response.data);
      setError(null);
    } catch (err: any) {
      setError('백업 목록을 불러오는데 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  React.useEffect(() => {
    loadBackups();
  }, []);

  const handleCreateBackup = async () => {
    try {
      setBackupInProgress(true);
      await settingsService.createBackup();
      setSuccess('백업이 성공적으로 생성되었습니다.');
      await loadBackups();
    } catch (err: any) {
      setError('백업 생성에 실패했습니다.');
    } finally {
      setBackupInProgress(false);
    }
  };

  const handleDownloadBackup = async (backup: Backup) => {
    try {
      const response = await settingsService.downloadBackup(backup.id);
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', backup.filename);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err: any) {
      setError('백업 다운로드에 실패했습니다.');
    }
  };

  const handleDeleteBackup = async (backup: Backup) => {
    if (!window.confirm('정말로 이 백업을 삭제하시겠습니까?')) return;

    try {
      await settingsService.deleteBackup(backup.id);
      setSuccess('백업이 성공적으로 삭제되었습니다.');
      await loadBackups();
    } catch (err: any) {
      setError('백업 삭제에 실패했습니다.');
    }
  };

  const handleRestoreBackup = async () => {
    if (!selectedBackup) return;

    try {
      await settingsService.restoreBackup(selectedBackup.id);
      setSuccess('백업이 성공적으로 복원되었습니다.');
      setRestoreDialogOpen(false);
    } catch (err: any) {
      setError('백업 복원에 실패했습니다.');
    }
  };

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  return (
    <Box>
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      {success && (
        <Alert severity="success" sx={{ mb: 2 }}>
          {success}
        </Alert>
      )}

      <Paper sx={{ p: 3, mb: 3 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 3 }}>
          <Typography variant="h6">
            백업 관리
          </Typography>
          <Button
            variant="contained"
            startIcon={<BackupIcon />}
            onClick={handleCreateBackup}
            disabled={backupInProgress}
          >
            새 백업 생성
          </Button>
        </Box>

        {backupInProgress && (
          <Box sx={{ width: '100%', mb: 2 }}>
            <LinearProgress />
          </Box>
        )}

        <TableContainer>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>파일명</TableCell>
                <TableCell>크기</TableCell>
                <TableCell>생성일</TableCell>
                <TableCell>유형</TableCell>
                <TableCell>상태</TableCell>
                <TableCell align="right">작업</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={6} align="center">
                    <LinearProgress />
                  </TableCell>
                </TableRow>
              ) : backups.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} align="center">
                    백업이 없습니다
                  </TableCell>
                </TableRow>
              ) : (
                backups.map((backup) => (
                  <TableRow key={backup.id}>
                    <TableCell>{backup.filename}</TableCell>
                    <TableCell>{formatFileSize(backup.size)}</TableCell>
                    <TableCell>
                      {new Date(backup.created_at).toLocaleString()}
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={backup.type === 'manual' ? '수동' : '자동'}
                        color={backup.type === 'manual' ? 'primary' : 'default'}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={backup.status === 'completed' ? '완료' : '실패'}
                        color={backup.status === 'completed' ? 'success' : 'error'}
                        size="small"
                      />
                    </TableCell>
                    <TableCell align="right">
                      <IconButton
                        onClick={() => handleDownloadBackup(backup)}
                        disabled={backup.status !== 'completed'}
                      >
                        <DownloadIcon />
                      </IconButton>
                      <IconButton
                        onClick={() => {
                          setSelectedBackup(backup);
                          setRestoreDialogOpen(true);
                        }}
                        disabled={backup.status !== 'completed'}
                      >
                        <RestoreIcon />
                      </IconButton>
                      <IconButton
                        onClick={() => handleDeleteBackup(backup)}
                        color="error"
                      >
                        <DeleteIcon />
                      </IconButton>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>

      <Dialog
        open={restoreDialogOpen}
        onClose={() => setRestoreDialogOpen(false)}
      >
        <DialogTitle>백업 복원</DialogTitle>
        <DialogContent>
          <Typography>
            정말로 이 백업을 복원하시겠습니까? 현재 데이터는 모두 백업 데이터로 대체됩니다.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setRestoreDialogOpen(false)}>
            취소
          </Button>
          <Button
            onClick={handleRestoreBackup}
            color="primary"
            variant="contained"
          >
            복원
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default BackupControls; 