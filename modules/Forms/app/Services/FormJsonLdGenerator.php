<?php

namespace Modules\Forms\Services;

use Modules\Forms\Models\Form;

/**
 * Genera schema.org JSON-LD para forms embebidos en Pages.
 * Detecta shortcodes [form id|slug] en el contenido y produce
 * un array con ContactPoint/ContactAction para inyectar en el <head>.
 */
class FormJsonLdGenerator
{
    /**
     * Escanea el contenido de una Page y devuelve el JSON-LD array
     * para los forms encontrados. Vacío si no hay.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generateForContent(string $content, ?string $pageUrl = null): array
    {
        if (! str_contains($content, '[form')) {
            return [];
        }

        $found = [];

        preg_match_all('/\[form(?:-link)?\s+(?:id="(\d+)"|slug="([a-z0-9\-_]+)")/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $id = isset($m[1]) && $m[1] !== '' ? (int) $m[1] : null;
            $slug = isset($m[2]) && $m[2] !== '' ? $m[2] : null;
            $key = $id ? "id:$id" : ($slug ? "slug:$slug" : null);
            if (! $key || isset($found[$key])) {
                continue;
            }

            $form = Form::query()
                ->where('is_active', true)
                ->when($id, fn ($q) => $q->where('id', $id))
                ->when(! $id && $slug, fn ($q) => $q->where('slug', $slug))
                ->first();

            if (! $form) {
                continue;
            }

            $found[$key] = [
                '@context' => 'https://schema.org',
                '@type' => 'ContactPoint',
                'name' => $form->name,
                'description' => $form->description ?: ($form->seo_description ?? null),
                'contactType' => 'customer support',
                'availableLanguage' => [app()->getLocale()],
                'url' => $pageUrl ?: route('forms.public.show', $form->slug),
                'potentialAction' => [
                    '@type' => 'CommunicateAction',
                    'target' => route('forms.public.submit', $form->slug),
                    'name' => $form->submit_button_text ?? 'Enviar formulario',
                ],
            ];
        }

        return array_values($found);
    }
}
