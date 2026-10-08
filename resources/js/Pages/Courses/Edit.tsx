import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Alert } from '@/Components/ui/badge';
import { Select, Textarea } from '@/Components/ui/form';
import { CourseSummary } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    course: CourseSummary & {
        url: string;
        settings: Record<string, boolean> | null;
        bibliography_principal?: string | null;
        bibliography_complementary?: string | null;
    };
}

const settingLabels: Record<string, string> = {
    ai_assistant_enabled: 'Asistente de IA disponible para alumnos',
    enable_search: 'Búsqueda en el contenido del aula',
    show_progress: 'Mostrar progreso de lectura',
    show_sources: 'Mostrar fuentes citadas en las lecciones',
    allow_downloads: 'Permitir descargar materiales',
    allow_external_knowledge: 'Permitir a la IA usar conocimiento externo al material',
    auto_generate_resources: 'Generar recursos automáticamente al aprobar una lección',
};

export default function Edit({ course }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: course.name ?? '',
        description: course.description ?? '',
        institution: course.institution ?? '',
        career: course.career ?? '',
        course_year: course.course_year ?? '',
        duration: course.duration ?? '',
        modality: course.modality ?? '',
        objectives: course.objectives ?? '',
        program: course.program ?? '',
        collaborators: course.collaborators ?? '',
        bibliography_principal: course.bibliography_principal ?? '',
        bibliography_complementary: course.bibliography_complementary ?? '',
    });

    const settingsForm = useForm({ ...(course.settings ?? {}) });
    const [settingsState, setSettingsState] = useState<Record<string, boolean>>({ ...(course.settings ?? {}) });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(route('courses.update', course.id));
    };

    const submitSettings = (event: FormEvent) => {
        event.preventDefault();
        settingsForm.transform((values) => ({ ...values, ...settingsState }));
        settingsForm.put(route('courses.settings', course.id), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-slate-900">Editar: {course.name}</h1>
                    <Link href={route('courses.overview', course.id)}>
                        <Button variant="outline" size="sm">Volver</Button>
                    </Link>
                </div>
            }
        >
            <Head title={`Editar ${course.name}`} />

            <div className="mx-auto max-w-4xl space-y-6 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la materia</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="name" value="Nombre *" />
                                <TextInput id="name" value={data.name} className="mt-1 block w-full" onChange={(e) => setData('name', e.target.value)} />
                                <InputError message={errors.name} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="description" value="Descripción" />
                                <Textarea id="description" value={data.description} className="mt-1" onChange={(e) => setData('description', e.target.value)} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="institution" value="Institución" />
                                    <TextInput id="institution" value={data.institution} className="mt-1 block w-full" onChange={(e) => setData('institution', e.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="career" value="Carrera" />
                                    <TextInput id="career" value={data.career} className="mt-1 block w-full" onChange={(e) => setData('career', e.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="course_year" value="Año / ciclo" />
                                    <TextInput id="course_year" value={data.course_year} className="mt-1 block w-full" onChange={(e) => setData('course_year', e.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="duration" value="Duración" />
                                    <TextInput id="duration" value={data.duration} className="mt-1 block w-full" onChange={(e) => setData('duration', e.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="modality" value="Modalidad" />
                                    <Select id="modality" value={data.modality} className="mt-1" onChange={(e) => setData('modality', e.target.value)}>
                                        <option value="">Seleccionar…</option>
                                        <option>Presencial</option>
                                        <option>Presencial con material digital</option>
                                        <option>Virtual sincrónico</option>
                                        <option>Virtual asincrónico</option>
                                        <option>Híbrida</option>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel htmlFor="collaborators" value="Otros docentes" />
                                    <TextInput id="collaborators" value={data.collaborators} className="mt-1 block w-full" onChange={(e) => setData('collaborators', e.target.value)} />
                                </div>
                            </div>
                            <div>
                                <InputLabel htmlFor="objectives" value="Objetivos (uno por línea)" />
                                <Textarea id="objectives" className="mt-1" value={data.objectives} onChange={(e) => setData('objectives', e.target.value)} />
                            </div>
                            <div>
                                <InputLabel htmlFor="program" value="Programa (uno por línea)" />
                                <Textarea id="program" className="mt-1" value={data.program} onChange={(e) => setData('program', e.target.value)} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="bibliography_principal" value="Bibliografía principal (una por línea)" />
                                    <Textarea id="bibliography_principal" className="mt-1" value={data.bibliography_principal} onChange={(e) => setData('bibliography_principal', e.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="bibliography_complementary" value="Bibliografía complementaria (una por línea)" />
                                    <Textarea id="bibliography_complementary" className="mt-1" value={data.bibliography_complementary} onChange={(e) => setData('bibliography_complementary', e.target.value)} />
                                </div>
                            </div>
                            <Button type="submit" disabled={processing}>Guardar cambios</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Configuración del aula</CardTitle>
                        <CardDescription>Define qué pueden ver y hacer los alumnos.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitSettings} className="space-y-3">
                            {Object.entries(settingLabels).map(([key, label]) => (
                                <label key={key} className="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-700 hover:bg-slate-50">
                                    <input
                                        type="checkbox"
                                        checked={Boolean(settingsState[key])}
                                        onChange={(e) => setSettingsState((prev) => ({ ...prev, [key]: e.target.checked }))}
                                        className="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    {label}
                                </label>
                            ))}
                            {settingsForm.errors && Object.keys(settingsForm.errors).length > 0 && (
                                <Alert tone="danger">{Object.values(settingsForm.errors).join(' ')}</Alert>
                            )}
                            <Button type="submit" variant="secondary" disabled={settingsForm.processing}>Guardar configuración</Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
