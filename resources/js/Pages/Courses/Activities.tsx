import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input, Label, Select, Textarea } from '@/Components/ui/form';
import { Modal } from '@/Components/ui/modal';
import { ActivityRow, CourseSummary, QuestionRow } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { confirmLocalized } from '@/lib/i18n';

interface Props {
    course: CourseSummary;
    activities: ActivityRow[];
    modules: { id: number; title: string }[];
}

const types = [
    ['quiz', 'Cuestionario'],
    ['autoeval', 'Autoevaluación'],
    ['flashcards', 'Flashcards'],
    ['exam', 'Preguntas de examen'],
    ['practice', 'Ejercicios prácticos'],
    ['assignment', 'Trabajo práctico'],
];

export default function Activities({ course, activities, modules }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [expanded, setExpanded] = useState<number | null>(null);
    const [question, setQuestion] = useState<{ activity: ActivityRow; question: QuestionRow } | null>(null);

    const createForm = useForm({ title: '', type: 'quiz', instructions: '', module_id: '' });
    const generateForm = useForm({ type: 'quiz', module_id: '' });
    const questionForm = useForm({ prompt: '', options: '', correct_answer: '', explanation: '' });

    const submitCreate = (event: FormEvent) => {
        event.preventDefault();
        createForm.post(route('courses.activities.store', course.id), { preserveScroll: true, onSuccess: () => { setCreateOpen(false); createForm.reset(); } });
    };

    const submitGenerate = (event: FormEvent) => {
        event.preventDefault();
        generateForm.post(route('courses.activities.generate', course.id), { preserveScroll: true });
    };

    const openQuestion = (activity: ActivityRow, item: QuestionRow) => {
        setQuestion({ activity, question: item });
        questionForm.setData({
            prompt: item.prompt,
            options: (item.options ?? []).join('\n'),
            correct_answer: item.correct_answer ?? '',
            explanation: item.explanation ?? '',
        });
    };

    const saveQuestion = (event: FormEvent) => {
        event.preventDefault();
        if (!question) return;

        router.put(
            route('courses.activities.questions.update', [course.id, question.activity.id, question.question.id]),
            {
                prompt: questionForm.data.prompt,
                options: questionForm.data.options.split('\n').map((o) => o.trim()).filter(Boolean),
                correct_answer: questionForm.data.correct_answer,
                explanation: questionForm.data.explanation,
            },
            { preserveScroll: true, onSuccess: () => setQuestion(null) },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Actividades</h1>
                        <p className="text-sm text-slate-500">Cuestionarios, autoevaluaciones, flashcards y exámenes.</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('courses.overview', course.id)}>
                            <Button variant="outline" size="sm">Volver</Button>
                        </Link>
                        <Button size="sm" onClick={() => setCreateOpen(true)}>+ Crear</Button>
                    </div>
                </div>
            }
        >
            <Head title={`Actividades · ${course.name}`} />

            <div className="mx-auto max-w-5xl space-y-6 py-8 sm:px-6 lg:px-8">
                <Card className="border-indigo-200">
                    <CardHeader>
                        <CardTitle>Generar con IA</CardTitle>
                        <CardDescription>Usa el material procesado y el contenido aprobado de la materia como fuente.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitGenerate} className="flex flex-wrap items-end gap-3">
                            <div>
                                <Label>Tipo</Label>
                                <Select className="w-48" value={generateForm.data.type} onChange={(e) => generateForm.setData('type', e.target.value)}>
                                    {types.slice(0, 4).map(([value, label]) => (
                                        <option key={value} value={value}>{label}</option>
                                    ))}
                                </Select>
                            </div>
                            <div>
                                <Label>Módulo (opcional)</Label>
                                <Select className="w-64" value={generateForm.data.module_id} onChange={(e) => generateForm.setData('module_id', e.target.value)}>
                                    <option value="">Toda la materia</option>
                                    {modules.map((module) => (
                                        <option key={module.id} value={module.id}>{module.title}</option>
                                    ))}
                                </Select>
                            </div>
                            <Button type="submit" variant="secondary" disabled={generateForm.processing}>Generar</Button>
                        </form>
                    </CardContent>
                </Card>

                <div className="space-y-3">
                    {activities.length === 0 && (
                        <Card>
                            <CardContent className="p-5 text-sm text-slate-500">No hay actividades todavía. Creá una manualmente o generá una con IA.</CardContent>
                        </Card>
                    )}

                    {activities.map((activity) => (
                        <Card key={activity.id}>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <CardTitle>{activity.title}</CardTitle>
                                            <Badge tone="info">{activity.type_label}</Badge>
                                            <Badge tone={activity.status === 'published' ? 'success' : 'muted'}>{activity.status}</Badge>
                                        </div>
                                        {activity.instructions && <CardDescription>{activity.instructions}</CardDescription>}
                                        <p className="mt-1 text-xs text-slate-400">{activity.questions_count ?? activity.questions?.length ?? 0} preguntas · {activity.cards_count ?? activity.cards?.length ?? 0} tarjetas {activity.module ? `· ${activity.module}` : ''}</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button size="sm" variant="outline" onClick={() => setExpanded(expanded === activity.id ? null : activity.id)}>
                                            {expanded === activity.id ? 'Ocultar' : 'Ver preguntas'}
                                        </Button>
                                        <Select
                                            className="h-8 w-32 text-xs"
                                            value={activity.status}
                                            onChange={(e) => router.put(route('courses.activities.update', [course.id, activity.id]), { status: e.target.value }, { preserveScroll: true, preserveState: true })}
                                        >
                                            <option value="draft">Borrador</option>
                                            <option value="review">Revisión</option>
                                            <option value="approved">Aprobada</option>
                                            <option value="published">Publicada</option>
                                        </Select>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => {
                                                if (confirmLocalized(`¿Eliminar "${activity.title}"?`)) {
                                                    router.delete(route('courses.activities.destroy', [course.id, activity.id]), { preserveScroll: true });
                                                }
                                            }}
                                        >
                                            ✕
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>

                            {expanded === activity.id && (
                                <CardContent className="space-y-2">
                                    {(activity.questions ?? []).map((item, index) => (
                                        <div key={item.id} className="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                                            <span className="text-xs text-slate-400">{index + 1}</span>
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium text-slate-800">{item.prompt}</p>
                                                <ul className="mt-1 list-inside list-disc text-xs text-slate-500">
                                                    {(item.options ?? []).map((option, optionIndex) => (
                                                        <li key={optionIndex} className={option === item.correct_answer ? 'font-medium text-emerald-700' : ''}>
                                                            {option} {option === item.correct_answer && '✓'}
                                                        </li>
                                                    ))}
                                                </ul>
                                                {item.explanation && <p className="mt-1 text-xs text-slate-500">Explicación: {item.explanation}</p>}
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => openQuestion(activity, item)}>Editar</Button>
                                        </div>
                                    ))}

                                    {(activity.cards ?? []).length > 0 && (
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {(activity.cards ?? []).map((card, index) => (
                                                <div key={index} className="rounded-lg border border-slate-200 p-3 text-sm">
                                                    <div className="font-medium text-slate-800">{card.front}</div>
                                                    <div className="mt-1 text-slate-500">{card.back}</div>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {(activity.questions ?? []).length === 0 && (activity.cards ?? []).length === 0 && (
                                        <p className="text-sm text-slate-500">Sin preguntas ni tarjetas todavía.</p>
                                    )}
                                </CardContent>
                            )}
                        </Card>
                    ))}
                </div>
            </div>

            <Modal
                open={createOpen}
                onClose={() => setCreateOpen(false)}
                title="Nueva actividad"
                footer={
                    <>
                        <Button variant="outline" onClick={() => setCreateOpen(false)}>Cancelar</Button>
                        <Button onClick={submitCreate}>Crear</Button>
                    </>
                }
            >
                <form onSubmit={submitCreate} className="space-y-4">
                    <div>
                        <Label>Título</Label>
                        <Input value={createForm.data.title} onChange={(e) => createForm.setData('title', e.target.value)} />
                        {createForm.errors.title && <p className="mt-1 text-xs text-rose-600">{createForm.errors.title}</p>}
                    </div>
                    <div>
                        <Label>Tipo</Label>
                        <Select value={createForm.data.type} onChange={(e) => createForm.setData('type', e.target.value)}>
                            {types.map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </Select>
                    </div>
                    <div>
                        <Label>Instrucciones</Label>
                        <Textarea value={createForm.data.instructions ?? ''} onChange={(e) => createForm.setData('instructions', e.target.value)} />
                    </div>
                    <div>
                        <Label>Módulo</Label>
                        <Select value={createForm.data.module_id} onChange={(e) => createForm.setData('module_id', e.target.value)}>
                            <option value="">Sin módulo</option>
                            {modules.map((module) => (
                                <option key={module.id} value={module.id}>{module.title}</option>
                            ))}
                        </Select>
                    </div>
                    <button type="submit" className="hidden" />
                </form>
            </Modal>

            <Modal
                open={question !== null}
                onClose={() => setQuestion(null)}
                title="Editar pregunta"
                footer={
                    <>
                        <Button variant="outline" onClick={() => setQuestion(null)}>Cancelar</Button>
                        <Button onClick={saveQuestion}>Guardar</Button>
                    </>
                }
            >
                <form onSubmit={saveQuestion} className="space-y-4">
                    <div>
                        <Label>Enunciado</Label>
                        <Textarea value={questionForm.data.prompt} onChange={(e) => questionForm.setData('prompt', e.target.value)} />
                    </div>
                    <div>
                        <Label>Opciones (una por línea)</Label>
                        <Textarea className="min-h-[120px]" value={questionForm.data.options} onChange={(e) => questionForm.setData('options', e.target.value)} />
                    </div>
                    <div>
                        <Label>Respuesta correcta (debe coincidir con una opción)</Label>
                        <Input value={questionForm.data.correct_answer} onChange={(e) => questionForm.setData('correct_answer', e.target.value)} />
                    </div>
                    <div>
                        <Label>Explicación</Label>
                        <Textarea value={questionForm.data.explanation ?? ''} onChange={(e) => questionForm.setData('explanation', e.target.value)} />
                    </div>
                    <button type="submit" className="hidden" />
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
