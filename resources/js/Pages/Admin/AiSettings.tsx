import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input, Label, Select } from '@/Components/ui/form';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface ProviderFields {
    url?: string;
    key?: string | null;
    key_masked?: string | null;
    model?: string;
    embedding_model?: string;
    label?: string;
}

interface FormData {
    provider: string;
    auto_primary: string;
    fallback: string;
    enabled: boolean;
    rate_limit: number;
    rag_top_k: number;
    embedding_dim: number;
    providers: Record<string, ProviderFields>;
}

interface Props {
    form: FormData;
    provider: { active: string; label: string; model?: string | null; configured: string; rate_limit: number };
}

const PROVIDER_OPTIONS = [
    { value: 'auto', label: 'Automático (Ollama con respaldo local)' },
    { value: 'ollama', label: 'Ollama (IA local, gratis)' },
    { value: 'gemini', label: 'Google Gemini' },
    { value: 'openai', label: 'OpenAI' },
    { value: 'custom', label: 'Otro (compatible con OpenAI)' },
    { value: 'local', label: 'Local heurístico (sin modelos)' },
];

const CUSTOM_HINTS = 'NotebookLM, OpenRouter, Groq, Mistral, LM Studio, etc.: usá el endpoint compatible con OpenAI (base URL + clave). NotebookLM no expone API pública; requeriría un proxy compatible.';

