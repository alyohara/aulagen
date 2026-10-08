<?php

namespace App\Enums;

enum ModuleType: string
{
    case Section = 'section';
    case Unit = 'unit';
    case Activities = 'activities';
    case Bibliography = 'bibliography';
    case Complementary = 'complementary';

    public function label(): string
    {
        return match ($this) {
            self::Section => 'Sección',
            self::Unit => 'Unidad',
            self::Activities => 'Actividades',
            self::Bibliography => 'Bibliografía',
            self::Complementary => 'Material complementario',
        };
    }
}
