import AulaLayout from '@/Layouts/AulaLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { AulaShared } from '@/types/models';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';

interface QuestionItem {
    id: number;
    type: string;
    prompt: string;
    options?: string[] | null;
}

interface ActivityItem {
    id: number;
    title: string;
    slug: string;
    type: string;
    type_label: string;
    instructions?: string | null;
    module?: string | null;
    questions: QuestionItem[];
    cards: { front: string; back: string }[];
}

interface Props extends AulaShared {
    activities: ActivityItem[];
    progress: number[];
}

function Quiz({ activity, questions, courseSlug }: { activity: ActivityItem; questions: QuestionItem[]; courseSlug: string }) {
    const [answers, setAnswers] = useState<Record<string, string>>({});
    const [results, setResults] = useState<Record<string, { correct: boolean; correct_answer?: string; explanation?: string }> | null>(null);
    const [busy, setBusy] = useState(false);

    const check = () => {
        setBusy(true);
        axios
            .post(route('aula.check', [courseSlug, activity.id]), { answers })
            .then((response) => setResults(response.data.results))
            .catch(() => setResults(null))
            .finally(() => setBusy(false));
    };

    const score = results ? Object.values(results).filter((r) => r.correct).length : 0;

    return (
        <div className="space-y-4">
            {questions.map((question, index) => {
                const result = results ? results[String(question.id)] : null;
                const chosen = answers[String(question.id)];

                return (
                    <div key={question.id} className="rounded-xl border border-slate-200 p-4">
                        <p className="font-medium text-slate-800">
                            {index + 1}. {question.prompt}
                        </p>
                        <div className="mt-3 space-y-2">
                            {(question.options ?? []).map((option) => {
                                const selected = chosen === option;
                                const isCorrect = result ? option === result.correct_answer : false;
                                const stateClass = !result
                                    ? selected
                                        ? 'border-indigo-400 bg-indigo-50'
                                        : 'border-slate-200 hover:border-slate-300'
                                    : isCorrect
                                      ? 'border-emerald-400 bg-emerald-50'
                                      : selected
                                        ? 'border-rose-400 bg-rose-50'
                                        : 'border-slate-200';

                                return (
                                    <button
                                        key={option}
                                        type="button"
                                        disabled={result !== null}
                                        onClick={() => setAnswers((prev) => ({ ...prev, [String(question.id)]: option }))}
                                        className={`block w-full rounded-lg border px-3 py-2 text-left text-sm text-slate-700 transition ${stateClass}`}
                                    >
                                        {option}
                                        {result && isCorrect && ' ✓'}
                                        {result && selected && !isCorrect && ' ✕'}
                                    </button>
                                );
                            })}
                        </div>
                        {result?.explanation && <p className="mt-2 text-xs text-slate-500">💡 {result.explanation}</p>}
                    </div>
                );
            })}

            {!results ? (
                <Button onClick={check} disabled={busy || Object.keys(answers).length === 0}>
                    {busy ? 'Corrigiendo…' : `Corregir (${Object.keys(answers).length}/${questions.length} respondidas)`}
                </Button>
            ) : (
                <div className="flex items-center gap-3">
                    <Badge tone={score === questions.length ? 'success' : 'warning'}>
                        {score}/{questions.length} correctas
                    </Badge>
                    <Button size="sm" variant="outline" onClick={() => { setAnswers({}); setResults(null); }}>
                        Reintentar
                    </Button>
                </div>
            )}
        </div>
    );
}

function Flashcards({ cards }: { cards: { front: string; back: string }[] }) {
    const [index, setIndex] = useState(0);
    const [flipped, setFlipped] = useState(false);

    if (cards.length === 0) return null;
    const card = cards[index];

    return (
        <div className="space-y-3">
            <button
                type="button"
                onClick={() => setFlipped((f) => !f)}
                className="flex min-h-[160px] w-full items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center text-lg font-medium text-slate-700 transition hover:border-indigo-300"
            >
                {flipped ? card.back : card.front}
            </button>
            <div className="flex items-center justify-between text-sm text-slate-500">
                <span>Tarjeta {index + 1} de {cards.length} · {flipped ? 'reverso' : 'anverso'} (tocá para voltear)</span>
                <span className="flex gap-2">
                    <Button size="sm" variant="outline" onClick={() => { setFlipped(false); setIndex((i) => (i - 1 + cards.length) % cards.length); }}>←</Button>
                    <Button size="sm" variant="outline" onClick={() => { setFlipped(false); setIndex((i) => (i + 1) % cards.length); }}>→</Button>
                </span>
            </div>
        </div>
    );
}

export default function AulaActivities({ course, navigation, settings, isPreview, previewUrl, activities, progress }: Props) {
    const [open, setOpen] = useState<number | null>(activities[0]?.id ?? null);

    return (
        <AulaLayout course={course} navigation={navigation} settings={settings} isPreview={isPreview} previewUrl={previewUrl} active="activities" progress={progress}>
            <Head title={`Actividades · ${course.name}`} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Actividades</h1>
                    <p className="mt-1 text-slate-600">Cuestionarios, autoevaluaciones y flashcards para repasar lo visto en clase.</p>
                </div>

                {activities.length === 0 && (
                    <Card>
                        <CardContent className="p-5 text-sm text-slate-500">Todavía no hay actividades publicadas.</CardContent>
                    </Card>
                )}

                {activities.map((activity) => (
                    <Card key={activity.id}>
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <CardTitle>{activity.title}</CardTitle>
                                        <Badge tone="info">{activity.type_label}</Badge>
                                    </div>
                                    {activity.instructions && <CardDescription>{activity.instructions}</CardDescription>}
                                    {activity.module && <p className="text-xs text-slate-400">{activity.module}</p>}
                                </div>
                                <Button size="sm" variant={open === activity.id ? 'secondary' : 'outline'} onClick={() => setOpen(open === activity.id ? null : activity.id)}>
                                    {open === activity.id ? 'Cerrar' : 'Resolver'}
                                </Button>
                            </div>
                        </CardHeader>

                        {open === activity.id && (
                            <CardContent>
                                {activity.questions.length > 0 && <Quiz activity={activity} questions={activity.questions} courseSlug={course.slug} />}
                                {activity.cards.length > 0 && <Flashcards cards={activity.cards} />}
                                {activity.questions.length === 0 && activity.cards.length === 0 && (
                                    <p className="text-sm text-slate-500">Esta actividad aún no tiene preguntas.</p>
                                )}
                            </CardContent>
                        )}
                    </Card>
                ))}
            </div>
        </AulaLayout>
    );
}
