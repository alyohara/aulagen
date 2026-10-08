import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Alert, Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/form';
import { GenerationRow, CourseSummary } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface GlossaryTerm {
    term: string;
    definition: string;
}

interface Props {
    course: CourseSummary & { has_proposal: boolean };
    provider: { name: string; label: string; model?: string | null };
    providers: { name: string; label: string; available: boolean; active: boolean }[];
    generations: GenerationRow[];
    glossary: GlossaryTerm[];
    hasProposal: boolean;
}

export default function Ai({ course, provider, providers, generations, glossary, hasProposal }: Props) {
    const [terms, setTerms] = useState<GlossaryTerm[]>(glossary);
    const [saving, setSaving] = useState(false);

    const saveGlossary = () => {
        setSaving(true);
        router.put(
            route('courses.ai.glossary.update', course.id),
            { glossary: terms.filter((t) => t.term.trim() !== '').map((t) => ({ term: t.term, definition: t.definition }) as Record<string, string>) },
            { preserveScroll: true, onFinish: () => setSaving(false) },
        );
    };

    const statusTone = (status: string) => (status === 'ok' || status === 'success' ? 'success' : status === 'error' ? 'danger' : 'warning');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Asistente de IA</h1>
                        <p className="text-sm text-slate-500">Proveedor activo: {provider.label} {provider.model ? `(${provider.model})` : ''}</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('courses.overview', course.id)}>
                            <Button variant="outline" size="sm">Volver</Button>
                        </Link>
                        <Button size="sm" variant="outline" onClick={() => router.post(route('courses.ai.refresh', course.id), {}, { preserveScroll: true })}>
                            Actualizar estado
                        </Button>
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => router.post(route('courses.structure.propose', course.id), {}, { preserveScroll: true })}
                        >
                            Proponer estructura
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`IA · ${course.name}`} />

            <div className="mx-auto max-w-5xl space-y-6 py-8 sm:px-6 lg:px-8">
                {hasProposal && (
                    <Alert tone="info">
                        Hay una estructura propuesta pendiente. <Link className="font-semibold underline" href={route('courses.content', course.id)}>Revisarla en Contenido</Link>.
                    </Alert>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Proveedores configurados</CardTitle>
                            <CardDescription>Si el proveedor principal falla, el sistema cae automáticamente al siguiente disponible.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {providers.map((item) => (
                                <div key={item.name} className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    <span className="font-medium text-slate-700">{item.label}</span>
                                    <span className="flex items-center gap-2">
                                        {item.active && <Badge tone="success">Activo</Badge>}
                                        <Badge tone={item.available ? 'success' : 'muted'}>{item.available ? 'disponible' : 'no disponible'}</Badge>
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Flujos de generación</CardTitle>
                            <CardDescription>La IA nunca publica: todo queda en borrador hasta que lo aprobás.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            <Link href={route('courses.content', course.id)}>
                                <Button variant="outline" size="sm" className="w-full">Estructura y lecciones</Button>
                            </Link>
                            <Link href={route('courses.activities.index', course.id)}>
                                <Button variant="outline" size="sm" className="w-full">Actividades y evaluaciones</Button>
                            </Link>
                            <Button
                                variant="outline"
                                size="sm"
                                className="w-full"
                                onClick={() => router.post(route('courses.ai.glossary', course.id), {}, { preserveScroll: true })}
                            >
                                Generar glosario con IA
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Glosario del aula</CardTitle>
                        <CardDescription>Se muestra en la pestaña «Glosario» del aula. Editá manualmente o generá con IA.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {terms.length === 0 && <p className="text-sm text-slate-500">Sin términos todavía.</p>}
                        {terms.map((item, index) => (
                            <div key={index} className="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                                <Input
                                    value={item.term}
                                    onChange={(e) => setTerms((prev) => prev.map((t, i) => (i === index ? { ...t, term: e.target.value } : t)))}
                                    placeholder="Término"
                                />
                                <Input
                                    value={item.definition}
                                    onChange={(e) => setTerms((prev) => prev.map((t, i) => (i === index ? { ...t, definition: e.target.value } : t)))}
                                    placeholder="Definición"
                                />
                                <Button size="sm" variant="ghost" onClick={() => setTerms((prev) => prev.filter((_, i) => i !== index))}>✕</Button>
                            </div>
                        ))}
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline" onClick={() => setTerms((prev) => [...prev, { term: '', definition: '' }])}>+ Término</Button>
                            <Button size="sm" onClick={saveGlossary} disabled={saving}>{saving ? 'Guardando…' : 'Guardar glosario'}</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Registro de generaciones</CardTitle>
                        <CardDescription>Últimas 60 ejecuciones de IA de esta materia.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {generations.length === 0 ? (
                            <p className="text-sm text-slate-500">Todavía no se ejecutó ninguna generación con IA.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-200 text-left text-xs uppercase text-slate-400">
                                            <th className="py-2">Fecha</th>
                                            <th className="py-2">Tipo</th>
                                            <th className="py-2">Proveedor</th>
                                            <th className="py-2">Estado</th>
                                            <th className="py-2">ms</th>
                                            <th className="py-2">Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {generations.map((row) => (
                                            <tr key={row.id} className="border-b border-slate-100">
                                                <td className="py-2 text-slate-500">{row.created_at}</td>
                                                <td className="py-2 text-slate-700">{row.type_label ?? row.type}</td>
                                                <td className="py-2 text-slate-500">{row.provider}</td>
                                                <td className="py-2"><Badge tone={statusTone(row.status)}>{row.status}</Badge></td>
                                                <td className="py-2 text-slate-500">{row.duration_ms ?? '—'}</td>
                                                <td className="max-w-[240px] truncate py-2 text-xs text-rose-600">{row.error ?? ''}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
