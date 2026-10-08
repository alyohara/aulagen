<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIResult;

/**
 * Proveedor local sin modelos ni conexiones externas.
 *
 * No inventa contenido académico: extrae, reordena y sintetiza únicamente
 * el material provisto en el "context" (fragmentos de los documentos del
 * profesor). Permite que AulaGen funcione íntegramente sin conexión ni
 * llaves de API, manteniendo el mismo contrato que los proveedores LLM.
 */
class LocalProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'local';
    }

    public function label(): string
    {
        return 'Local (extracción, sin IA externa)';
    }

    public function model(): string
    {
        return 'heuristic-local';
    }

    public function available(): bool
    {
        return true;
    }

    public function supportsEmbeddings(): bool
    {
        return true;
    }

    public function complete(string $system, string $user, array $options = []): AIResult
    {
        $start = microtime(true);
        $task = $options['task'] ?? null;
        $context = $options['context'] ?? [];

        $text = match ($task) {
            'structure' => $this->taskStructure($context),
            'lesson_content' => $this->taskLessonContent($context),
            'summary' => $this->taskSummary($context),
            'quiz', 'autoeval', 'exam' => $this->taskQuestions($context, $task),
            'flashcards' => $this->taskFlashcards($context),
            'glossary' => $this->taskGlossary($context),
            'faq' => $this->taskFaq($context),
            'assistant' => $this->taskAssistant($context),
            default => $this->taskGeneric($context),
        };

        return $this->result($text, $this->model(), start: $start);
    }

    protected function embedOne(string $text): array
    {
        $dim = (int) config('ai.embedding_dim', 768);
        $vector = array_fill(0, $dim, 0.0);
        $tokens = $this->tokens($text);

        $counts = [];

        foreach ($tokens as $token) {
            $counts[$token] = ($counts[$token] ?? 0) + 1;
        }

        // Bigramas: mejoran la similitud de preguntas tipo frase.
        for ($i = 0; $i < count($tokens) - 1; $i++) {
            $bigram = $tokens[$i].' '.$tokens[$i + 1];
            $counts[$bigram] = ($counts[$bigram] ?? 0) + 1;
        }

        foreach ($counts as $token => $count) {
            $index = crc32($token) % $dim;
            $weight = (1.0 + log($count)) * (str_contains($token, ' ') ? 0.6 : 1.0);
            $vector[$index] += $weight;
        }

        return $this->normalize($vector);
    }

    /* ---------------------------------------------------------------------
     | Tareas
     * ------------------------------------------------------------------- */

    protected function taskStructure(array $context): string
    {
        $documents = $context['documents'] ?? [];
        $groups = [];
        $specials = ['presentation' => null, 'bibliography' => null, 'activities' => [], 'complementary' => []];

        foreach ($documents as $doc) {
            $name = (string) ($doc['name'] ?? 'Documento');
            $title = (string) ($doc['title'] ?? preg_replace('/\.[a-z0-9]+$/i', '', $name));
            $headings = array_values(array_filter(array_map(
                fn ($h) => trim(is_array($h) ? ($h['text'] ?? '') : (string) $h),
                $doc['headings'] ?? []
            )));
            $haystack = $name.' '.implode(' ', array_slice($headings, 0, 3));

            if (preg_match('/programa|s[íi]labo|syllabus|presentaci[óo]n/i', $haystack)) {
                $specials['presentation'] = ['doc' => $doc, 'title' => $title];

                continue;
            }

            if (preg_match('/bibliograf|referencias|fuentes/i', $haystack)) {
                $specials['bibliography'] = ['doc' => $doc, 'title' => $title];

                continue;
            }

            if (preg_match('/trabajo pr[áa]ctic|tp\s?\d|ejercicios|pr[áa]cticas/i', $haystack)) {
                $specials['activities'][] = ['doc' => $doc, 'title' => $title];

                continue;
            }

            if (preg_match('/complementario|anexo|ap[ée]ndice|extra/i', $haystack)) {
                $specials['complementary'][] = ['doc' => $doc, 'title' => $title];

                continue;
            }

            $unit = null;
            if (preg_match('/unidad\s*(\d+)|m[óo]dulo\s*(\d+)|unit\s*(\d+)/i', $haystack, $m)) {
                $unit = (int) ($m[1] ?: ($m[2] ?: $m[3]));
            }

            $groups[$unit ?? 'generic'][] = ['doc' => $doc, 'title' => $title, 'headings' => $headings];
        }

        $modules = [];

        if ($specials['presentation'] !== null) {
            $modules[] = [
                'title' => 'Presentación',
                'type' => 'section',
                'summary' => $this->summaryFrom($specials['presentation']['doc']['excerpt'] ?? ''),
                'lessons' => [[
                    'title' => 'Descripción de la materia',
                    'summary' => $this->summaryFrom($specials['presentation']['doc']['excerpt'] ?? ''),
                    'documents' => [$specials['presentation']['doc']['name'] ?? ''],
                ]],
            ];
        }

        $position = 1;
        $groupKeys = array_keys($groups);
        sort($groupKeys);

        foreach ($groupKeys as $key) {
            $members = $groups[$key];
            $unitNumber = is_int($key) ? $key : $position;
            $unitTitle = is_int($key)
                ? 'Unidad '.$key.' - '.$this->unitNameFrom($members)
                : $members[0]['title'];

            $lessons = [];
            foreach ($members as $member) {
                $index = 1;
                foreach ($this->lessonHeadings($member['headings']) as $heading) {
                    $lessons[] = [
                        'title' => $unitNumber.'.'.$index.' '.$heading,
                        'summary' => '',
                        'documents' => [$member['doc']['name'] ?? ''],
                    ];
                    $index++;
                }

                if ($lessons === [] || count($lessons) < 2) {
                    $lessons[] = [
                        'title' => $unitNumber.'.1 '.$member['title'],
                        'summary' => $this->summaryFrom($member['doc']['excerpt'] ?? ''),
                        'documents' => [$member['doc']['name'] ?? ''],
                    ];
                }
            }

            $modules[] = [
                'title' => $unitTitle,
                'type' => 'unit',
                'summary' => $this->summaryFrom($members[0]['doc']['excerpt'] ?? ''),
                'lessons' => array_slice($lessons, 0, 8),
            ];

            $position++;
        }

        foreach ($specials['activities'] as $activity) {
            $modules[] = [
                'title' => 'Trabajos prácticos',
                'type' => 'activities',
                'summary' => 'Actividades y trabajos prácticos de la materia.',
                'lessons' => [[
                    'title' => $activity['title'],
                    'summary' => $this->summaryFrom($activity['doc']['excerpt'] ?? ''),
                    'documents' => [$activity['doc']['name'] ?? ''],
                ]],
            ];
        }

        if ($modules === []) {
            foreach ($documents as $doc) {
                $modules[] = [
                    'title' => (string) ($doc['title'] ?? $doc['name'] ?? 'Contenido'),
                    'type' => 'unit',
                    'summary' => $this->summaryFrom($doc['excerpt'] ?? ''),
                    'lessons' => [[
                        'title' => (string) ($doc['title'] ?? $doc['name'] ?? 'Contenido'),
                        'summary' => '',
                        'documents' => [$doc['name'] ?? ''],
                    ]],
                ];
            }
        }

        if ($specials['bibliography'] !== null || $context['bibliography'] ?? false) {
            $modules[] = [
                'title' => 'Bibliografía',
                'type' => 'bibliography',
                'summary' => 'Bibliografía principal y complementaria de la materia.',
                'lessons' => [],
            ];
        }

        $modules[] = [
            'title' => 'Material complementario',
            'type' => 'complementary',
            'summary' => 'Enlaces, videos y recursos adicionales.',
            'lessons' => [],
        ];

        return json_encode(['modules' => $modules], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    protected function taskLessonContent(array $context): string
    {
        $chunks = array_values(array_filter($context['chunks'] ?? []));

        if ($chunks === []) {
            return "Todavía no hay material cargado para esta sección.\n\n> Fuente: sin material";
        }

        $max = (int) config('ai.max_context_chars', 24000);
        $used = 0;
        $blocks = [];
        $seen = [];

        foreach ($chunks as $chunk) {
            $content = trim((string) ($chunk['content'] ?? ''));
            if ($content === '' || isset($seen[md5(mb_substr($content, 0, 120))])) {
                continue;
            }
            $seen[md5(mb_substr($content, 0, 120))] = true;

            if ($used + strlen($content) > $max) {
                break;
            }

            $used += strlen($content);
            $blocks[] = $content."\n\n> Fuente: ".($chunk['reference'] ?? 'material de la materia');
        }

        $title = trim((string) ($context['title'] ?? ''));
        $intro = trim((string) ($context['intro'] ?? ''));

        $out = [];
        if ($intro !== '') {
            $out[] = $intro."\n";
        }

        $out[] = implode("\n\n", $blocks);

        if ($title !== '' && count($blocks) > 1) {
            $out[] = '### Ideas principales'."\n\n".$this->bulletList(
                $this->keySentences(implode("\n", array_column($chunks, 'content')), 5)
            );
        }

        return trim(implode("\n\n", $out));
    }

    protected function taskSummary(array $context): string
    {
        $text = implode("\n\n", array_map(fn ($c) => (string) ($c['content'] ?? ''), $context['chunks'] ?? []));

        if (trim($text) === '') {
            return 'No hay material disponible para resumir.';
        }

        return $this->bulletList($this->keySentences($text, 7));
    }

    protected function taskQuestions(array $context, string $task): string
    {
        $text = implode("\n\n", array_map(fn ($c) => (string) ($c['content'] ?? ''), $context['chunks'] ?? []));
        $definitions = $this->definitions($text);
        $sentences = $this->sentences($text);
        $questions = [];

        foreach ($definitions as $i => $definition) {
            $distractors = [];

            foreach ($definitions as $other) {
                if ($other['definition'] === $definition['definition']) {
                    continue;
                }
                $distractors[] = $other['definition'];
                if (count($distractors) === 3) {
                    break;
                }
            }

            while (count($distractors) < 3) {
                $distractors[] = 'No corresponde a una definición de este material.';
            }

            $options = $distractors;
            $options[] = $definition['definition'];
            $options = $this->stableShuffle($options, $i);

            $questions[] = [
                'type' => 'multiple',
                'prompt' => $task === 'exam'
                    ? 'Explique brevemente qué es '.$definition['term'].'.'
                    : '¿Qué es '.$definition['term'].'?',
                'options' => $options,
                'correct_answer' => $definition['definition'],
                'explanation' => 'Fuente: '.$definition['source'],
                'points' => $task === 'exam' ? 4 : 1,
            ];
        }

        foreach (array_slice($sentences, 0, 6) as $i => $sentence) {
            if (mb_strlen($sentence) < 40 || mb_strlen($sentence) > 220) {
                continue;
            }

            if ($i % 2 === 0) {
                $questions[] = [
                    'type' => 'boolean',
                    'prompt' => 'Verdadero o falso: '.$sentence,
                    'options' => ['Verdadero', 'Falso'],
                    'correct_answer' => 'Verdadero',
                    'explanation' => 'Se afirma textualmente en el material de la materia.',
                    'points' => 1,
                ];
            } else {
                $negated = 'No es cierto que '.lcfirst($sentence);
                $questions[] = [
                    'type' => 'boolean',
                    'prompt' => 'Verdadero o falso: '.$negated,
                    'options' => ['Verdadero', 'Falso'],
                    'correct_answer' => 'Falso',
                    'explanation' => 'El material afirma: "'.$sentence.'"',
                    'points' => 1,
                ];
            }
        }

        if ($questions === []) {
            $questions[] = [
                'type' => 'short',
                'prompt' => 'Resuma con sus palabras los conceptos centrales de esta sección.',
                'options' => null,
                'correct_answer' => null,
                'explanation' => 'Se sugiere reutilizar los conceptos del material cargado.',
                'points' => 2,
            ];
        }

        return json_encode(
            ['questions' => array_slice($questions, 0, 10)],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    protected function taskFlashcards(array $context): string
    {
        $text = implode("\n\n", array_map(fn ($c) => (string) ($c['content'] ?? ''), $context['chunks'] ?? []));
        $cards = [];

        foreach ($this->definitions($text) as $definition) {
            $cards[] = ['front' => '¿Qué es '.$definition['term'].'?', 'back' => $definition['definition']];
        }

        foreach (array_slice($this->keySentences($text, 6), 0, 4) as $sentence) {
            $cards[] = ['front' => 'Complete: '.mb_substr($sentence, 0, 60).'…', 'back' => $sentence];
        }

        if ($cards === []) {
            $cards[] = ['front' => 'Sin material cargado', 'back' => 'Cargue documentos para generar tarjetas.'];
        }

        return json_encode(['cards' => array_slice($cards, 0, 20)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    protected function taskGlossary(array $context): string
    {
        $text = implode("\n\n", array_map(fn ($c) => (string) ($c['content'] ?? ''), $context['chunks'] ?? []));
        $terms = [];

        foreach ($this->definitions($text) as $definition) {
            $terms[] = [
                'term' => $definition['term'],
                'definition' => $definition['definition'],
                'source' => $definition['source'],
            ];
        }

        return json_encode(['terms' => $terms], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    protected function taskFaq(array $context): string
    {
        $text = implode("\n\n", array_map(fn ($c) => (string) ($c['content'] ?? ''), $context['chunks'] ?? []));
        $items = [];

        foreach (array_slice($this->definitions($text), 0, 8) as $definition) {
            $items[] = [
                'question' => '¿Qué es '.$definition['term'].'?',
                'answer' => $definition['definition'],
                'source' => $definition['source'],
            ];
        }

        return json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    protected function taskAssistant(array $context): string
    {
        $question = trim((string) ($context['question'] ?? ''));
        $chunks = array_values(array_filter($context['chunks'] ?? []));

        if ($chunks === []) {
            return "No encontré información sobre esa pregunta en el material de la materia. Podés revisar el índice o consultar al docente.\n";
        }

        $answerChunks = [];
        $used = 0;
        $max = (int) config('ai.max_context_chars', 24000) / 2;

        foreach ($chunks as $chunk) {
            $best = $this->mostRelevantSentence((string) ($chunk['content'] ?? ''), $question);

            if ($best === '') {
                continue;
            }

            if ($used + strlen($best) > $max) {
                break;
            }

            $used += strlen($best);
            $answerChunks[] = $best."\n\n> Fuente: ".($chunk['reference'] ?? 'material de la materia');
        }

        if ($answerChunks === []) {
            return "No encontré información sobre esa pregunta en el material de la materia.\n";
        }

        return 'Según el material de la materia:'.implode("\n\n", array_slice($answerChunks, 0, 4))."\n";
    }

    protected function taskGeneric(array $context): string
    {
        $chunks = $context['chunks'] ?? [];

        if ($chunks === []) {
            return 'No hay material disponible.';
        }

        return trim((string) ($chunks[0]['content'] ?? 'No hay material disponible.'));
    }

    /* ---------------------------------------------------------------------
     | Utilidades de texto
     * ------------------------------------------------------------------- */

    protected function tokens(string $text): array
    {
        $text = mb_strtolower($text);
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = strtolower($text);
        }
        $text = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: preg_replace('/[^a-z0-9ñ\s]/iu', '', $text);

        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($words, fn ($w) => mb_strlen($w) > 2 && ! in_array($w, ['las', 'los', 'una', 'unos', 'del', 'que', 'para', 'con', 'por', 'como', 'este', 'esta', 'más', 'mas'], true)));
    }

    protected function sentences(string $text): array
    {
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $parts = preg_split('/(?<=[.!?;])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn ($s) => mb_strlen($s) > 25));
    }

    protected function definitions(string $text): array
    {
        $out = [];
        $sources = $this->sentences($text);

        foreach ($sources as $sentence) {
            if (preg_match('/^(.{3,70}?)\s+(?:es|son|se define como|se denomina|consiste en|refiere a|corresponde a)\s+(?:una?|el|la|los|las|un|unos)?\s*(.{20,300})$/iu', $sentence, $m)) {
                $term = trim($m[1]);
                $definition = trim($m[2]);

                if (preg_match('/^(el|la|los|las|un|una|unos|unas)$/i', $term) || str_contains($definition, '???')) {
                    continue;
                }

                $out[$term] = ['term' => $term, 'definition' => $this->stripSourceMarks($sentence), 'source' => 'material de la materia'];
            }
        }

        return array_values($out);
    }

    protected function stripSourceMarks(string $text): string
    {
        return trim(preg_replace('/\s*>\s*Fuente:.*$/m', '', $text) ?? $text);
    }

    protected function keySentences(string $text, int $limit): array
    {
        $sentences = $this->sentences($text);

        return array_slice($sentences, 0, $limit);
    }

    protected function mostRelevantSentence(string $text, string $question): string
    {
        $questionTokens = $this->tokens($question);
        $best = '';
        $bestScore = 0;

        foreach ($this->sentences($text) as $sentence) {
            $tokens = $this->tokens($sentence);
            if ($tokens === []) {
                continue;
            }

            $score = 0;
            foreach ($questionTokens as $token) {
                if (in_array($token, $tokens, true)) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $sentence;
            }
        }

        if ($best === '') {
            $best = mb_substr($this->stripSourceMarks($text), 0, 400);
        }

        return $this->stripSourceMarks($best);
    }

    protected function bulletList(array $items): string
    {
        return implode("\n", array_map(fn ($i) => '- '.$i, $items));
    }

    protected function summaryFrom(string $excerpt, int $sentences = 2): string
    {
        return implode(' ', array_slice($this->sentences($excerpt), 0, $sentences));
    }

    protected function unitNameFrom(array $members): string
    {
        foreach ($members as $member) {
            $title = $member['title'];
            $clean = preg_replace('/^\d+[\.\-\s]+/', '', $title) ?? $title;

            if (mb_strlen($clean) > 3 && mb_strtolower($clean) !== mb_strtolower($title)) {
                return $clean;
            }
        }

        foreach ($members as $member) {
            if (! empty($member['headings'][0])) {
                return $member['headings'][0];
            }
        }

        return $members[0]['title'];
    }

    protected function lessonHeadings(array $headings): array
    {
        $out = [];

        foreach ($headings as $heading) {
            if (is_array($heading)) {
                $level = (int) ($heading['level'] ?? 2);
                $text = trim((string) ($heading['text'] ?? ''));
            } else {
                $level = 2;
                $text = trim((string) $heading);
            }

            if ($text === '' || $level > 3 || mb_strlen($text) > 90) {
                continue;
            }

            if (in_array(mb_strtolower($text), ['introducción', 'introduccion', 'conclusión', 'conclusion', 'referencias'], true)) {
                continue;
            }

            $out[] = preg_replace('/^\d+(\.\d+)*\.?\s*/', '', $text) ?? $text;
        }

        return array_values(array_unique($out));
    }

    protected function stableShuffle(array $items, int $seed): array
    {
        $items = array_values($items);
        mt_srand(crc32('aulagen'.$seed));

        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        mt_srand();

        return $items;
    }
}
