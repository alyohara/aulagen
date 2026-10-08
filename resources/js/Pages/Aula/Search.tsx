import AulaLayout from '@/Layouts/AulaLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { AulaShared } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Result {
    type: string;
    title: string;
    subtitle?: string | null;
    snippet: string;
    url: string;
    score?: number;
}

interface Props extends AulaShared {
    query: string;
    results: Result[];
    progress: number[];
}

const typeLabels: Record<string, string> = {
    lesson: 'Lección',
    module: 'Módulo',
    document: 'Documento',
    material: 'Material',
    chunk: 'Fragmento',
};

export default function AulaSearch({ course, navigation, settings, isPreview, previewUrl, query, results, progress }: Props) {
    const [value, setValue] = useState(query);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('aula.search', course.slug), { q: value }, { preserveState: true });
    };

    return (
        <AulaLayout course={course} navigation={navigation} settings={settings} isPreview={isPreview} previewUrl={previewUrl} active="search" progress={progress}>
            <Head title={`Buscar · ${course.name}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Buscar en el aula</h1>
                    <p className="mt-1 text-slate-600">Búsqueda semántica sobre las lecciones, módulos y materiales de la materia.</p>
                </div>

                <form onSubmit={submit} className="flex gap-2">
                    <input
                        value={value}
                        onChange={(e) => setValue(e.target.value)}
                        placeholder="Ej: complejidad de búsqueda binaria"
                        className="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    />
                    <button type="submit" className="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                        Buscar
                    </button>
                </form>

                {query && <p className="text-sm text-slate-500">{results.length} resultados para «{query}»</p>}

                <div className="space-y-3">
                    {results.map((result, index) => (
                        <Card key={index}>
                            <CardContent className="p-4">
                                <div className="flex items-center gap-2">
                                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                        {typeLabels[result.type] ?? result.type}
                                    </span>
                                    <Link href={result.url} className="font-semibold text-indigo-700 hover:underline">
                                        {result.title}
                                    </Link>
                                    {result.subtitle && <span className="text-xs text-slate-400">· {result.subtitle}</span>}
                                </div>
                                <p className="mt-1 line-clamp-3 text-sm text-slate-600">{result.snippet}</p>
                            </CardContent>
                        </Card>
                    ))}

                    {query && results.length === 0 && (
                        <Card>
                            <CardContent className="p-5 text-sm text-slate-500">
                                Sin resultados. Probá con otras palabras: el buscador también entiende sinónimos del contenido.
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AulaLayout>
    );
}
