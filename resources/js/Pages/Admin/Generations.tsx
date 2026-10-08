import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { GenerationRow } from '@/types/models';
import { Head } from '@inertiajs/react';

interface Props {
    generations: GenerationRow[];
    provider: { active: string; label: string; model?: string | null };
}

export default function AdminGenerations({ generations, provider }: Props) {
    const tone = (status: string) => (status === 'ok' || status === 'success' ? 'success' : status === 'error' ? 'danger' : 'warning');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Generaciones de IA</h1>
                        <p className="text-sm text-slate-500">Últimas 100 ejecuciones · proveedor activo: {provider.label}</p>
                    </div>
                    <Button variant="outline" size="sm" onClick={() => window.history.back()}>Volver</Button>
                </div>
            }
        >
            <Head title="Generaciones IA" />

            <div className="mx-auto max-w-6xl py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Registro</CardTitle>
                        <CardDescription>Todo lo que la IA generó en la plataforma, con duración y errores.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {generations.length === 0 ? (
                            <p className="text-sm text-slate-500">Sin generaciones registradas.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-200 text-left text-xs uppercase text-slate-400">
                                            <th className="py-2">Fecha</th>
                                            <th className="py-2">Materia</th>
                                            <th className="py-2">Tipo</th>
                                            <th className="py-2">Usuario</th>
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
                                                <td className="py-2 text-slate-700">{row.course ?? '—'}</td>
                                                <td className="py-2 text-slate-700">{row.type_label ?? row.type}</td>
                                                <td className="py-2 text-slate-500">{row.user ?? '—'}</td>
                                                <td className="py-2 text-slate-500">{row.provider}</td>
                                                <td className="py-2"><Badge tone={tone(row.status)}>{row.status}</Badge></td>
                                                <td className="py-2 text-slate-500">{row.duration_ms ?? '—'}</td>
                                                <td className="max-w-[220px] truncate py-2 text-xs text-rose-600">{row.error ?? ''}</td>
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
