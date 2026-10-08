<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Teacher => 'Docente',
            self::Student => 'Alumno',
        };
    }
}
