import { Button as MuiButton, ButtonProps as MuiButtonProps, CircularProgress } from '@mui/material';

interface ButtonProps extends MuiButtonProps {
  loading?: boolean;
}

export function Button({ loading, disabled, children, ...props }: ButtonProps) {
  return (
    <MuiButton
      disabled={loading || disabled}
      {...props}
    >
      {loading ? <CircularProgress size={24} color="inherit" /> : children}
    </MuiButton>
  );
} 