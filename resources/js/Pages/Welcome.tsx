import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

type Demo = { name: string; url: string } | null;
type Copy = {
    title: string;
    signIn: string;
    createAccount: string;
    eyebrow: string;
    headline: string;
    highlight: string;
    introduction: string;
    exploreDemo: string;
    createTeacherAccount: string;
    stepsTitle: string;
    steps: { title: string; body: string }[];
    experienceTitle: string;
    features: { title: string; body: string }[];
    ctaTitle: string;
    ctaBody: string;
    openDemo: string;
    testUsers: string;
    teacher: string;
    student: string;
    administrator: string;
    footer: string;
};

const copy: Record<'en' | 'es', Copy> = {
    en: {
        title: 'AI-assisted classrooms, led by educators',
        signIn: 'Sign in',
        createAccount: 'Create account',
        eyebrow: 'Built for higher education teams',
        headline: 'Turn your teaching material into a',
        highlight: 'guided learning experience',
        introduction: 'Upload your course sources and let AulaGen organize the first draft of units, lessons, and activities. You review every decision before students see it.',
        exploreDemo: 'Explore the demo classroom',
        createTeacherAccount: 'Create an educator account',
        stepsTitle: 'From source material to a published classroom',
        steps: [
            { title: 'Bring your sources together', body: 'Upload PDFs, notes, slides, spreadsheets, links, and video references. AulaGen turns them into a searchable course knowledge base.' },
            { title: 'Build with an AI copilot', body: 'Generate a proposed curriculum, lessons, activities, and glossary from your own material—not from a generic template.' },
            { title: 'Review, refine, and publish', body: 'Edit, approve, preview, and publish deliberately. Students see only the learning experience your team is ready to share.' },
        ],
        experienceTitle: 'A classroom designed for learning',
        features: [
            { title: 'Clear learning paths', body: 'Organized units and lessons with previous/next navigation and individual progress.' },
            { title: 'Practice that gives feedback', body: 'Self-check activities, quizzes, and flashcards can give learners immediate feedback.' },
            { title: 'Find what matters', body: 'Semantic search finds course concepts even when students use different words.' },
            { title: 'Answers grounded in sources', body: 'The course assistant retrieves relevant material and can show the source behind its answers.' },
        ],
        ctaTitle: 'See what an educator-reviewed classroom feels like.',
        ctaBody: 'The demo course includes units, lessons, activities, bibliography, glossary, and a source-grounded assistant.',
        openDemo: 'Open',
        testUsers: 'Local test accounts (password: password)',
        teacher: 'Educator',
        student: 'Student',
        administrator: 'Administrator',
        footer: 'AulaGen — AI helps organize. Educators decide.',
    },
    es: {
        title: 'Aulas asistidas por IA, lideradas por docentes',
        signIn: 'Ingresar',
        createAccount: 'Crear cuenta',
        eyebrow: 'Creado para equipos de educación superior',
        headline: 'Transformá tu material docente en una',
        highlight: 'experiencia de aprendizaje guiada',
        introduction: 'Subí las fuentes de tu materia y dejá que AulaGen organice el primer borrador de unidades, lecciones y actividades. Revisás cada decisión antes de que llegue a estudiantes.',
        exploreDemo: 'Explorar el aula demo',
        createTeacherAccount: 'Crear cuenta docente',
        stepsTitle: 'Del material fuente a un aula publicada',
        steps: [
            { title: 'Reuní tus fuentes', body: 'Subí PDFs, apuntes, presentaciones, planillas, enlaces y videos. AulaGen los convierte en una base de conocimiento consultable.' },
            { title: 'Creá con un copiloto de IA', body: 'Generá una propuesta de programa, lecciones, actividades y glosario desde tu propio material, no desde una plantilla genérica.' },
            { title: 'Revisá, mejorá y publicá', body: 'Editá, aprobá, previsualizá y publicá deliberadamente. Los estudiantes ven solo la experiencia que el equipo está listo para compartir.' },
        ],
        experienceTitle: 'Un aula diseñada para aprender',
        features: [
            { title: 'Recorridos claros', body: 'Unidades y lecciones ordenadas con navegación previa/siguiente y progreso individual.' },
            { title: 'Práctica con devolución', body: 'Actividades, cuestionarios y flashcards pueden dar retroalimentación inmediata.' },
            { title: 'Encontrá lo importante', body: 'La búsqueda semántica encuentra conceptos aunque estudiantes usen otras palabras.' },
            { title: 'Respuestas basadas en fuentes', body: 'El asistente recupera material relevante y puede mostrar la fuente de sus respuestas.' },
        ],
        ctaTitle: 'Conocé cómo se siente un aula revisada por docentes.',
        ctaBody: 'La materia demo incluye unidades, lecciones, actividades, bibliografía, glosario y un asistente basado en fuentes.',
        openDemo: 'Abrir',
        testUsers: 'Cuentas de prueba locales (contraseña: password)',
        teacher: 'Docente',
        student: 'Estudiante',
        administrator: 'Administrador',
        footer: 'AulaGen — la IA ayuda a organizar, el equipo docente decide.',
    },
};

