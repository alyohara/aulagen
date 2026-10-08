import AulaLayout from '@/Layouts/AulaLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { AulaShared } from '@/types/models';
import { Head } from '@inertiajs/react';

interface Entry {
    id: number;
    kind: string;
    authors?: string | null;
    title: string;
    edition?: string | null;
    publisher?: string | null;
    year?: string | null;
    url?: string | null;
    note?: string | null;
    formatted?: string;
}

interface Props extends AulaShared {
    principal: Entry[];
    complementary: Entry[];
    rawPrincipal?: string | null;
    rawComplementary?: string | null;
    progress: number[];
}

const rawLines = (value?: string | null) => (value ?? '').split('\n').map((l) => l.trim()).filter(Boolean);

export default function AulaBibliography({ course, navigation, settings, isPreview, previewUrl, principal, complementary, rawPrincipal, rawComplementary, progress }: Props) {
    const renderList = (entries: Entry[], raw?: string | null) => (
        <div className="space-y-2">
            {entries.map((entry) => (
                <div key={entry.id} className="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                    {entry.formatted ?? `${entry.authors ?? ''} (${entry.year ?? 's.f.'}). ${entry.title}. ${entry.publisher ?? ''}`}
                    {entry.edition && !entry.formatted && ` (${entry.edition})`}
                    {entry.url && (
                        <>
                            {' '}
                            <a href={entry.url} target="_blank" rel="noreferrer" className="text-indigo-600 underline">Disponible en línea</a>
                        </>
                    )}
                    {entry.note && <p className="mt-1 text-xs text-slate-500">{entry.note}</p>}
                </div>
            ))}
            {entries.length === 0 &&
                rawLines(raw).map((line, index) => (
                    <div key={index} className="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">{line}</div>
                ))}
            {entries.length === 0 && rawLines(raw).length === 0 && <p className="text-sm text-slate-500">Sin referencias cargadas.</p>}
        </div>
    );

    return (
        <AulaLayout course={course} navigation={navigation} settings={settings} isPreview={isPreview} previewUrl={previewUrl} active="bibliography" progress={progress}>
            <Head title={`Bibliografía · ${course.name}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Bibliografía</h1>
                    <p className="mt-1 text-slate-600">Material de consulta para seguir la cursada.</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Principal</CardTitle>
                        <CardDescription>Obras de consulta obligatoria.</CardDescription>
                    </CardHeader>
                    <CardContent>{renderList(principal, rawPrincipal)}</CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Complementaria</CardTitle>
                        <CardDescription>Material optativo, artículos y enlaces.</CardDescription>
                    </CardHeader>
                    <CardContent>{renderList(complementary, rawComplementary)}</CardContent>
                </Card>
            </div>
        </AulaLayout>
    );
}
