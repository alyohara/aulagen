import AulaLayout from '@/Layouts/AulaLayout';
import { Alert, Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { AulaShared, LessonRow } from '@/types/models';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface LessonData {
    id: number;
    title: string;
    content: string | null;
    summary?: string | null;
    status: string;
    status_label: string;
    sources: string[];
    module: { id: number; title: string; slug: string };
    generated_at?: string | null;
}

interface Props extends AulaShared {
    lesson: LessonData;
    prev: { id: number; title: string; url: string } | null;
    next: { id: number; title: string; url: string } | null;
    relatedDocuments: { id: number; name: string; url: string; external: boolean }[];
    progress: number[];
}

export default function AulaLesson({ course, navigation, settings, isPreview, previewUrl, lesson, prev, next, relatedDocuments, progress }: Props) {
    const { auth } = usePage().props as unknown as { auth: { user: { id: number } | null } };
    const [done, setDone] = useState(progress.includes(lesson.id));

    const markComplete = () => {
        const completed = !done;
        setDone(completed);
        router.post(
            route('aula.progress', course.slug),
            { lesson_id: lesson.id, completed },
            { preserveScroll: true, only: [], onError: () => setDone(!completed) },
        );
    };

    return (
        <AulaLayout course={course} navigation={navigation} settings={settings} isPreview={isPreview} previewUrl={previewUrl} active="home" progress={progress}>
            <Head title={`${lesson.title} · ${course.name}`} />

            <article className="space-y-6">
                <div>
                    <p className="text-sm text-slate-400">
                        <Link href={route('aula.home', course.slug)} className="hover:text-indigo-600">Inicio</Link>{' / '}
                        <Link href={route('aula.module', [course.slug, lesson.module.slug])} className="hover:text-indigo-600">{lesson.module.title}</Link>
                    </p>
                    <div className="mt-1 flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-bold text-slate-900">{lesson.title}</h1>
                        {lesson.status !== 'published' && <Badge tone="warning">{lesson.status_label}</Badge>}
                    </div>
                    {lesson.summary && <p className="mt-2 text-slate-600">{lesson.summary}</p>}
                </div>

                {isPreview && lesson.status !== 'published' && (
                    <Alert tone="warning">Esta lección está en estado «{lesson.status_label}» y no es visible para los alumnos.</Alert>
                )}

                <Card>
                    <CardContent className="p-6">
                        {lesson.content ? (
                            <div className="aula-prose" dangerouslySetInnerHTML={{ __html: lesson.content }} />
                        ) : (
                            <p className="text-sm text-slate-500">Esta lección todavía no tiene contenido aprobado.</p>
                        )}
                    </CardContent>
                </Card>

                {settings.show_sources && lesson.sources.length > 0 && (
                    <Card>
                        <CardContent className="p-5">
                            <p className="mb-2 text-sm font-semibold text-slate-800">Fuentes de esta lección</p>
                            <ul className="space-y-1 text-sm text-slate-600">
                                {lesson.sources.map((source, index) => (
                                    <li key={index} className="rounded-lg bg-slate-50 px-3 py-2">{source}</li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                {relatedDocuments.length > 0 && (
                    <Card>
                        <CardContent className="p-5">
                            <p className="mb-2 text-sm font-semibold text-slate-800">Material relacionado</p>
                            <div className="space-y-1">
                                {relatedDocuments.map((doc) => (
                                    <a
                                        key={doc.id}
                                        href={doc.url}
                                        target={doc.external ? '_blank' : undefined}
                                        rel="noreferrer"
                                        className="block rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                    >
                                        📄 {doc.name}
                                    </a>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex gap-2">
                        {prev && (
                            <Link href={prev.url}>
                                <Button variant="outline" size="sm">← {prev.title}</Button>
                            </Link>
                        )}
                        {next && (
                            <Link href={next.url}>
                                <Button variant="outline" size="sm">{next.title} →</Button>
                            </Link>
                        )}
                    </div>

                    {settings.show_progress && auth.user && (
                        <Button size="sm" variant={done ? 'success' : 'secondary'} onClick={markComplete}>
                            {done ? '✓ Lección completada' : 'Marcar como completada'}
                        </Button>
                    )}
                </div>

                {lesson.generated_at && (
                    <p className="text-xs text-slate-400">Contenido generado con IA el {lesson.generated_at} y revisado por el equipo docente.</p>
                )}
            </article>
        </AulaLayout>
    );
}
