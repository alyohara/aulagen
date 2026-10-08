import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Alert, Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Head, Link, router } from '@inertiajs/react';
import { CourseSummary } from '@/types/models';

interface Proposal {
    modules?: { title: string; summary?: string; lessons?: { title: string; summary?: string }[] }[];
    rationale?: string;
}

interface Props {
    course: CourseSummary & { url: string; settings: Record<string, boolean> | null; has_proposal: boolean };
    stats: Record<string, number>;
    recentDocuments: { id: number; name: string; status: string; type: string }[];
    proposal: Proposal | null;
    provider: string;
}

const statusTone: Record<string, 'success' | 'warning' | 'info'> = {
    ready: 'success',
    published: 'success',
    pending: 'warning',
    processing: 'info',
};

export default function Overview({ course, stats, recentDocuments, proposal, provider }: Props) {
    const published = course.status === 'published';

    const publish = () => (published ? router.post(route('courses.unpublish', course.id)) : router.post(route('courses.publish', course.id)));

    const steps = [
        { title: '1. Materiales', value: `${stats.documents_ready ?? 0}/${stats.documents ?? 0} procesados`, href: route('courses.documents.index', course.id), done: (stats.documents_ready ?? 0) > 0 },
        { title: '2. Estructura', value: `${stats.modules ?? 0} módulos`, href: route('courses.content', course.id), done: (stats.modules ?? 0) > 0 },
        { title: '3. Contenido', value: `${stats.pending_lessons ?? 0} lecciones pendientes`, href: route('courses.content', course.id), done: (stats.lessons ?? 0) > 0 && (stats.pending_lessons ?? 0) === 0 },
        { title: '4. Actividades', value: `${stats.activities ?? 0} creadas`, href: route('courses.activities.index', course.id), done: (stats.activities ?? 0) > 0 },
        { title: '5. Bibliografía', value: `${stats.bibliography ?? 0} referencias`, href: route('courses.bibliography.index', course.id), done: (stats.bibliography ?? 0) > 0 },
    ];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-semibold text-slate-900">{course.name}</h1>
                            <Badge tone={published ? 'success' : 'warning'}>{course.status_label}</Badge>
                        </div>
                        <p className="text-sm text-slate-500">{course.institution} {course.career ? `· ${course.career}` : ''}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href={route('courses.edit', course.id)}>
                            <Button variant="outline" size="sm">Editar datos</Button>
                        </Link>
                        <Link href={route('courses.ai.index', course.id)}>
                            <Button variant="outline" size="sm">Asistente IA</Button>
                        </Link>
                        <Link href={course.url} target="_blank">
                            <Button variant="outline" size="sm">Ver aula</Button>
                        </Link>
                        <Button size="sm" variant={published ? 'danger' : 'success'} onClick={publish}>
                            {published ? 'Despublicar' : 'Publicar aula'}
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={course.name} />

            <div className="mx-auto max-w-7xl space-y-6 py-8 sm:px-6 lg:px-8">
                {proposal && <Alert tone="info">Hay una estructura propuesta por la IA pendiente de revisión. <Link className="font-semibold underline" href={route('courses.content', course.id)}>Revisarla</Link></Alert>}
                {!proposal && !published && <Alert tone="warning">La materia está en borrador: los alumnos no pueden verla todavía.</Alert>}

                <div className="grid gap-4 md:grid-cols-5">
                    {steps.map((step) => (
                        <Link key={step.title} href={step.href} className="block">
                            <Card className="h-full transition hover:border-indigo-300">
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-semibold text-slate-800">{step.title}</span>
                                        {step.done && <Badge tone="success">OK</Badge>}
                                    </div>
                                    <p className="mt-2 text-xs text-slate-500">{step.value}</p>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm text-slate-600">
                            <div className="flex justify-between"><span>Módulos</span><strong>{stats.modules}</strong></div>
                            <div className="flex justify-between"><span>Unidades</span><strong>{stats.units}</strong></div>
                            <div className="flex justify-between"><span>Lecciones</span><strong>{stats.lessons}</strong></div>
                            <div className="flex justify-between"><span>Lecciones pendientes</span><strong>{stats.pending_lessons}</strong></div>
                            <div className="flex justify-between"><span>Proveedor de IA</span><strong>{provider}</strong></div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Últimos materiales</CardTitle>
                            <CardDescription>Estado del procesamiento</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {recentDocuments.length === 0 && <p className="text-sm text-slate-500">Sin materiales cargados.</p>}
                            {recentDocuments.map((doc) => (
                                <div key={doc.id} className="flex items-center justify-between gap-2 text-sm">
                                    <span className="truncate text-slate-700">{doc.name}</span>
                                    <Badge tone={statusTone[doc.status] ?? 'default'}>{doc.status}</Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Accesos rápidos</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            <Link href={route('courses.content', course.id)}><Button variant="outline" size="sm" className="w-full">Estructura y contenido</Button></Link>
                            <Link href={route('courses.documents.index', course.id)}><Button variant="outline" size="sm" className="w-full">Cargar materiales</Button></Link>
                            <Link href={route('courses.activities.index', course.id)}><Button variant="outline" size="sm" className="w-full">Actividades</Button></Link>
                            <Link href={route('courses.bibliography.index', course.id)}><Button variant="outline" size="sm" className="w-full">Bibliografía</Button></Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
