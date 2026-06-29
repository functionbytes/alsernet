<?php

namespace Modules\Social\Services;

use OpenAI\Laravel\Facades\OpenAI;

class AIContentGenerator
{
    public function generateContent(string $topic, ?string $tone = 'professional', ?int $maxLength = 280): string
    {
        $prompt = "Crea un post atractivo para redes sociales sobre: {$topic}. "
            ."Tono: {$tone}. Máximo {$maxLength} caracteres. "
            .'Solo el contenido, sin comillas ni intro.';

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un experto en marketing de redes sociales que crea contenido viral y atractivo.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 500,
            'temperature' => 0.8,
        ]);

        return trim($response->choices[0]->message->content);
    }

    public function suggestHashtags(string $content, int $count = 8): array
    {
        $prompt = "Basándote en este contenido: \"{$content}\", sugiere exactamente {$count} hashtags relevantes. "
            .'Devuelve solo los hashtags separados por espacios, con # incluido.';

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 100,
            'temperature' => 0.7,
        ]);

        $hashtags = trim($response->choices[0]->message->content);

        return array_filter(explode(' ', $hashtags), fn ($tag) => str_starts_with($tag, '#'));
    }

    public function improveContent(string $content, ?string $goal = 'engagement'): string
    {
        $prompt = "Mejora este post para maximizar {$goal}: \"{$content}\". "
            .'Mantén el mismo largo aproximado pero hazlo más atractivo.';

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un experto copywriter especializado en redes sociales.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        return trim($response->choices[0]->message->content);
    }

    public function generateVariations(string $content, int $count = 3): array
    {
        $prompt = "Crea {$count} variaciones diferentes de este post: \"{$content}\". "
            .'Cada variación debe tener un enfoque diferente pero el mismo mensaje core. '
            .'Separa cada variación con ||| (tres barras verticales).';

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 1000,
            'temperature' => 0.9,
        ]);

        $variations = explode('|||', trim($response->choices[0]->message->content));

        return array_map('trim', array_filter($variations));
    }
}
