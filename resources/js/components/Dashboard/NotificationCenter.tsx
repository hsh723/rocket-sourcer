import { Card } from '@/components/UI/Card';
import {
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  Typography,
  IconButton,
} from '@mui/material';
import NotificationsIcon from '@mui/icons-material/Notifications';
import CloseIcon from '@mui/icons-material/Close';
import { formatDistanceToNow } from 'date-fns';
import { ko } from 'date-fns/locale';

interface Notification {
  id: number;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'success' | 'error';
  createdAt: string;
}

interface NotificationCenterProps {
  notifications: Notification[];
  onDismiss?: (id: number) => void;
}

export function NotificationCenter({ notifications, onDismiss }: NotificationCenterProps) {
  return (
    <Card
      title="알림"
      subheader={`${notifications.length}개의 새로운 알림`}
    >
      <List>
        {notifications.map((notification) => (
          <ListItem
            key={notification.id}
            secondaryAction={
              onDismiss && (
                <IconButton
                  edge="end"
                  aria-label="dismiss"
                  onClick={() => onDismiss(notification.id)}
                >
                  <CloseIcon />
                </IconButton>
              )
            }
          >
            <ListItemIcon>
              <NotificationsIcon color={notification.type} />
            </ListItemIcon>
            <ListItemText
              primary={notification.title}
              secondary={
                <>
                  <Typography component="span" variant="body2">
                    {notification.message}
                  </Typography>
                  <Typography
                    component="span"
                    variant="caption"
                    color="text.secondary"
                    sx={{ display: 'block' }}
                  >
                    {formatDistanceToNow(new Date(notification.createdAt), {
                      addSuffix: true,
                      locale: ko,
                    })}
                  </Typography>
                </>
              }
            />
          </ListItem>
        ))}
      </List>
    </Card>
  );
} 