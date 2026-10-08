import AulaLayout from '@/Layouts/AulaLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { AulaShared, LessonRow } from '@/types/models';
import { Head, Link } from '@inertiajs/react';

interface Props extends AulaShared {
    stats: { modules: number; lessons: number; activities: number; documents: number };
    objectives?: string | null;
    program?: string | null;
    description?: string | null;
    continue: LessonRow | null;
    progress: number[];
    bibliography: string[];
}

const lines = (value?: string | null) => (value ?? '').split('\n').map((l) => l.trim()).filter(Boolean);

export default function AulaHome({ course, navigation, settings, isPreview, previewUrl, stats, objectives, program, description, continue: continueLesson, progress, bibliography }: Props) {
    return (
        <AulaLayout course={course} navigation={navigation} settings={settings} isPreview={isPreview} previewUrl={previewUrl} active="home" progress={progress}>
            <Head title={course.name} />

            <div className="space-y-6">
                <Card className="border-indigo-100 bg-gradient-to-br from-indigo-50 to-white">
                    <CardContent className="p-6">
                        <div className="flex flex-wrap items-center gap-2">
                            {course.course_year && <Badge tone="info">{course.course_year}</Badge>}
                            {course.duration && <Badge tone="muted">{course.duration}</Badge>}
                            {course.modality && <Badge tone="muted">{course.modality}</Badge>}
                        </div>
                        <h1 className="mt-3 text-2xl font-bold text-slate-900">{course.name}</h1>
                        <p className="mt-2 text-slate-600">{description ?? course.description}</p>

                        <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {[
                                ['Módulos', stats.modules],
                                ['Lecciones', stats.lessons],
                                ['Actividades', stats.activities],
                                ['Materiales', stats.documents],
                            ].map(([label, value]) => (
                                <div key={label as string} className="rounded-xl bg-white/70 p-3 text-center">
                                    <div className="text-xl font-bold text-indigo-700">{value as number}</div>
                                    <div className="text-xs text-slate-500">{label}</div>
                                </div>
                            ))}
                        </div>

                        {continueLesson && (
                            <div className="mt-5">
                                <Link href={continueLesson.url ?? route('aula.home', course.slug)}>
                                    <Button>Continuar: {continueLesson.title} →</Button>
                                </Link>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Objetivos de aprendizaje</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {lines(objectives).length > 0 ? (
                                <ul className="list-inside list-disc space-y-1 text-sm text-slate-600">
                                    {lines(objectives).map((objective, index) => (
                                        <li key={index}>{objective}</li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-slate-500">Sin objetivos cargados.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Programa</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {lines(program).length > 0 ? (
                                <ol className="list-inside list-decimal space-y-1 text-sm text-slate-600">
                                    {lines(program).map((item, index) => (
                                        <li key={index}>{item}</li>
                                    ))}
                                </ol>
                            ) : (
                                <p className="text-sm text-slate-500">Sin programa cargado.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    {navigation
                        .filter((module) => module.lessons.length > 0)
                        .map((module) => (
                            <Card key={module.id} className="hover:border-indigo-200">
                                <CardHeader>
                                    <CardTitle>
                                        <Link href={route('aula.module', [course.slug, module.slug])} className="hover:text-indigo-600">
                                            {module.title}
                                        </Link>
                                    </CardTitle>
                                    <CardDescription>{module.lessons.length} lecciones</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-1">
                                    {module.lessons.slice(0, 4).map((lesson) => (
                                        <Link
                                            key={lesson.id}
                                            href={route('aula.lesson', [course.slug, module.slug, lesson.slug])}
                                            className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
                                        >
                                            <span className={progress.includes(lesson.id) ? 'text-emerald-500' : 'text-slate-300'}>
                                                {progress.includes(lesson.id) ? '✓' : '○'}
                                            </span>
                                            {lesson.title}
                                        </Link>
                                    ))}
                                    {module.lessons.length > 4 && <p className="px-2 text-xs text-slate-400">+{module.lessons.length - 4} más…</p>}
                                </CardContent>
                            </Card>
                        ))}
                </div>

                {bibliography.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Bibliografía principal</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm text-slate-600">
                            {bibliography.map((entry, index) => (
                                <p key={index}>{entry}</p>
                            ))}
                            <Link href={route('aula.bibliography', course.slug)} className="inline-block pt-2 text-sm font-medium text-indigo-600 hover:underline">
                                Ver toda la bibliografía →
                            </Link>
                        </CardContent>
                    </Card>
                )}

                {course.owner && (
                    <p className="text-center text-xs text-slate-400">Docente a cargo: {course.owner}{course.collaborators ? ` · ${course.collaborators}` : ''}</p>
                )}
            </div>
        </AulaLayout>
    );
}
