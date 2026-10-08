import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Head, Link } from '@inertiajs/react';

interface Props {
    stats: Record<string, number>;
    provider: { active: string; label: string; model?: string | null; configured: string; rate_limit: number; embedding_dim: number };
    errors: { source: string; subject: string; error?: string | null; created_at?: string }[];
}

export default function AdminDashboard({ stats, provider, errors }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Administración</h1>
                        <p className="text-sm text-slate-500">Estado general de la plataforma.</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('admin.users')}>
                            <Button variant="outline" size="sm">Usuarios</Button>
                        </Link>
                        <Link href={route('admin.generations')}>
                            <Button variant="outline" size="sm">Generaciones IA</Button>
                        </Link>
                        <Link href={route('admin.ai.index')}>
                            <Button variant="outline" size="sm">Configurar IA</Button>
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Admin" />

            <div className="mx-auto max-w-7xl space-y-6 py-8 sm:px-6 lg:px-8">
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {[
                        ['Usuarios', stats.users],
                        ['Docentes', stats.teachers],
                        ['Materias', stats.courses],
                        ['Publicadas', stats.published],
                        ['Materiales', stats.documents],
                        ['Docs. fallidos', stats.documents_failed],
                        ['Generaciones IA', stats.generations],
                        ['Errores IA', stats.generations_errors],
                    ].map(([label, value]) => (
                        <Card key={label as string}>
                            <CardContent className="p-4">
                                <div className="text-2xl font-semibold text-slate-900">{(value as number) ?? 0}</div>
                                <div className="text-sm text-slate-500">{label}</div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Proveedor de IA</CardTitle>
                        <CardDescription>Configuración efectiva de generación y embeddings.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-1 text-sm text-slate-600">
                        <div className="flex justify-between"><span>Activo</span><strong>{provider.label}</strong></div>
                        <div className="flex justify-between"><span>Modelo</span><strong>{provider.model ?? '—'}</strong></div>
                        <div className="flex justify-between"><span>Configurado en .env</span><strong>{provider.configured}</strong></div>
                        <div className="flex justify-between"><span>Rate limit (IA/min)</span><strong>{provider.rate_limit}</strong></div>
                        <div className="flex justify-between"><span>Dimensión de embeddings</span><strong>{provider.embedding_dim}</strong></div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Errores recientes</CardTitle>
                        <CardDescription>Materiales fallidos y generaciones con error.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {errors.length === 0 && <p className="text-sm text-slate-500">Sin errores registrados.</p>}
                        {errors.map((item, index) => (
                            <div key={index} className="flex items-start justify-between gap-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-sm">
                                <div className="min-w-0">
                                    <Badge tone="danger">{item.source}</Badge>
                                    <span className="ml-2 font-medium text-slate-800">{item.subject}</span>
                                    <p className="mt-1 truncate text-xs text-rose-700">{item.error}</p>
                                </div>
                                <span className="shrink-0 text-xs text-slate-400">{item.created_at}</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
