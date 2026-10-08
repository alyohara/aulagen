import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input, Label, Select, Textarea } from '@/Components/ui/form';
import { CourseSummary } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface LessonData {
    id: number;
    title: string;
    slug: string;
    content: string | null;
    summary: string | null;
    status: string;
    status_label: string;
    badge: string;
    is_ai_generated: boolean;
    version: number;
    generated_at?: string | null;
    sources: string[];
    module: { id: number; title: string } | null;
}

interface Props {
    course: CourseSummary;
    lesson: LessonData;
    sourcesIndex: { label: string; content: string }[];
}

const statusOptions = [
    ['draft', 'Borrador'],
    ['generated', 'Generado'],
    ['review', 'Revisión'],
    ['approved', 'Aprobado'],
    ['published', 'Publicado'],
];

export default function EditLesson({ course, lesson, sourcesIndex }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        title: lesson.title,
        summary: lesson.summary ?? '',
        content: lesson.content ?? '',
        status: lesson.status,
    });
    const [preview, setPreview] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(route('courses.lessons.update', [course.id, lesson.id]), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-semibold text-slate-900">{lesson.title}</h1>
                            <Badge className={lesson.badge}>{lesson.status_label}</Badge>
                            {lesson.is_ai_generated && <Badge tone="info">IA</Badge>}
                        </div>
                        <p className="text-sm text-slate-500">
                            {course.name} · {lesson.module?.title ?? 'Sin módulo'}
                            {lesson.generated_at && ` · generado ${lesson.generated_at}`}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('courses.content', course.id)}>
                            <Button variant="outline" size="sm">Volver al contenido</Button>
                        </Link>
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => router.post(route('courses.lessons.generate', [course.id, lesson.id]), {}, { preserveScroll: true })}
                        >
                            Regenerar con IA
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`${lesson.title} · edición`} />

            <div className="mx-auto max-w-6xl space-y-6 py-8 sm:px-6 lg:px-8">
                <form onSubmit={submit} className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Lección</CardTitle>
                            <CardDescription>
                                El contenido es HTML. Todo cambio de contenido vuelve a estado «Revisión» hasta que lo apruebes.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="sm:col-span-2">
                                    <Label htmlFor="title">Título</Label>
                                    <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                                    {errors.title && <p className="mt-1 text-xs text-rose-600">{errors.title}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="status">Estado</Label>
                                    <Select id="status" value={data.status} onChange={(e) => setData('status', e.target.value)}>
                                        {statusOptions.map(([value, label]) => (
                                            <option key={value} value={value}>{label}</option>
                                        ))}
                                    </Select>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="summary">Resumen (aparece en la lista del aula)</Label>
                                <Textarea id="summary" value={data.summary ?? ''} onChange={(e) => setData('summary', e.target.value)} />
                            </div>

                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <Label htmlFor="content" className="mb-0">Contenido (HTML)</Label>
                                    <Button type="button" size="sm" variant="ghost" onClick={() => setPreview((p) => !p)}>
                                        {preview ? 'Editar' : 'Vista previa'}
                                    </Button>
                                </div>
                                {preview ? (
                                    <div
                                        className="aula-prose rounded-lg border border-slate-200 p-4"
                                        dangerouslySetInnerHTML={{ __html: data.content }}
                                    />
                                ) : (
                                    <Textarea
                                        id="content"
                                        className="min-h-[420px] font-mono text-[13px]"
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                    />
                                )}
                                {errors.content && <p className="mt-1 text-xs text-rose-600">{errors.content}</p>}
                            </div>

                            <div className="flex items-center gap-3">
                                <Button type="submit" disabled={processing}>Guardar lección</Button>
                                <span className="text-xs text-slate-500">v{lesson.version}</span>
                            </div>
                        </CardContent>
                    </Card>
                </form>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Fuentes citadas en el contenido</CardTitle>
                            <CardDescription>Se muestran al alumno si la configuración lo permite.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {lesson.sources.length === 0 && <p className="text-sm text-slate-500">Esta lección no tiene fuentes declaradas.</p>}
                            {lesson.sources.map((source, index) => (
                                <div key={index} className="rounded-lg border border-slate-200 p-3 text-sm text-slate-700">{source}</div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Fragmentos del material relacionados</CardTitle>
                            <CardDescription>Chips usados por la IA al generar (top 10 por coincidencia).</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {sourcesIndex.length === 0 && <p className="text-sm text-slate-500">Cargá material en la materia para indexar fragmentos.</p>}
                            {sourcesIndex.map((chunk, index) => (
                                <div key={index} className="rounded-lg border border-slate-200 p-3">
                                    <div className="text-xs font-medium text-indigo-600">{chunk.label}</div>
                                    <p className="mt-1 line-clamp-3 text-xs text-slate-500">{chunk.content}…</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
