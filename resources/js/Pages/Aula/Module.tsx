import AulaLayout from '@/Layouts/AulaLayout';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { AulaShared, DocumentRow, LessonRow } from '@/types/models';
import { Head, Link } from '@inertiajs/react';

interface Props extends AulaShared {
    module: { id: number; title: string; slug: string; summary?: string | null; type: string };
    lessons: LessonRow[];
    progress: number[];
    documents: DocumentRow[];
}

export default function AulaModule({ course, navigation, settings, isPreview, previewUrl, module, lessons, progress, documents }: Props) {
    return (
        <AulaLayout course={course} navigation={navigation} settings={settings} isPreview={isPreview} previewUrl={previewUrl} active="home" progress={progress}>
            <Head title={`${module.title} · ${course.name}`} />

            <div className="space-y-6">
                <div>
                    <p className="text-sm text-slate-400">
                        <Link href={route('aula.home', course.slug)} className="hover:text-indigo-600">Inicio</Link> / Módulo
                    </p>
                    <h1 className="text-2xl font-bold text-slate-900">{module.title}</h1>
                    {module.summary && <p className="mt-2 text-slate-600">{module.summary}</p>}
                </div>

                <div className="space-y-3">
                    {lessons.map((lesson, index) => {
                        const done = progress.includes(lesson.id);

                        return (
                            <Link key={lesson.id} href={lesson.url ?? route('aula.lesson', [course.slug, module.slug ?? module.title, lesson.slug])}>
                                <Card className="transition hover:border-indigo-300 hover:shadow-sm">
                                    <CardContent className="flex items-center gap-4 p-4">
                                        <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold ${done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                                            {done ? '✓' : index + 1}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <div className="font-semibold text-slate-800">{lesson.title}</div>
                                            {lesson.summary && <p className="line-clamp-2 text-sm text-slate-500">{lesson.summary}</p>}
                                        </div>
                                        <Badge className={lesson.badge}>{lesson.status_label}</Badge>
                                    </CardContent>
                                </Card>
                            </Link>
                        );
                    })}
                    {lessons.length === 0 && (
                        <Card>
                            <CardContent className="p-5 text-sm text-slate-500">Este módulo todavía no tiene lecciones visibles.</CardContent>
                        </Card>
                    )}
                </div>

                {documents.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Material del módulo</CardTitle>
                            <CardDescription>Archivos y enlaces cargados por el equipo docente.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {documents.map((doc) => (
                                <a
                                    key={doc.id}
                                    href={doc.url}
                                    target={doc.external ? '_blank' : undefined}
                                    rel="noreferrer"
                                    className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                >
                                    <span>{doc.external ? '🔗 ' : '📄 '}{doc.name}</span>
                                    <span className="text-xs text-slate-400">{doc.external ? 'abrir' : doc.size}</span>
                                </a>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AulaLayout>
    );
}
