import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Select, Textarea } from '@/Components/ui/form';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        institution: '',
        career: '',
        course_year: '',
        duration: '',
        modality: '',
        objectives: '',
        program: '',
        collaborators: '',
        bibliography_principal: '',
        bibliography_complementary: '',
        generate_structure: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('courses.store'));
    };

    return (
        <AuthenticatedLayout header={<h1 className="text-xl font-semibold text-slate-900">Nueva materia</h1>}>
            <Head title="Nueva materia" />

            <div className="mx-auto max-w-4xl space-y-6 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la materia</CardTitle>
                        <CardDescription>Con estos datos se arma el encabezado del aula virtual. Podés completarlos después.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="name" value="Nombre de la materia *" />
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Algoritmos y Estructuras de Datos"
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="description" value="Descripción" />
                                <Textarea id="description" value={data.description} className="mt-1" onChange={(e) => setData('description', e.target.value)} />
                                <InputError message={errors.description} className="mt-2" />
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
                                    <TextInput id="course_year" value={data.course_year} className="mt-1 block w-full" onChange={(e) => setData('course_year', e.target.value)} placeholder="1.º año" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="duration" value="Duración" />
                                    <TextInput id="duration" value={data.duration} className="mt-1 block w-full" onChange={(e) => setData('duration', e.target.value)} placeholder="16 semanas" />
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
                                    <TextInput id="collaborators" value={data.collaborators} className="mt-1 block w-full" onChange={(e) => setData('collaborators', e.target.value)} placeholder="Prof. Ana Rivas, Prof. Diego Salas" />
                                </div>
                            </div>

                            <div>
                                <InputLabel htmlFor="objectives" value="Objetivos de aprendizaje (uno por línea)" />
                                <Textarea id="objectives" className="mt-1" value={data.objectives} onChange={(e) => setData('objectives', e.target.value)} />
                            </div>

                            <div>
                                <InputLabel htmlFor="program" value="Programa / plan de contenidos (uno por línea)" />
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

                            <label className="flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={data.generate_structure}
                                    onChange={(e) => setData('generate_structure', e.target.checked)}
                                    className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Pedirle a la IA que proponga una estructura cuando haya material cargado
                            </label>

                            <div className="flex gap-3 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Crear materia
                                </Button>
                                <Link href={route('dashboard')}>
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
