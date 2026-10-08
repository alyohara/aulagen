import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

type Demo = { name: string; url: string } | null;

const steps = [
    {
        title: '1. Cargá tu material',
        body: 'PDF, apuntes, presentaciones, planillas, enlaces o videos. La IA extrae y indexa el contenido en segundos.',
    },
    {
        title: '2. La IA organiza',
        body: 'Propone unidades, lecciones, actividades y glosario. Todo con citas a tus fuentes: nunca inventa contenido.',
    },
    {
        title: '3. Vos aprobás y publicás',
        body: 'Nada llega al alumno sin tu visto bueno. Editá, aprobá y publicá con un clic.',
    },
];

const studentFeatures = [
    { title: 'Aula navegable', body: 'Unidades y lecciones ordenadas con progreso individual y navegación previa/siguiente.' },
    { title: 'Actividades autocorregibles', body: 'Quizzes, autoevaluaciones y flashcards con corrección y explicación al instante.' },
    { title: 'Búsqueda semántica', body: 'Encontrá cualquier concepto del material, aunque uses otras palabras.' },
    { title: 'Asistente con fuentes', body: 'Consultás dudas y las respuestas citan de qué documento sale cada respuesta.' },
];

export default function Welcome({ canLogin, canRegister, demo }: PageProps<{ canLogin: boolean; canRegister: boolean; demo: Demo }>) {
    return (
        <>
            <Head title="AulaGen — Material de cátedra con IA" />
            <div className="min-h-screen bg-slate-950 text-slate-100">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-2">
                        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-lg font-bold text-white">A</span>
                        <span className="text-xl font-semibold tracking-tight">AulaGen</span>
                    </div>
                    <nav className="flex items-center gap-2">
                        {canLogin && (
                            <Link
                                href={route('login')}
                                className="rounded-md px-4 py-2 text-sm font-medium text-slate-200 transition hover:text-white"
                            >
                                Ingresar
                            </Link>
                        )}
                        {canRegister && (
                            <Link
                                href={route('register')}
                                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500"
                            >
                                Crear cuenta
                            </Link>
                        )}
                    </nav>
                </header>

                <main className="mx-auto max-w-6xl px-6">
                    <section className="py-16 text-center lg:py-24">
                        <p className="mx-auto mb-4 rounded-full border border-blue-500/30 bg-blue-500/10 px-4 py-1 text-sm text-blue-300">
                            Para docentes universitarios
                        </p>
                        <h1 className="mx-auto max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">
                            Tu material de cátedra, convertido en un{' '}
                            <span className="text-blue-400">aula virtual</span> con IA
                        </h1>
                        <p className="mx-auto mt-6 max-w-2xl text-lg text-slate-400">
                            Cargá PDFs, apuntes y presentaciones. La IA organiza unidades, lecciones y actividades citando
                            siempre tus fuentes. Vos aprobás cada contenido antes de que lo vean tus alumnos.
                        </p>
                        <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                            {demo && (
                                <a
                                    href={demo.url}
                                    className="rounded-md bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500"
                                >
                                    Ver aula demo →
                                </a>
                            )}
                            {canRegister && (
                                <Link
                                    href={route('register')}
                                    className="rounded-md border border-slate-700 px-6 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:text-white"
                                >
                                    Crear cuenta docente
                                </Link>
                            )}
                        </div>
                    </section>

                    <section className="grid gap-6 py-12 md:grid-cols-3">
                        {steps.map((step) => (
                            <div key={step.title} className="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
                                <h3 className="text-lg font-semibold text-white">{step.title}</h3>
                                <p className="mt-3 text-sm leading-relaxed text-slate-400">{step.body}</p>
                            </div>
                        ))}
                    </section>

                    <section className="py-12">
                        <h2 className="text-center text-2xl font-bold tracking-tight sm:text-3xl">
                            El aula que reciben tus alumnos
                        </h2>
                        <div className="mt-10 grid gap-6 sm:grid-cols-2">
                            {studentFeatures.map((feature) => (
                                <div key={feature.title} className="flex gap-4 rounded-xl border border-slate-800 bg-slate-900/40 p-6">
                                    <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500" />
                                    <div>
                                        <h3 className="font-semibold text-white">{feature.title}</h3>
                                        <p className="mt-2 text-sm leading-relaxed text-slate-400">{feature.body}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-slate-800 bg-gradient-to-b from-slate-900 to-slate-950 p-8 text-center sm:p-12">
                        <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">Probá la materia demo</h2>
                        <p className="mx-auto mt-4 max-w-xl text-slate-400">
                            <em>Algoritmos y Estructuras de Datos</em> con 6 unidades, 11 lecciones, actividades
                            autocorregibles, bibliografía, glosario y asistente. Todo generado a partir de material real.
                        </p>
                        {demo && (
                            <a
                                href={demo.url}
                                className="mt-8 inline-block rounded-md bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-500"
                            >
                                Abrir {demo.name} →
                            </a>
                        )}
                    </section>
                </main>

                <footer className="mx-auto mt-16 max-w-6xl border-t border-slate-900 px-6 py-10 text-sm text-slate-500">
                    <p className="font-medium text-slate-400">Usuarios de prueba (contraseña: password)</p>
                    <ul className="mt-2 space-y-1">
                        <li>
                            Docente: <code className="text-slate-300">docente@aulagen.test</code>
                        </li>
                        <li>
                            Alumno: <code className="text-slate-300">alumno@aulagen.test</code>
                        </li>
                        <li>
                            Administrador: <code className="text-slate-300">admin@aulagen.test</code>
                        </li>
                    </ul>
                    <p className="mt-6">AulaGen — la IA organiza, el docente aprueba.</p>
                </footer>
            </div>
        </>
    );
}
