import { ReactNode, useEffect } from 'react';
import { cn } from '@/lib/utils';

interface Props {
    open: boolean;
    onClose: () => void;
    title?: string;
    description?: string;
    children: ReactNode;
    footer?: ReactNode;
    maxWidth?: string;
}

export function Modal({ open, onClose, title, description, children, footer, maxWidth = 'max-w-lg' }: Props) {
    useEffect(() => {
        if (!open) return;

        const handler = (event: KeyboardEvent) => {
            if (event.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', handler);

        return () => document.removeEventListener('keydown', handler);
    }, [open, onClose]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="fixed inset-0 bg-slate-900/50" onClick={onClose} />
            <div className={cn('relative z-50 w-full rounded-xl bg-white shadow-xl', maxWidth)}>
                <div className="border-b border-slate-200 px-5 py-4">
                    {title && <h2 className="text-lg font-semibold text-slate-900">{title}</h2>}
                    {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
                </div>
                <div className="max-h-[70vh] overflow-y-auto px-5 py-4">{children}</div>
                {footer && <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">{footer}</div>}
            </div>
        </div>
    );
}
