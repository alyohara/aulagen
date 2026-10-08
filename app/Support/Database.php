<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class Database
{
    public static function driver(): string
    {
        return Schema::getConnection()->getDriverName();
    }

    public static function usesVector(): bool
    {
        return self::driver() === 'pgsql';
    }

    public static function vectorDefinition(): string
    {
        $dim = (int) config('ai.embedding_dim', 768);

        return self::usesVector() ? "vector({$dim})" : 'text';
    }
}
