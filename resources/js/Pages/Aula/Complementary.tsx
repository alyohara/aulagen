import AulaLayout from '@/Layouts/AulaLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { AulaShared, DocumentRow } from '@/types/models';
import { Head } from '@inertiajs/react';

interface Props extends AulaShared {
    groups: { title: string; documents: DocumentRow[] }[];
    progress: number[];
}

export default function AulaComplementary({ course, navigation, settings, isPreview, previewUrl, groups, progress }: Props) {
    return (
        <AulaLayout course={course} navigation={navigation} settings={settings} isPreview={isPreview} previewUrl={previewUrl} active="complementary" progress={progress}>
            <Head title={`Material complementario · ${course.name}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Material complementario</h1>
                    <p className="mt-1 text-slate-600">Archivos, enlaces y videos cargados por el equipo docente.</p>
                </div>

                {groups.length === 0 && (
                    <Card>
                        <CardContent className="p-5 text-sm text-slate-500">No hay material complementario disponible.</CardContent>
                    </Card>
                )}

                {groups.map((group) => (
                    <Card key={group.title}>
                        <CardHeader>
                            <CardTitle>{group.title}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {group.documents.map((doc) => (
                                <a
                                    key={doc.id}
                                    href={doc.url}
                                    target={doc.external ? '_blank' : undefined}
                                    rel="noreferrer"
                                    className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                >
                                    <span>{doc.external ? '🔗 ' : '📄 '}{doc.name}</span>
                                    <span className="text-xs uppercase text-slate-400">{doc.type} {doc.size ? `· ${doc.size}` : ''}</span>
                                </a>
                            ))}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AulaLayout>
    );
}
