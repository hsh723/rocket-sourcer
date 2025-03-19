import { ReactNode } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  IconButton,
  DialogProps,
} from '@mui/material';
import CloseIcon from '@mui/icons-material/Close';

interface ModalProps extends Omit<DialogProps, 'title'> {
  title?: ReactNode;
  actions?: ReactNode;
  onClose: () => void;
  children: ReactNode;
}

export function Modal({ title, actions, onClose, children, ...props }: ModalProps) {
  return (
    <Dialog onClose={onClose} {...props}>
      {title && (
        <DialogTitle>
          {title}
          <IconButton
            aria-label="close"
            onClick={onClose}
            sx={{
              position: 'absolute',
              right: 8,
              top: 8,
            }}
          >
            <CloseIcon />
          </IconButton>
        </DialogTitle>
      )}
      <DialogContent>{children}</DialogContent>
      {actions && <DialogActions>{actions}</DialogActions>}
    </Dialog>
  );
} 