export default function AdminAiSettings({ form, provider }: Props) {
    const { data, setData, put, processing } = useForm<FormData>(form);
    const [testResult, setTestResult] = useState<{ ok: boolean; message: string } | null>(null);
    const [testing, setTesting] = useState(false);

    const save = () => put(route('admin.ai.update'), { preserveScroll: true });

    const setProviderField = (name: string, field: string, value: string) => {
        setData('providers', { ...data.providers, [name]: { ...data.providers[name], [field]: value } });
    };

    const testConnection = async (name: string) => {
        setTesting(true);
        setTestResult(null);

        try {
            const fields = data.providers[name] ?? {};
            const config: Record<string, string> = {};
            for (const [k, v] of Object.entries(fields)) {
                if (k === 'key_masked') continue;
                if (v !== null && v !== undefined) config[k] = String(v);
            }

            const res = await fetch(route('admin.ai.test'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        (document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/) ?? [])[1] ?? '',
                    ),
                },
                body: JSON.stringify({ provider: name, config }),
            });

            const json = await res.json();
            setTestResult(
                json.ok
                    ? { ok: true, message: `Conectado con ${json.label} (${json.model}) en ${json.duration_ms} ms. Respuesta: "${json.reply}"` }
                    : { ok: false, message: json.message ?? 'Error desconocido.' },
            );
        } catch {
            setTestResult({ ok: false, message: 'No se pudo contactar al servidor.' });
        } finally {
            setTesting(false);
        }
    };

    const active = data.provider === 'auto' ? data.auto_primary : data.provider;

    const renderProviderCard = (name: string, title: string, description: string) => {
        const fields = data.providers[name] ?? {};

        return (
            <Card key={name} className={active === name ? 'border-indigo-300' : ''}>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div>
                            <CardTitle>{title}</CardTitle>
                            <CardDescription>{description}</CardDescription>
                        </div>
                        {active === name && <Badge tone="info">Activo</Badge>}
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    {name === 'custom' && (
                        <div>
                            <Label>Nombre del proveedor</Label>
                            <Input value={fields.label ?? ''} onChange={(e) => setProviderField(name, 'label', e.target.value)} placeholder="Mi proveedor" />
                            <p className="mt-1 text-xs text-slate-500">{CUSTOM_HINTS}</p>
                        </div>
                    )}

                    {(name === 'ollama' || name === 'openai' || name === 'custom') && (
                        <div>
                            <Label>URL base</Label>
                            <Input value={fields.url ?? ''} onChange={(e) => setProviderField(name, 'url', e.target.value)} placeholder={name === 'ollama' ? 'http://ollama:11434' : 'https://api.example.com/v1'} />
                        </div>
                    )}

                    {name !== 'ollama' && (
                        <div>
                            <Label>API key</Label>
                            <Input
                                type="password"
                                value={fields.key ?? ''}
                                onChange={(e) => setProviderField(name, 'key', e.target.value)}
                                placeholder={fields.key_masked ? `Guardada (${fields.key_masked}) — dejá en blanco para mantener` : 'sk-...'}
                            />
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <Label>Modelo</Label>
                            <Input value={fields.model ?? ''} onChange={(e) => setProviderField(name, 'model', e.target.value)} placeholder={name === 'ollama' ? 'llama3.2' : name === 'gemini' ? 'gemini-2.0-flash' : 'gpt-4o-mini'} />
                        </div>
                        <div>
                            <Label>Modelo de embeddings</Label>
                            <Input value={fields.embedding_model ?? ''} onChange={(e) => setProviderField(name, 'embedding_model', e.target.value)} placeholder={name === 'ollama' ? 'nomic-embed-text' : 'text-embedding-3-small'} />
                        </div>
                    </div>

                    {name !== 'local' && (
                        <Button variant="outline" size="sm" onClick={() => testConnection(name)} disabled={testing}>
                            {testing ? 'Probando…' : 'Probar conexión'}
                        </Button>
                    )}
                </CardContent>
            </Card>
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Configuración de IA</h1>
                        <p className="text-sm text-slate-500">Proveedor activo: {provider.label} · modelo {provider.model ?? '—'}</p>
                    </div>
                    <Button variant="outline" size="sm" onClick={() => window.history.back()}>Volver</Button>
                </div>
            }
        >
            <Head title="Configuración de IA" />

            <div className="mx-auto max-w-4xl space-y-6 py-8 sm:px-6 lg:px-8">
                {testResult && (
                    <div className={`rounded-lg border px-4 py-3 text-sm ${testResult.ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'}`}>
                        {testResult.message}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>General</CardTitle>
                        <CardDescription>Selección del proveedor y parámetros globales.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <Label>Proveedor principal</Label>
                                <Select value={data.provider} onChange={(e) => setData('provider', e.target.value)}>
                                    {PROVIDER_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                </Select>
                            </div>
                            {data.provider === 'auto' && (
                                <>
                                    <div>
                                        <Label>Intentar primero</Label>
                                        <Select value={data.auto_primary} onChange={(e) => setData('auto_primary', e.target.value)}>
                                            {PROVIDER_OPTIONS.filter((o) => o.value !== 'auto' && o.value !== 'local').map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                        </Select>
                                    </div>
                                    <div>
                                        <Label>Respaldo</Label>
                                        <Select value={data.fallback} onChange={(e) => setData('fallback', e.target.value)}>
                                            {PROVIDER_OPTIONS.filter((o) => o.value !== 'auto').map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                        </Select>
                                    </div>
                                </>
                            )}
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <Label>Consultas IA por minuto</Label>
                                <Input type="number" min={1} max={120} value={data.rate_limit} onChange={(e) => setData('rate_limit', Number(e.target.value))} />
                            </div>
                            <div>
                                <Label>Fragmentos RAG (top K)</Label>
                                <Input type="number" min={1} max={30} value={data.rag_top_k} onChange={(e) => setData('rag_top_k', Number(e.target.value))} />
                            </div>
                            <div>
                                <Label>Dimensión de embeddings</Label>
                                <Input type="number" min={128} max={4096} value={data.embedding_dim} onChange={(e) => setData('embedding_dim', Number(e.target.value))} />
                            </div>
                        </div>

                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" checked={data.enabled} onChange={(e) => setData('enabled', e.target.checked)} className="h-4 w-4 rounded border-slate-300" />
                            Generación con IA habilitada
                        </label>
                    </CardContent>
                </Card>

                {renderProviderCard('ollama', 'Ollama (local)', 'Modelos corriendo en tu servidor, sin costo ni límites.')}
                {renderProviderCard('gemini', 'Google Gemini', 'API de Google con free tier.')}
                {renderProviderCard('openai', 'OpenAI', 'API oficial de OpenAI.')}
                {renderProviderCard('custom', 'Otro proveedor compatible', 'Cualquier endpoint que hable el protocolo de OpenAI.')}

                <div className="flex justify-end">
                    <Button onClick={save} disabled={processing}>
                        {processing ? 'Guardando…' : 'Guardar configuración'}
                    </Button>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
