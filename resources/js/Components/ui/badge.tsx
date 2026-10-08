import { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type Tone = 'default' | 'success' | 'warning' | 'danger' | 'info' | 'muted';

const tones: Record<Tone, string> = {
    default: 'bg-slate-100 text-slate-700',
    success: 'bg-emerald-100 text-emerald-800',
    warning: 'bg-amber-100 text-amber-800',
    danger: 'bg-rose-100 text-rose-800',
    info: 'bg-blue-100 text-blue-800',
    muted: 'bg-slate-50 text-slate-500',
};

export function Badge({ className, tone = 'default', ...props }: HTMLAttributes<HTMLSpanElement> & { tone?: Tone }) {
    return (
        <span
            className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', tones[tone], className)}
            {...props}
        />
    );
}

export function Alert({ className, tone = 'info', children, ...props }: HTMLAttributes<HTMLDivElement> & { tone?: Tone }) {
    const tonesMap: Record<string, string> = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        warning: 'border-amber-200 bg-amber-50 text-amber-800',
        danger: 'border-rose-200 bg-rose-50 text-rose-800',
        info: 'border-blue-200 bg-blue-50 text-blue-800',
        default: 'border-slate-200 bg-slate-50 text-slate-700',
        muted: 'border-slate-200 bg-slate-50 text-slate-600',
    };

    return (
        <div className={cn('rounded-lg border px-4 py-3 text-sm', tonesMap[tone] ?? tonesMap.info, className)} {...props}>
            {children}
        </div>
    );
}
