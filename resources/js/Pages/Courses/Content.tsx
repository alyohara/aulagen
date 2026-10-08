import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Alert, Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input, Label, Select, Textarea } from '@/Components/ui/form';
import { Modal } from '@/Components/ui/modal';
import { CourseSummary, ModuleNode } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface ProposalModule {
    title: string;
    summary?: string;
    lessons?: { title: string; summary?: string }[];
}

interface Props {
    course: CourseSummary & { has_proposal: boolean };
    modules: ModuleNode[];
    proposal: { modules?: ProposalModule[]; rationale?: string } | null;
    documents: { id: number; name: string; status: string; module: string | null }[];
    provider: string;
}

const statusOptions = [
    ['draft', 'Borrador'],
    ['generated', 'Generado'],
    ['review', 'Revisión'],
    ['approved', 'Aprobado'],
    ['published', 'Publicado'],
];

export default function Content({ course, modules, proposal, provider }: Props) {
    const [moduleModal, setModuleModal] = useState<null | { id?: number; title: string; summary: string }>(null);
    const [lessonModal, setLessonModal] = useState<null | { moduleId: number; title: string; summary: string }>(null);

    const submitModule = (event: { preventDefault: () => void }) => {
        event.preventDefault();
        if (!moduleModal) return;

        if (moduleModal.id) {
            router.put(route('courses.modules.update', [course.id, moduleModal.id]), { title: moduleModal.title, summary: moduleModal.summary }, {
                preserveScroll: true,
                onSuccess: () => setModuleModal(null),
            });
        } else {
            router.post(route('courses.modules.store', course.id), { title: moduleModal.title, summary: moduleModal.summary }, {
                preserveScroll: true,
                onSuccess: () => setModuleModal(null),
            });
        }
    };

    const submitLesson = (event: { preventDefault: () => void }) => {
        event.preventDefault();
        if (!lessonModal) return;

        router.post(route('courses.lessons.store', [course.id, lessonModal.moduleId]), { title: lessonModal.title, summary: lessonModal.summary }, {
            preserveScroll: true,
            onSuccess: () => setLessonModal(null),
        });
    };

    const moveModule = (index: number, direction: -1 | 1) => {
        const order = modules.map((m) => m.id);
        const target = index + direction;
        if (target < 0 || target >= order.length) return;
        [order[index], order[target]] = [order[target], order[index]];
        router.post(route('courses.modules.reorder', course.id), { order }, { preserveScroll: true, preserveState: true });
    };

    const changeLessonStatus = (lessonId: number, status: string) => {
        router.post(route('courses.lessons.status', [course.id, lessonId]), { status }, { preserveScroll: true, preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Estructura y contenido</h1>
                        <p className="text-sm text-slate-500">{course.name} · proveedor de IA: {provider}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href={route('courses.overview', course.id)}>
                            <Button variant="outline" size="sm">Volver</Button>
                        </Link>
                        <Button size="sm" variant="secondary" onClick={() => router.post(route('courses.structure.propose', course.id), {}, { preserveScroll: true })}>
                            Proponer estructura con IA
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => router.post(route('courses.content.generate-all', course.id), {}, { preserveScroll: true })}>
                            Generar contenido faltante
                        </Button>
                        <Button size="sm" onClick={() => setModuleModal({ title: '', summary: '' })}>+ Módulo</Button>
                    </div>
                </div>
            }
        >
            <Head title={`Contenido · ${course.name}`} />

            <div className="mx-auto max-w-5xl space-y-6 py-8 sm:px-6 lg:px-8">
                {proposal?.modules?.length ? (
                    <Card className="border-indigo-200">
                        <CardHeader>
                            <CardTitle>Propuesta de estructura (IA)</CardTitle>
                            <CardDescription>{proposal.rationale ?? 'La IA propuso esta organización basada en tu material cargado.'}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {proposal.modules.map((module, index) => (
                                <div key={index} className="rounded-lg border border-slate-200 p-3">
                                    <div className="font-medium text-slate-800">{module.title}</div>
                                    {module.summary && <p className="text-sm text-slate-500">{module.summary}</p>}
                                    <ul className="mt-2 list-inside list-disc text-sm text-slate-600">
                                        {(module.lessons ?? []).map((lesson, lessonIndex) => (
                                            <li key={lessonIndex}>{lesson.title}</li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                            <div className="flex gap-2">
                                <Button size="sm" onClick={() => router.post(route('courses.structure.apply', course.id), { proposal: proposal as Record<string, string> }, { preserveScroll: true })}>Aplicar estructura</Button>
                                <Button size="sm" variant="outline" onClick={() => router.post(route('courses.structure.discard', course.id), {}, { preserveScroll: true })}>
                                    Descartar
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                ) : null}

                {modules.length === 0 && !proposal && (
                    <Alert tone="info">
                        Todavía no hay módulos. Crealos manualmente con «+ Módulo» o pedile a la IA que proponga una estructura a partir del material cargado.
                    </Alert>
                )}

                {modules.map((module, moduleIndex) => (
                    <Card key={module.id}>
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <CardTitle>{module.title}</CardTitle>
                                        {module.type !== 'unit' && <Badge tone="muted">{module.type}</Badge>}
                                        {module.is_ai_generated && <Badge tone="info">IA</Badge>}
                                    </div>
                                    {module.summary && <CardDescription>{module.summary}</CardDescription>}
                                </div>
                                <div className="flex gap-1">
                                    <Button size="sm" variant="ghost" onClick={() => moveModule(moduleIndex, -1)} disabled={moduleIndex === 0}>↑</Button>
                                    <Button size="sm" variant="ghost" onClick={() => moveModule(moduleIndex, 1)} disabled={moduleIndex === modules.length - 1}>↓</Button>
                                    <Button size="sm" variant="outline" onClick={() => setModuleModal({ id: module.id, title: module.title, summary: module.summary ?? '' })}>Editar</Button>
                                    <Button size="sm" variant="outline" onClick={() => setLessonModal({ moduleId: module.id, title: '', summary: '' })}>+ Lección</Button>
                                    <Button
                                        size="sm"
                                        variant="danger"
                                        onClick={() => {
                                            if (confirm(`¿Eliminar el módulo "${module.title}" con sus lecciones?`)) {
                                                router.delete(route('courses.modules.destroy', [course.id, module.id]), { preserveScroll: true });
                                            }
                                        }}
                                    >
                                        Eliminar
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {(module.lessons ?? []).length === 0 && <p className="text-sm text-slate-500">Sin lecciones todavía.</p>}
                            {(module.lessons ?? []).map((lesson, lessonIndex) => (
                                <div key={lesson.id} className="flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 px-3 py-2">
                                    <span className="w-6 text-xs text-slate-400">{moduleIndex + 1}.{lessonIndex + 1}</span>
                                    <div className="min-w-0 flex-1">
                                        <Link href={route('courses.lessons.edit', [course.id, lesson.id])} className="font-medium text-slate-800 hover:text-indigo-600">
                                            {lesson.title}
                                        </Link>
                                        <div className="flex items-center gap-2 text-xs text-slate-500">
                                            <Badge className={lesson.badge}>{lesson.status_label}</Badge>
                                            {lesson.is_ai_generated && <span>generado por IA</span>}
                                            {lesson.has_content && <span>· con contenido</span>}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Select
                                            value={lesson.status}
                                            className="h-8 w-36 text-xs"
                                            onChange={(e) => changeLessonStatus(lesson.id, e.target.value)}
                                        >
                                            {statusOptions.map(([value, label]) => (
                                                <option key={value} value={value}>{label}</option>
                                            ))}
                                        </Select>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => router.post(route('courses.lessons.generate', [course.id, lesson.id]), {}, { preserveScroll: true })}
                                        >
                                            Generar con IA
                                        </Button>
                                        <Link href={route('courses.lessons.edit', [course.id, lesson.id])}>
                                            <Button size="sm" variant="ghost">Editar</Button>
                                        </Link>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => {
                                                if (confirm(`¿Eliminar la lección "${lesson.title}"?`)) {
                                                    router.delete(route('courses.lessons.destroy', [course.id, lesson.id]), { preserveScroll: true });
                                                }
                                            }}
                                        >
                                            ✕
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Modal
                open={moduleModal !== null}
                onClose={() => setModuleModal(null)}
                title={moduleModal?.id ? 'Editar módulo' : 'Nuevo módulo'}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setModuleModal(null)}>Cancelar</Button>
                        <Button onClick={submitModule}>Guardar</Button>
                    </>
                }
            >
                <form onSubmit={submitModule} className="space-y-4">
                    <div>
                        <Label htmlFor="module-title">Título</Label>
                        <Input id="module-title" value={moduleModal?.title ?? ''} onChange={(e) => setModuleModal((m) => m && { ...m, title: e.target.value })} />
                    </div>
                    <div>
                        <Label htmlFor="module-summary">Resumen</Label>
                        <Textarea id="module-summary" value={moduleModal?.summary ?? ''} onChange={(e) => setModuleModal((m) => m && { ...m, summary: e.target.value })} />
                    </div>
                    <button type="submit" className="hidden" />
                </form>
            </Modal>

            <Modal
                open={lessonModal !== null}
                onClose={() => setLessonModal(null)}
                title="Nueva lección"
                footer={
                    <>
                        <Button variant="outline" onClick={() => setLessonModal(null)}>Cancelar</Button>
                        <Button onClick={submitLesson}>Crear</Button>
                    </>
                }
            >
                <form onSubmit={submitLesson} className="space-y-4">
                    <div>
                        <Label htmlFor="lesson-title">Título de la lección</Label>
                        <Input id="lesson-title" value={lessonModal?.title ?? ''} onChange={(e) => setLessonModal((m) => m && { ...m, title: e.target.value })} />
                    </div>
                    <div>
                        <Label htmlFor="lesson-summary">Resumen (opcional)</Label>
                        <Textarea id="lesson-summary" value={lessonModal?.summary ?? ''} onChange={(e) => setLessonModal((m) => m && { ...m, summary: e.target.value })} />
                    </div>
                    <button type="submit" className="hidden" />
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
