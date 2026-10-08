import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Head, Link, usePage } from '@inertiajs/react';

interface CourseRow {
    id: number;
    name: string;
    slug: string;
    institution?: string | null;
    status: string;
    status_label: string;
    modules_count: number;
    documents_count: number;
    lessons_count: number;
    published_lessons: number;
    pending_lessons: number;
    updated_at?: string;
    url: string;
}

export default function Dashboard({ courses, stats }: { courses: CourseRow[]; stats: Record<string, number> }) {
    const { user } = usePage().props.auth;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Mis materias</h1>
                        <p className="text-sm text-slate-500">Hola {user?.name}, elegí una materia para trabajar.</p>
                    </div>
                    <Link href={route('courses.create')}>
                        <Button>+ Nueva materia</Button>
                    </Link>
                </div>
            }
        >
            <Head title="Mis materias" />

            <div className="mx-auto max-w-7xl space-y-6 py-8 sm:px-6 lg:px-8">
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {[
                        ['Materias', stats.courses],
                        ['Publicadas', stats.published],
                        ['Materiales', stats.documents],
                        ['Lecciones', stats.lessons],
                    ].map(([label, value]) => (
                        <Card key={label as string}>
                            <CardContent className="p-5">
                                <div className="text-2xl font-semibold text-slate-900">{(value as number) ?? 0}</div>
                                <div className="text-sm text-slate-500">{label}</div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {courses.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Todavía no tenés materias</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-slate-500">
                                Creá tu primera materia, cargá el material de clase y dejá que la IA proponga la estructura del aula.
                            </p>
                            <Link href={route('courses.create')}>
                                <Button>Crear la primera materia</Button>
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {courses.map((course) => (
                            <Card key={course.id} className="flex flex-col">
                                <CardHeader>
                                    <div className="flex items-start justify-between gap-2">
                                        <CardTitle>{course.name}</CardTitle>
                                        <Badge tone={course.status === 'published' ? 'success' : 'warning'}>{course.status_label}</Badge>
                                    </div>
                                    {course.institution && <p className="text-sm text-slate-500">{course.institution}</p>}
                                </CardHeader>
                                <CardContent className="flex flex-1 flex-col gap-3">
                                    <div className="grid grid-cols-3 gap-2 text-center text-xs text-slate-500">
                                        <div className="rounded-lg bg-slate-50 p-2">
                                            <div className="text-base font-semibold text-slate-800">{course.modules_count}</div>
                                            módulos
                                        </div>
                                        <div className="rounded-lg bg-slate-50 p-2">
                                            <div className="text-base font-semibold text-slate-800">{course.lessons_count}</div>
                                            lecciones
                                        </div>
                                        <div className="rounded-lg bg-slate-50 p-2">
                                            <div className="text-base font-semibold text-slate-800">{course.documents_count}</div>
                                            materiales
                                        </div>
                                    </div>

                                    <div className="text-xs text-slate-500">
                                        {course.published_lessons} visibles · {course.pending_lessons} pendientes
                                    </div>

                                    <div className="mt-auto flex flex-wrap gap-2">
                                        <Link href={route('courses.overview', course.id)}>
                                            <Button size="sm" variant="outline">Abrir</Button>
                                        </Link>
                                        <Link href={route('courses.content', course.id)}>
                                            <Button size="sm" variant="outline">Contenido</Button>
                                        </Link>
                                        <Link href={course.url}>
                                            <Button size="sm" variant="ghost">Ver aula →</Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
