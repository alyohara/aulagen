<?php

namespace App\Enums;

enum ActivityType: string
{
    case Quiz = 'quiz';
    case Autoeval = 'autoeval';
    case Flashcards = 'flashcards';
    case Exam = 'exam';
    case Practice = 'practice';
    case Assignment = 'assignment';

    public function label(): string
    {
        return match ($this) {
            self::Quiz => 'Cuestionario',
            self::Autoeval => 'Autoevaluación',
            self::Flashcards => 'Flashcards',
            self::Exam => 'Preguntas de examen',
            self::Practice => 'Ejercicios prácticos',
            self::Assignment => 'Trabajo práctico',
        };
    }
}
