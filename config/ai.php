<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor de IA activo
    |--------------------------------------------------------------------------
    | auto     -> intenta el proveedor principal (ollama) y si no está
    |             disponible cae automáticamente al proveedor local.
    | ollama   -> IA local (gratuita, sin límites).
    | gemini   -> Google Gemini (free tier).
    | openai   -> OpenAI.
    | local    -> proveedor heurístico local, sin modelos ni llamadas externas.
    */
    'provider' => env('AI_PROVIDER', 'auto'),

    // Proveedor que se intenta primero cuando provider = auto.
    'auto_primary' => env('AI_AUTO_PRIMARY', 'ollama'),

    'fallback' => env('AI_FALLBACK', 'local'),

    'providers' => [
        'ollama' => [
            'url' => env('OLLAMA_HOST', 'http://127.0.0.1:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3.2'),
            'embedding_model' => env('OLLAMA_EMBED_MODEL', 'nomic-embed-text'),
            'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
        ],

        'gemini' => [
            'key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'embedding_model' => env('GEMINI_EMBED_MODEL', 'text-embedding-004'),
        ],

        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'embedding_model' => env('OPENAI_EMBED_MODEL', 'text-embedding-3-small'),
        ],

        'custom' => [
            'label' => env('AI_CUSTOM_LABEL', 'Endpoint compatible con OpenAI'),
            'key' => env('AI_CUSTOM_KEY'),
            'url' => env('AI_CUSTOM_URL'),
            'model' => env('AI_CUSTOM_MODEL', 'gpt-4o-mini'),
            'embedding_model' => env('AI_CUSTOM_EMBED_MODEL', 'text-embedding-3-small'),
        ],

        'local' => [
            'model' => 'heuristic-local',
        ],
    ],

    'embedding_dim' => (int) env('AI_EMBEDDING_DIM', 768),

    // Tope de tokens de contexto enviado al modelo en una generación.
    'max_context_chars' => (int) env('AI_MAX_CONTEXT_CHARS', 24000),

    // Fragmentos devueltos por la búsqueda semántica para RAG.
    'rag_top_k' => (int) env('AI_RAG_TOP_K', 6),

    // Límite de consultas de IA por minuto y usuario.
    'rate_limit' => (int) env('AI_RATE_LIMIT', 10),

    'max_upload_mb' => (int) env('AI_MAX_UPLOAD_MB', 40),

    'enabled' => env('AI_ENABLED', true),
];
