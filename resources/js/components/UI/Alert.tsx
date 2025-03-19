import { Alert as MuiAlert, AlertProps as MuiAlertProps, Snackbar } from '@mui/material';

interface AlertProps extends MuiAlertProps {
  open: boolean;
  onClose: () => void;
  autoHideDuration?: number;
}

export function Alert({ open, onClose, autoHideDuration = 6000, ...props }: AlertProps) {
  return (
    <Snackbar
      open={open}
      autoHideDuration={autoHideDuration}
      onClose={onClose}
      anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
    >
      <MuiAlert
        elevation={6}
        variant="filled"
        onClose={onClose}
        {...props}
      />
    </Snackbar>
  );
} 