import AulaLayout from '@/Layouts/AulaLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { AulaShared } from '@/types/models';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface Term {
    term: string;
    definition: string;
}

interface Props extends AulaShared {
    terms: Term[];
    progress: number[];
}

export default function AulaGlossary({ course, navigation, settings, isPreview, previewUrl, terms, progress }: Props) {
    const [query, setQuery] = useState('');

    const filtered = useMemo(() => {
        const needle = query.trim().toLowerCase();

        return terms.filter((item) => !needle || item.term.toLowerCase().includes(needle) || item.definition.toLowerCase().includes(needle));
    }, [terms, query]);

    return (
        <AulaLayout course={course} navigation={navigation} settings={settings} isPreview={isPreview} previewUrl={previewUrl} active="glossary" progress={progress}>
            <Head title={`Glosario · ${course.name}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Glosario</h1>
                    <p className="mt-1 text-slate-600">Definiciones de los términos clave de la materia.</p>
                </div>

                <input
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder="Buscar término o definición…"
                    className="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                />

                {filtered.length === 0 ? (
                    <Card>
                        <CardContent className="p-5 text-sm text-slate-500">Sin resultados para «{query}».</CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3 md:grid-cols-2">
                        {filtered.map((item, index) => (
                            <Card key={index}>
                                <CardContent className="p-4">
                                    <p className="font-semibold text-indigo-700">{item.term}</p>
                                    <p className="mt-1 text-sm text-slate-600">{item.definition}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AulaLayout>
    );
}
