<?php

namespace App\AI\Contracts;

interface AIProvider
{
    /** Identificador técnico del proveedor (ollama, gemini, openai, local). */
    public function name(): string;

    /** Nombre visible para el usuario. */
    public function label(): string;

    /** Modelo utilizado (o descripción corta). */
    public function model(): string;

    /** ¿Puede usarse ahora mismo? (configuración presente / servicio accesible). */
    public function available(): bool;

    /** ¿Puede producir embeddings? */
    public function supportsEmbeddings(): bool;

    /**
     * Ejecuta una generación de texto.
     *
     * @param  array{task?: string, context?: array, temperature?: float, max_tokens?: int, json?: bool}  $options
     */
    public function complete(string $system, string $user, array $options = []): AIResult;

    /** @return array<int, float> */
    public function embed(string $text): array;

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedMany(array $texts): array;
}
