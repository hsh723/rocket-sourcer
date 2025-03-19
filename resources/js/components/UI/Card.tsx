import { ReactNode } from 'react';
import { Card as MuiCard, CardContent, CardHeader, CardProps as MuiCardProps } from '@mui/material';

interface CardProps extends Omit<MuiCardProps, 'title'> {
  title?: ReactNode;
  subheader?: ReactNode;
  children: ReactNode;
}

export function Card({ title, subheader, children, ...props }: CardProps) {
  return (
    <MuiCard {...props}>
      {(title || subheader) && (
        <CardHeader title={title} subheader={subheader} />
      )}
      <CardContent>{children}</CardContent>
    </MuiCard>
  );
} 