export default function Welcome({ canLogin, canRegister, demo }: PageProps<{ canLogin: boolean; canRegister: boolean; demo: Demo }>) {
    const locale = usePage().props.locale ?? 'en';
    const t = copy[locale];

    return (
        <>
            <Head title={t.title} />
            <div className="min-h-screen overflow-hidden bg-slate-950 text-slate-100">
                <div className="absolute inset-x-0 top-0 -z-0 h-[42rem] bg-[radial-gradient(circle_at_50%_-10%,rgba(37,99,235,0.42),transparent_42rem)]" />
                <header className="relative z-10 mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-2.5"><span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-lg font-bold text-white shadow-lg shadow-blue-950/40">A</span><span className="text-xl font-semibold tracking-tight">AulaGen</span></div>
                    <nav className="flex items-center gap-2">
                        <LanguageSwitcher className="border-slate-700 bg-slate-900/80 text-slate-200 hover:bg-slate-800 hover:text-white" />
                        {canLogin && <Link href={route('login')} className="rounded-md px-3 py-2 text-sm font-medium text-slate-300 transition hover:text-white">{t.signIn}</Link>}
                        {canRegister && <Link href={route('register')} className="rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-200">{t.createAccount}</Link>}
                    </nav>
                </header>

                <main className="relative z-10">
                    <section className="mx-auto max-w-6xl px-6 pb-20 pt-14 text-center sm:pt-24 lg:pb-28">
                        <p className="mx-auto mb-5 inline-flex rounded-full border border-blue-400/30 bg-blue-400/10 px-4 py-1.5 text-sm font-medium text-blue-200">{t.eyebrow}</p>
                        <h1 className="mx-auto max-w-4xl text-4xl font-bold tracking-tight text-white sm:text-6xl">{t.headline} <span className="bg-gradient-to-r from-blue-300 to-cyan-200 bg-clip-text text-transparent">{t.highlight}</span>.</h1>
                        <p className="mx-auto mt-7 max-w-2xl text-lg leading-relaxed text-slate-300">{t.introduction}</p>
                        <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                            {demo && <a href={demo.url} className="rounded-lg bg-blue-500 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-950/40 transition hover:bg-blue-400">{t.exploreDemo} <span aria-hidden="true">→</span></a>}
                            {canRegister && <Link href={route('register')} className="rounded-lg border border-slate-700 bg-slate-900/50 px-6 py-3.5 text-sm font-semibold text-slate-100 transition hover:border-slate-500 hover:bg-slate-800">{t.createTeacherAccount}</Link>}
                        </div>
                    </section>

                    <section className="border-y border-slate-800/80 bg-slate-900/40 py-20">
                        <div className="mx-auto max-w-6xl px-6"><h2 className="mx-auto max-w-2xl text-center text-3xl font-bold tracking-tight text-white">{t.stepsTitle}</h2><div className="mt-10 grid gap-5 md:grid-cols-3">{t.steps.map((step, index) => <article key={step.title} className="rounded-2xl border border-slate-800 bg-slate-900/80 p-7 shadow-lg shadow-black/10"><span className="text-sm font-bold text-blue-300">0{index + 1}</span><h3 className="mt-5 text-lg font-semibold text-white">{step.title}</h3><p className="mt-3 text-sm leading-relaxed text-slate-400">{step.body}</p></article>)}</div></div>
                    </section>

                    <section className="mx-auto max-w-6xl px-6 py-20"><h2 className="text-center text-3xl font-bold tracking-tight text-white">{t.experienceTitle}</h2><div className="mt-10 grid gap-5 sm:grid-cols-2">{t.features.map((feature) => <article key={feature.title} className="flex gap-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-6"><span className="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-cyan-400 shadow-[0_0_12px_rgba(34,211,238,.8)]" /><div><h3 className="font-semibold text-white">{feature.title}</h3><p className="mt-2 text-sm leading-relaxed text-slate-400">{feature.body}</p></div></article>)}</div></section>

                    {demo && <section className="mx-auto mb-10 max-w-6xl px-6"><div className="rounded-3xl border border-blue-500/30 bg-gradient-to-br from-blue-600/20 via-slate-900 to-slate-900 p-8 text-center sm:p-14"><h2 className="text-3xl font-bold tracking-tight text-white">{t.ctaTitle}</h2><p className="mx-auto mt-4 max-w-xl text-slate-300">{t.ctaBody}</p><a href={demo.url} className="mt-8 inline-block rounded-lg bg-white px-6 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-200">{t.openDemo} {demo.name} <span aria-hidden="true">→</span></a></div></section>}
                </main>

                <footer className="mx-auto mt-16 max-w-6xl border-t border-slate-800 px-6 py-10 text-sm text-slate-500"><p className="font-medium text-slate-300">{t.testUsers}</p><ul className="mt-2 space-y-1"><li>{t.teacher}: <code className="text-slate-300">docente@aulagen.test</code></li><li>{t.student}: <code className="text-slate-300">alumno@aulagen.test</code></li><li>{t.administrator}: <code className="text-slate-300">admin@aulagen.test</code></li></ul><p className="mt-6">{t.footer}</p></footer>
            </div>
        </>
    );
}
