import { ButtonHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

type Variant = 'default' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'success';
type Size = 'sm' | 'md' | 'lg';

const variants: Record<Variant, string> = {
    default: 'bg-indigo-600 text-white hover:bg-indigo-700 focus-visible:ring-indigo-500',
    secondary: 'bg-slate-900 text-white hover:bg-slate-800 focus-visible:ring-slate-500',
    outline: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-400',
    ghost: 'text-slate-600 hover:bg-slate-100 focus-visible:ring-slate-400',
    danger: 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-500',
    success: 'bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:ring-emerald-500',
};

const sizes: Record<Size, string> = {
    sm: 'px-2.5 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-2.5 text-base',
};

interface Props extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
}

const Button = forwardRef<HTMLButtonElement, Props>(({ className, variant = 'default', size = 'md', ...props }, ref) => (
    <button
        ref={ref}
        className={cn(
            'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            variants[variant],
            sizes[size],
            className,
        )}
        {...props}
    />
));

Button.displayName = 'Button';

export { Button };
