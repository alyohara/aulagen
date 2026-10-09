import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input, Label, Select, Textarea } from '@/Components/ui/form';
import { Modal } from '@/Components/ui/modal';
import { BibliographyRow, CourseSummary } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { confirmLocalized } from '@/lib/i18n';

interface Props {
    course: CourseSummary;
    entries: BibliographyRow[];
}

export default function Bibliography({ course, entries }: Props) {
    const [editing, setEditing] = useState<BibliographyRow | null>(null);
    const [creating, setCreating] = useState(false);

    const form = useForm({
        kind: 'principal',
        authors: '',
        title: '',
        edition: '',
        publisher: '',
        year: '',
        url: '',
        note: '',
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setCreating(true);
    };

    const openEdit = (entry: BibliographyRow) => {
        setCreating(false);
        setEditing(entry);
        form.setData({
            kind: entry.kind,
            authors: entry.authors ?? '',
            title: entry.title,
            edition: entry.edition ?? '',
            publisher: entry.publisher ?? '',
            year: entry.year ?? '',
            url: entry.url ?? '',
            note: entry.note ?? '',
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (editing) {
            form.put(route('courses.bibliography.update', [course.id, editing.id]), {
                preserveScroll: true,
                onSuccess: () => { setEditing(null); form.reset(); },
            });
        } else {
            form.post(route('courses.bibliography.store', course.id), {
                preserveScroll: true,
                onSuccess: () => { setCreating(false); form.reset(); },
            });
        }
    };

    const principal = entries.filter((entry) => entry.kind === 'principal');
    const complementary = entries.filter((entry) => entry.kind !== 'principal');

    const renderList = (list: BibliographyRow[]) => (
        <div className="space-y-2">
            {list.length === 0 && <p className="text-sm text-slate-500">Sin referencias.</p>}
            {list.map((entry) => (
                <div key={entry.id} className="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                    <div className="min-w-0 flex-1 text-sm text-slate-700">
                        {entry.formatted ?? `${entry.authors ?? ''} (${entry.year ?? 's.f.'}). ${entry.title}. ${entry.publisher ?? ''}`}
                        {entry.url && (
                            <>
                                {' '}
                                <a href={entry.url} target="_blank" rel="noreferrer" className="text-indigo-600 underline">{entry.url}</a>
                            </>
                        )}
                    </div>
                    <Button size="sm" variant="outline" onClick={() => openEdit(entry)}>Editar</Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => {
                            if (confirmLocalized('¿Eliminar esta referencia?')) {
                                router.delete(route('courses.bibliography.destroy', [course.id, entry.id]), { preserveScroll: true });
                            }
                        }}
                    >
                        ✕
                    </Button>
                </div>
            ))}
        </div>
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Bibliografía</h1>
                        <p className="text-sm text-slate-500">Referencias que ven los alumnos en el aula.</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('courses.overview', course.id)}>
                            <Button variant="outline" size="sm">Volver</Button>
                        </Link>
                        <Button size="sm" onClick={openCreate}>+ Referencia</Button>
                    </div>
                </div>
            }
        >
            <Head title={`Bibliografía · ${course.name}`} />

            <div className="mx-auto max-w-4xl space-y-6 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle>Bibliografía principal</CardTitle>
                            <Badge tone="info">{principal.length}</Badge>
                        </div>
                        <CardDescription>Obras de consulta obligatoria.</CardDescription>
                    </CardHeader>
                    <CardContent>{renderList(principal)}</CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle>Bibliografía complementaria</CardTitle>
                            <Badge tone="muted">{complementary.length}</Badge>
                        </div>
                        <CardDescription>Material optativo y enlaces útiles.</CardDescription>
                    </CardHeader>
                    <CardContent>{renderList(complementary)}</CardContent>
                </Card>
            </div>

            <Modal
                open={creating || editing !== null}
                onClose={() => { setCreating(false); setEditing(null); }}
                title={editing ? 'Editar referencia' : 'Nueva referencia'}
                maxWidth="max-w-xl"
                footer={
                    <>
                        <Button variant="outline" onClick={() => { setCreating(false); setEditing(null); }}>Cancelar</Button>
                        <Button onClick={submit}>Guardar</Button>
                    </>
                }
            >
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label>Tipo</Label>
                            <Select value={form.data.kind} onChange={(e) => form.setData('kind', e.target.value)}>
                                <option value="principal">Principal</option>
                                <option value="complementaria">Complementaria</option>
                            </Select>
                        </div>
                        <div>
                            <Label>Año</Label>
                            <Input value={form.data.year ?? ''} onChange={(e) => form.setData('year', e.target.value)} />
                        </div>
                    </div>
                    <div>
                        <Label>Autores</Label>
                        <Input value={form.data.authors ?? ''} onChange={(e) => form.setData('authors', e.target.value)} placeholder="Cormen, T. H.; Leiserson, C. E.; …" />
                    </div>
                    <div>
                        <Label>Título *</Label>
                        <Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                        {form.errors.title && <p className="mt-1 text-xs text-rose-600">{form.errors.title}</p>}
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label>Edición</Label>
                            <Input value={form.data.edition ?? ''} onChange={(e) => form.setData('edition', e.target.value)} placeholder="3.ª ed." />
                        </div>
                        <div>
                            <Label>Editorial</Label>
                            <Input value={form.data.publisher ?? ''} onChange={(e) => form.setData('publisher', e.target.value)} />
                        </div>
                    </div>
                    <div>
                        <Label>URL (opcional)</Label>
                        <Input value={form.data.url ?? ''} onChange={(e) => form.setData('url', e.target.value)} placeholder="https://…" />
                        {form.errors.url && <p className="mt-1 text-xs text-rose-600">{form.errors.url}</p>}
                    </div>
                    <div>
                        <Label>Nota</Label>
                        <Textarea value={form.data.note ?? ''} onChange={(e) => form.setData('note', e.target.value)} />
                    </div>
                    <button type="submit" className="hidden" />
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
