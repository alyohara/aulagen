import { router, usePage } from '@inertiajs/react';

type Locale = 'en' | 'es';

export default function LanguageSwitcher({ className = '' }: { className?: string }) {
    const locale = (usePage().props.locale ?? 'en') as Locale;
    const nextLocale: Locale = locale === 'en' ? 'es' : 'en';
    const label = locale === 'en' ? 'ES' : 'EN';

    return (
        <button
            type="button"
            onClick={() => router.put(route('locale.update'), { locale: nextLocale }, { preserveScroll: true })}
            className={`rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-semibold tracking-wide text-slate-600 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 ${className}`}
            aria-label={locale === 'en' ? 'Switch to Spanish' : 'Cambiar a inglés'}
        >
            {label}
        </button>
    );
}
