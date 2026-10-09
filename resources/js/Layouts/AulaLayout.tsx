import { Alert } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { AulaShared, ChatAnswer } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import { FormEvent, PropsWithChildren, ReactNode, useState } from 'react';
import axios from 'axios';
import { LocalizedContent, translate } from '@/lib/i18n';

interface Props extends AulaShared {
    active?: string;
    progress?: number[];
}

export default function AulaLayout({ course, navigation, settings, isPreview, active, progress = [], children }: PropsWithChildren<Props>) {
    const { auth, flash, locale } = usePage().props as unknown as { auth: { user: { id: number; name: string } | null }; flash: Record<string, string | null>; locale: 'en' | 'es' };
    const t = locale === 'es'
        ? { preview: 'Vista previa de docente: los alumnos ven solo las lecciones aprobadas/publicadas.', back: 'Volver al panel', menu: 'Menú', home: 'Inicio', activities: 'Actividades', bibliography: 'Bibliografía', glossary: 'Glosario', complementary: 'Complementario', search: 'Buscar', assistant: 'Asistente', signIn: 'Ingresar', contents: 'Contenidos', progress: 'Tu progreso', lessons: 'lecciones', generated: 'contenido generado con IA y aprobado por el equipo docente' }
        : { preview: 'Educator preview: students see only approved or published lessons.', back: 'Back to dashboard', menu: 'Menu', home: 'Home', activities: 'Activities', bibliography: 'Bibliography', glossary: 'Glossary', complementary: 'Additional resources', search: 'Search', assistant: 'Assistant', signIn: 'Sign in', contents: 'Contents', progress: 'Your progress', lessons: 'lessons', generated: 'AI-generated content reviewed by the teaching team' };
    const [menuOpen, setMenuOpen] = useState(false);
    const [assistantOpen, setAssistantOpen] = useState(false);

    const isActive = (key: string) => active === key;

    return (
        <LocalizedContent><div className="min-h-screen bg-slate-50">
            {isPreview && (
                <div className="bg-amber-400 px-4 py-2 text-center text-sm font-medium text-amber-950">
                    {t.preview}{' '}
                    <Link className="underline" href={route('courses.overview', course.id)}>{t.back}</Link>
                </div>
            )}

            {(flash.success || flash.warning || flash.error) && (
                <div className="mx-auto max-w-6xl px-4 pt-4">
                    {flash.success && <Alert tone="success">{flash.success}</Alert>}
                    {flash.warning && <Alert tone="warning">{flash.warning}</Alert>}
                    {flash.error && <Alert tone="danger">{flash.error}</Alert>}
                </div>
            )}

            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div className="flex items-center gap-3">
                        <button className="text-slate-500 md:hidden" onClick={() => setMenuOpen((v) => !v)} aria-label={t.menu}>
                            ☰
                        </button>
                        <Link
                            href="/"
                            className="hidden items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 sm:flex"
                            title={t.back}
                        >
                            ← AulaGen
                        </Link>
                        <Link href={route('aula.home', course.slug)} className="flex items-center gap-2">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
                                {course.name.charAt(0)}
                            </span>
                            <span>
                                <span className="block text-sm font-semibold leading-tight text-slate-900">{course.name}</span>
                                <span className="block text-xs text-slate-500">{course.institution}</span>
                            </span>
                        </Link>
                    </div>

                    <nav className="hidden items-center gap-1 text-sm md:flex">
                        {[
                            ['home', t.home, route('aula.home', course.slug)],
                            ['activities', t.activities, route('aula.activities', course.slug)],
                            ['bibliography', t.bibliography, route('aula.bibliography', course.slug)],
                            ['glossary', t.glossary, route('aula.glossary', course.slug)],
                            ['complementary', t.complementary, route('aula.complementary', course.slug)],
                        ].map(([key, label, href]) => (
                            <Link
                                key={key}
                                href={href as string}
                                className={`rounded-lg px-3 py-2 font-medium transition ${isActive(key as string) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'}`}
                            >
                                {label}
                            </Link>
                        ))}
                        {settings.enable_search && (
                            <Link
                                href={route('aula.search', course.slug)}
                                className={`rounded-lg px-3 py-2 font-medium transition ${isActive('search') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'}`}
                            >
                                🔍 {t.search}
                            </Link>
                        )}
                    </nav>

                    <div className="flex items-center gap-2">
                        <LanguageSwitcher />
                        {settings.ai_assistant_enabled && (
                            <Button size="sm" variant={assistantOpen ? 'secondary' : 'outline'} onClick={() => setAssistantOpen((v) => !v)}>
                                ✦ {t.assistant}
                            </Button>
                        )}
                        {auth.user ? (
                            <span className="hidden text-sm text-slate-500 sm:block">{auth.user.name}</span>
                        ) : (
                            <Link href={route('login')}>
                                <Button size="sm" variant="outline">{t.signIn}</Button>
                            </Link>
                        )}
                    </div>
                </div>

                {menuOpen && (
                    <nav className="border-t border-slate-100 px-4 py-2 text-sm md:hidden">
                        <Link href="/" className="block rounded-lg px-3 py-2 font-medium text-slate-900 hover:bg-slate-100">
                            ← Volver al inicio de AulaGen
                        </Link>
                        {[
                            [t.home, route('aula.home', course.slug)],
                            [t.activities, route('aula.activities', course.slug)],
                            [t.bibliography, route('aula.bibliography', course.slug)],
                            [t.glossary, route('aula.glossary', course.slug)],
                            [t.complementary, route('aula.complementary', course.slug)],
                            ...(settings.enable_search ? [[t.search, route('aula.search', course.slug)]] : []),
                        ].map(([label, href]) => (
                            <Link key={label as string} href={href as string} className="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-100">
                                {label}
                            </Link>
                        ))}
                    </nav>
                )}
            </header>

            <div className="mx-auto flex max-w-6xl gap-6 px-4 py-6">
                <aside className="hidden w-64 shrink-0 md:block">
                    <div className="sticky top-4 space-y-4">
                        <nav className="rounded-xl border border-slate-200 bg-white p-3">
                            <p className="mb-2 px-2 text-xs font-semibold uppercase text-slate-400">{t.contents}</p>
                            <ul className="space-y-3">
                                {navigation.map((module) => (
                                    <li key={module.id}>
                                        <Link
                                            href={route('aula.module', [course.slug, module.slug])}
                                            className="block rounded-lg px-2 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-100"
                                        >
                                            {module.title}
                                        </Link>
                                        {module.lessons.length > 0 && (
                                            <ul className="mt-1 space-y-0.5 border-l border-slate-200 pl-3">
                                                {module.lessons.map((lesson) => {
                                                    const done = progress.includes(lesson.id);

                                                    return (
                                                        <li key={lesson.id}>
                                                            <Link
                                                                href={route('aula.lesson', [course.slug, module.slug, lesson.slug])}
                                                                className="flex items-start gap-2 rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100"
                                                            >
                                                                <span className={`mt-0.5 text-xs ${done ? 'text-emerald-500' : 'text-slate-300'}`}>
                                                                    {done ? '✓' : '○'}
                                                                </span>
                                                                <span className="line-clamp-2">{lesson.title}</span>
                                                            </Link>
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </nav>

                        {settings.show_progress && (
                            <div className="rounded-xl border border-slate-200 bg-white p-4 text-sm">
                                <p className="font-medium text-slate-700">{t.progress}</p>
                                <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        className="h-full rounded-full bg-emerald-500 transition-all"
                                        style={{
                                            width: `${Math.round(
                                                (progress.length / Math.max(1, navigation.reduce((sum, m) => sum + m.lessons.length, 0))) * 100,
                                            )}%`,
                                        }}
                                    />
                                </div>
                                <p className="mt-1 text-xs text-slate-500">
                                    {progress.length} / {navigation.reduce((sum, m) => sum + m.lessons.length, 0)} {t.lessons}
                                </p>
                            </div>
                        )}
                    </div>
                </aside>

                <main className="min-w-0 flex-1">{children}</main>
            </div>

            <footer className="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-400">
                {course.name} · {course.institution} {course.career ? `· ${course.career}` : ''} — {t.generated}
            </footer>

            {settings.ai_assistant_enabled && assistantOpen && (
                <AssistantPanel courseSlug={course.slug} onClose={() => setAssistantOpen(false)} />
            )}
        </div></LocalizedContent>
    );
}

function AssistantPanel({ courseSlug, onClose }: { courseSlug: string; onClose: () => void }) {
    const locale = (usePage().props.locale ?? 'en') as 'en' | 'es';
    const [question, setQuestion] = useState('');
    const [busy, setBusy] = useState(false);
    const [history, setHistory] = useState<{ q: string; a: ChatAnswer }[]>([]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const value = question.trim();
        if (!value || busy) return;

        setBusy(true);
        setQuestion('');

        axios
            .post<ChatAnswer>(route('aula.ask', courseSlug), { question: value })
            .then((response) => setHistory((prev) => [...prev, { q: value, a: response.data }]))
            .catch(() =>
                setHistory((prev) => [
                    ...prev,
                    { q: value, a: { answer: translate('No pude responder en este momento. Intentá de nuevo en unos segundos.', locale), error: true } },
                ]),
            )
            .finally(() => setBusy(false));
    };

    return (
        <div className="fixed inset-y-0 right-0 z-40 flex w-full max-w-md flex-col border-l border-slate-200 bg-white shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div>
                    <p className="text-sm font-semibold text-slate-900">Asistente de la materia</p>
                    <p className="text-xs text-slate-500">Responde solo con el material cargado por el equipo docente.</p>
                </div>
                <button onClick={onClose} className="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100">✕</button>
            </div>

            <div className="flex-1 space-y-4 overflow-y-auto p-4">
                {history.length === 0 && (
                    <div className="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                        Ejemplos:
                        <ul className="mt-2 list-inside list-disc space-y-1 text-slate-500">
                            <li>¿Qué es la notación O?</li>
                            <li>¿Cuál es la diferencia entre pila y cola?</li>
                            <li>¿Cómo funciona BFS?</li>
                        </ul>
                    </div>
                )}

                {history.map((item, index) => (
                    <div key={index} className="space-y-2">
                        <div className="ml-auto max-w-[85%] rounded-2xl rounded-br-sm bg-indigo-600 px-4 py-2 text-sm text-white">{item.q}</div>
                        <div className="max-w-[90%] whitespace-pre-wrap rounded-2xl rounded-bl-sm bg-slate-100 px-4 py-3 text-sm text-slate-700">
                            {item.a.answer}
                        </div>
                        {item.a.sources && item.a.sources.length > 0 && (
                            <div className="space-y-1 text-xs text-slate-500">
                                {item.a.sources.map((source, sourceIndex) => (
                                    <div key={sourceIndex} className="rounded-lg border border-slate-200 px-3 py-1.5">
                                        📄 {source.label}
                                        {source.quote ? ` — "${source.quote.slice(0, 120)}…"` : ''}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ))}

                {busy && <p className="text-sm text-slate-400">Pensando…</p>}
            </div>

            <form onSubmit={submit} className="border-t border-slate-200 p-3">
                <div className="flex gap-2">
                    <input
                        value={question}
                        onChange={(e) => setQuestion(e.target.value)}
                        placeholder="Escribí tu pregunta…"
                        className="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    />
                    <Button type="submit" size="sm" disabled={busy}>Enviar</Button>
                </div>
                <p className="mt-1 text-[11px] text-slate-400">La IA puede equivocarse: siempre citá la fuente indicada.</p>
            </form>
        </div>
    );
}
