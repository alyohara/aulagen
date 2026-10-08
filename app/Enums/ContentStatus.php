<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Review = 'review';
    case Approved = 'approved';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Generated => 'Generado',
            self::Review => 'Revisión',
            self::Approved => 'Aprobado',
            self::Published => 'Publicado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700',
            self::Generated => 'bg-amber-100 text-amber-800',
            self::Review => 'bg-orange-100 text-orange-800',
            self::Approved => 'bg-emerald-100 text-emerald-800',
            self::Published => 'bg-blue-100 text-blue-800',
        };
    }
}
