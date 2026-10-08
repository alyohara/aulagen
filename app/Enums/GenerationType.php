<?php

namespace App\Enums;

enum GenerationType: string
{
    case Structure = 'structure';
    case LessonContent = 'lesson_content';
    case Summary = 'summary';
    case Quiz = 'quiz';
    case Autoeval = 'autoeval';
    case Flashcards = 'flashcards';
    case Glossary = 'glossary';
    case Faq = 'faq';
    case Exam = 'exam';
    case Assistant = 'assistant';

    public function label(): string
    {
        return match ($this) {
            self::Structure => 'Estructura de la materia',
            self::LessonContent => 'Contenido de lección',
            self::Summary => 'Resumen',
            self::Quiz => 'Cuestionario',
            self::Autoeval => 'Autoevaluación',
            self::Flashcards => 'Flashcards',
            self::Glossary => 'Glosario',
            self::Faq => 'Preguntas frecuentes',
            self::Exam => 'Preguntas de examen',
            self::Assistant => 'Asistente IA',
        };
    }
}
