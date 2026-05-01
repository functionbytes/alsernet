<?php

namespace Modules\Helpdesk\Services\AI;

use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;

class AutoTagService
{
    private const CATEGORIES = ['facturación', 'soporte', 'ventas', 'reclamo', 'otro'];

    public function __construct(
        private readonly AiClient $client
    ) {}

    public function categorize(Conversation $conversation): ?string
    {
        if (! $this->client->isEnabled()) {
            return null;
        }

        $firstCustomerMessage = $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->whereNotNull('author_id')
            ->orderBy('created_at')
            ->first();

        if (! $firstCustomerMessage) {
            return null;
        }

        $text = mb_substr(strip_tags($firstCustomerMessage->body ?? ''), 0, 500);

        if (empty($text)) {
            return null;
        }

        $categories = implode(', ', self::CATEGORIES);
        $systemPrompt = 'Eres un clasificador de tickets de soporte. '
            ."Responde SOLO con una de estas categorías exactas: {$categories}. Sin texto adicional.";

        $raw = $this->client->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Clasifica este mensaje de soporte:\n{$text}"],
        ]);

        return $this->parseCategory(trim((string) $raw));
    }

    /**
     * Categorize and attach the corresponding tag to the conversation.
     */
    public function categorizeAndAttach(Conversation $conversation): ?ConversationTag
    {
        $category = $this->categorize($conversation);

        if (empty($category)) {
            return null;
        }

        $slug = Str::slug($category);

        $tag = ConversationTag::query()
            ->where('slug', $slug)
            ->first();

        if (! $tag) {
            $tag = ConversationTag::query()->create([
                'name' => ucfirst($category),
                'slug' => $slug,
                'color' => $this->colorForCategory($category),
                'is_active' => true,
            ]);
        }

        if (! $conversation->conversationTags()->where('tag_id', $tag->id)->exists()) {
            $conversation->conversationTags()->attach($tag->id);
        }

        return $tag;
    }

    private function parseCategory(string $raw): ?string
    {
        $normalized = mb_strtolower($raw);

        foreach (self::CATEGORIES as $category) {
            if (str_contains($normalized, $category)) {
                return $category;
            }
        }

        return null;
    }

    private function colorForCategory(string $category): string
    {
        return match ($category) {
            'facturación' => '#0d6efd',
            'soporte' => '#198754',
            'ventas' => '#fd7e14',
            'reclamo' => '#dc3545',
            default => '#6c757d',
        };
    }
}
