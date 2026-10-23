import type { ReactNode } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from './dialog';
import { cn } from '@/lib/utils';

interface Props {
  open: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
  className?: string;
}

export const Modal = ({ open, onClose, title, children, className }: Props) => (
  <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
    <DialogContent className={cn('max-h-[90vh] overflow-y-auto p-0', className)}>
      <DialogHeader className="border-b px-6 py-4">
        <DialogTitle>{title}</DialogTitle>
      </DialogHeader>
      <div className="px-6 py-5">{children}</div>
    </DialogContent>
  </Dialog>
);
