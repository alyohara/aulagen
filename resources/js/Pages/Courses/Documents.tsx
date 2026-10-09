import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Alert, Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input, Label, Select, Textarea } from '@/Components/ui/form';
import { CourseSummary, DocumentRow } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { confirmLocalized } from '@/lib/i18n';

interface Props {
    course: CourseSummary;
    documents: DocumentRow[];
    modules: { id: number; title: string; type: string }[];
}

const statusTone: Record<string, 'success' | 'warning' | 'info' | 'danger' | 'muted'> = {
    ready: 'success',
    pending: 'warning',
    processing: 'info',
    failed: 'danger',
    skipped: 'muted',
};

export default function Documents({ course, documents, modules }: Props) {
    const [tab, setTab] = useState<'files' | 'link' | 'text'>('files');

    const fileForm = useForm({ files: [] as File[], module_id: '' });
    const linkForm = useForm({ link_url: '', link_title: '', link_type: 'link', module_id: '' });
    const textForm = useForm({ text_title: '', text_content: '', module_id: '' });

    const uploadFiles = (event: FormEvent) => {
        event.preventDefault();
        fileForm.post(route('courses.documents.store', course.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => fileForm.reset(),
        });
    };

    const uploadLink = (event: FormEvent) => {
        event.preventDefault();
        linkForm.post(route('courses.documents.store', course.id), {
            preserveScroll: true,
            onSuccess: () => linkForm.reset(),
        });
    };

    const uploadText = (event: FormEvent) => {
        event.preventDefault();
        textForm.post(route('courses.documents.store', course.id), {
            preserveScroll: true,
            onSuccess: () => textForm.reset(),
        });
    };

    const assignModule = (documentId: number, moduleId: string) => {
        router.put(route('courses.documents.assign', [course.id, documentId]), { module_id: moduleId || null }, { preserveScroll: true, preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Material de la materia</h1>
                        <p className="text-sm text-slate-500">PDF, DOCX, PPTX, XLSX, TXT, Markdown, imágenes, enlaces o texto pegado.</p>
                    </div>
                    <Link href={route('courses.overview', course.id)}>
                        <Button variant="outline" size="sm">Volver</Button>
                    </Link>
                </div>
            }
        >
            <Head title={`Materiales · ${course.name}`} />

            <div className="mx-auto max-w-5xl space-y-6 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Cargar material</CardTitle>
                        <CardDescription>El procesamiento (extracción, fragmentación y vectores) corre en segundo plano.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4 flex gap-2">
                            {([['files', 'Archivos'], ['link', 'Enlace o video'], ['text', 'Texto pegado']] as const).map(([key, label]) => (
                                <Button key={key} size="sm" variant={tab === key ? 'default' : 'outline'} onClick={() => setTab(key)}>
                                    {label}
                                </Button>
                            ))}
                        </div>

                        {tab === 'files' && (
                            <form onSubmit={uploadFiles} className="space-y-3">
                                <input
                                    type="file"
                                    multiple
                                    accept=".pdf,.docx,.pptx,.xlsx,.txt,.md,.markdown,.csv,.png,.jpg,.jpeg,.webp,.gif"
                                    onChange={(e) => fileForm.setData('files', Array.from(e.target.files ?? []))}
                                    className="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                                <Select value={fileForm.data.module_id} onChange={(e) => fileForm.setData('module_id', e.target.value)}>
                                    <option value="">Sin módulo asignado</option>
                                    {modules.map((module) => (
                                        <option key={module.id} value={module.id}>{module.title}</option>
                                    ))}
                                </Select>
                                {fileForm.errors.files && <Alert tone="danger">{fileForm.errors.files}</Alert>}
                                <Button type="submit" disabled={fileForm.processing || fileForm.data.files.length === 0}>Subir {fileForm.data.files.length > 0 ? `(${fileForm.data.files.length})` : ''}</Button>
                            </form>
                        )}

                        {tab === 'link' && (
                            <form onSubmit={uploadLink} className="space-y-3">
                                <div>
                                    <Label>URL</Label>
                                    <Input value={linkForm.data.link_url} onChange={(e) => linkForm.setData('link_url', e.target.value)} placeholder="https://…" />
                                    {linkForm.errors.link_url && <p className="mt-1 text-xs text-rose-600">{linkForm.errors.link_url}</p>}
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <Label>Título</Label>
                                        <Input value={linkForm.data.link_title} onChange={(e) => linkForm.setData('link_title', e.target.value)} />
                                    </div>
                                    <div>
                                        <Label>Tipo</Label>
                                        <Select value={linkForm.data.link_type} onChange={(e) => linkForm.setData('link_type', e.target.value)}>
                                            <option value="link">Enlace</option>
                                            <option value="video">Video</option>
                                        </Select>
                                    </div>
                                </div>
                                <Select value={linkForm.data.module_id} onChange={(e) => linkForm.setData('module_id', e.target.value)}>
                                    <option value="">Sin módulo asignado</option>
                                    {modules.map((module) => (
                                        <option key={module.id} value={module.id}>{module.title}</option>
                                    ))}
                                </Select>
                                <Button type="submit" disabled={linkForm.processing}>Agregar enlace</Button>
                            </form>
                        )}

                        {tab === 'text' && (
                            <form onSubmit={uploadText} className="space-y-3">
                                <div>
                                    <Label>Título</Label>
                                    <Input value={textForm.data.text_title} onChange={(e) => textForm.setData('text_title', e.target.value)} placeholder="Apuntes de clase" />
                                </div>
                                <div>
                                    <Label>Contenido</Label>
                                    <Textarea className="min-h-[180px]" value={textForm.data.text_content} onChange={(e) => textForm.setData('text_content', e.target.value)} />
                                </div>
                                <Select value={textForm.data.module_id} onChange={(e) => textForm.setData('module_id', e.target.value)}>
                                    <option value="">Sin módulo asignado</option>
                                    {modules.map((module) => (
                                        <option key={module.id} value={module.id}>{module.title}</option>
                                    ))}
                                </Select>
                                <Button type="submit" disabled={textForm.processing || !textForm.data.text_content}>Guardar texto</Button>
                            </form>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Materiales ({documents.length})</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {documents.length === 0 && <p className="text-sm text-slate-500">Todavía no cargaste material.</p>}
                        {documents.map((doc) => (
                            <div key={doc.id} className="flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 px-3 py-2">
                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-sm font-medium text-slate-800">{doc.name}</div>
                                    <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <Badge tone={statusTone[doc.status] ?? 'muted'}>{doc.status_label}</Badge>
                                        {doc.chunk_count != null && <span>{doc.chunk_count} fragmentos</span>}
                                        {doc.page_count != null && <span>· {doc.page_count} págs.</span>}
                                        {doc.created_at && <span>· {doc.created_at}</span>}
                                        {doc.source_url && <span>· {doc.source_url}</span>}
                                    </div>
                                    {doc.error && <p className="mt-1 text-xs text-rose-600">{doc.error}</p>}
                                </div>
                                <Select className="h-8 w-40 text-xs" value={doc.module?.id ?? ''} onChange={(e) => assignModule(doc.id, e.target.value)}>
                                    <option value="">Sin módulo</option>
                                    {modules.map((module) => (
                                        <option key={module.id} value={module.id}>{module.title}</option>
                                    ))}
                                </Select>
                                <Button size="sm" variant="outline" onClick={() => router.post(route('courses.documents.reprocess', [course.id, doc.id]), {}, { preserveScroll: true })}>
                                    Reprocesar
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => {
                                        if (confirmLocalized(`¿Eliminar "${doc.name}"?`)) {
                                            router.delete(route('courses.documents.destroy', [course.id, doc.id]), { preserveScroll: true });
                                        }
                                    }}
                                >
                                    ✕
                                </Button>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
