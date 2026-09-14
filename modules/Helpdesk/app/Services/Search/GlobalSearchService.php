<?php

namespace Modules\Helpdesk\Services\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;

class GlobalSearchService
{
    public const MIN_QUERY_LENGTH = 2;

    public const SNIPPET_LENGTH = 140;

    /**
     * Por debajo de este largo el término cae al LIKE '%term%' de siempre en
     * vez de FULLTEXT: innodb_ft_min_token_size (3 en esta BD) hace que
     * MariaDB ignore en silencio los términos más cortos y devuelva 0 filas.
     */
    private const FULLTEXT_MIN_LENGTH = 4;

    /**
     * Quick autocomplete payload for the global search modal/sidebar.
     * Returns up to 5 results per category.
     */
    public function quickSearch(string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < self::MIN_QUERY_LENGTH) {
            return [
                'customers' => [],
                'conversations' => [],
                'messages' => [],
                'tags' => [],
            ];
        }

        return [
            'customers' => $this->searchCustomers($term, 5)->values()->all(),
            'conversations' => $this->searchConversations($term, 5)->values()->all(),
            'messages' => $this->searchMessages($term, 5)->values()->all(),
            'tags' => $this->searchTags($term, 5)->values()->all(),
        ];
    }

    public function searchCustomers(string $term, int $limit = 20): Collection
    {
        return Customer::query()
            ->forAgent(auth()->user())
            ->search($term)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'phone', 'updated_at'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'type' => 'customer',
                'updated_at' => $c->updated_at?->toIso8601String(),
                'url' => route('manager.helpdesk.customers.show', $c),
            ]);
    }

    public function paginateCustomers(string $term, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::query()
            ->forAgent(auth()->user())
            ->search($term)
            ->orderByDesc('updated_at')
            ->select(['id', 'name', 'email', 'phone', 'updated_at'])
            ->paginate($perPage)
            ->withQueryString();
    }

    public function searchConversations(string $term, int $limit = 20): Collection
    {
        return Conversation::query()
            ->forAgent(auth()->user())
            ->with(['customer:id,name,email', 'status:id,name,color'])
            ->where(function ($q) use ($term) {
                $this->applyFullTextSearch($q, 'subject', $term)
                    ->orWhere('id', $term)
                    // Sin FULLTEXT en customer.name/email: se mantiene el LIKE
                    // '%term%' de siempre (un prefijo rompería búsquedas por
                    // apellido en medio del nombre o dominio del email).
                    ->orWhereHas('customer', fn ($qc) => $qc->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderByDesc('last_message_at')
            ->limit($limit)
            ->get(['id', 'subject', 'channel', 'customer_id', 'status_id', 'last_message_at'])
            ->map(fn ($c) => $this->mapConversation($c, $term));
    }

    public function paginateConversations(string $term, int $perPage = 20): LengthAwarePaginator
    {
        return Conversation::query()
            ->forAgent(auth()->user())
            ->with(['customer:id,name,email', 'status:id,name,color'])
            ->where(function ($q) use ($term) {
                $this->applyFullTextSearch($q, 'subject', $term)
                    ->orWhere('id', $term)
                    ->orWhereHas('customer', fn ($qc) => $qc->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderByDesc('last_message_at')
            ->select(['id', 'subject', 'channel', 'customer_id', 'status_id', 'last_message_at'])
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($c) => $this->mapConversation($c, $term));
    }

    public function searchMessages(string $term, int $limit = 20): Collection
    {
        $query = ConversationItem::query()
            ->whereHas('conversation', fn ($q) => $q->forAgent(auth()->user()))
            ->with(['conversation:id,subject,customer_id', 'conversation.customer:id,name'])
            ->where('is_internal', false);

        return $this->applyFullTextSearch($query, 'body', $term)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'conversation_id', 'body', 'created_at'])
            ->map(fn ($m) => $this->mapMessage($m, $term));
    }

    public function paginateMessages(string $term, int $perPage = 20): LengthAwarePaginator
    {
        $query = ConversationItem::query()
            ->whereHas('conversation', fn ($q) => $q->forAgent(auth()->user()))
            ->with(['conversation:id,subject,customer_id', 'conversation.customer:id,name'])
            ->where('is_internal', false);

        return $this->applyFullTextSearch($query, 'body', $term)
            ->orderByDesc('created_at')
            ->select(['id', 'conversation_id', 'body', 'created_at'])
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($m) => $this->mapMessage($m, $term));
    }

    public function searchTags(string $term, int $limit = 5): Collection
    {
        return ConversationTag::query()
            ->active()
            ->where('name', 'like', "%{$term}%")
            ->limit($limit)
            ->get(['id', 'name', 'color'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
                'type' => 'tag',
                'url' => route('manager.helpdesk.conversations.index', ['tag' => $t->id]),
            ]);
    }

    private function mapConversation(Conversation $c, string $term): array
    {
        return [
            'id' => $c->id,
            'subject' => $c->subject,
            'snippet' => $this->buildSnippet($c->subject ?? '', $term),
            'customer_name' => $c->customer?->name,
            'customer_email' => $c->customer?->email,
            'channel' => $c->channel,
            'status' => $c->status?->name,
            'status_color' => $c->status?->color,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'type' => 'conversation',
            'url' => route('manager.helpdesk.conversations.index', ['selected' => $c->id]),
        ];
    }

    private function mapMessage(ConversationItem $m, string $term): array
    {
        $plain = strip_tags((string) ($m->body ?? ''));

        return [
            'id' => $m->id,
            'conversation_id' => $m->conversation_id,
            'conversation_subject' => $m->conversation?->subject,
            'customer_name' => $m->conversation?->customer?->name,
            'snippet' => $this->buildSnippet($plain, $term),
            'created_at' => $m->created_at?->toIso8601String(),
            'type' => 'message',
            'url' => route('manager.helpdesk.conversations.index', ['selected' => $m->conversation_id]).'#message-'.$m->id,
        ];
    }

    /**
     * Filtra `$column` (subject en conversations, body en conversation_items)
     * usando su índice FULLTEXT en boolean mode con comodín de sufijo, para
     * que MariaDB use el índice en vez de escanear la tabla con LIKE '%term%'.
     * Cae al LIKE original cuando el término es corto o queda vacío tras
     * retirar operadores boolean-mode.
     */
    private function applyFullTextSearch(Builder $q, string $column, string $term): Builder
    {
        $fullTextTerm = $this->fullTextBooleanTerm($term);

        if ($fullTextTerm === null) {
            return $q->where($column, 'like', "%{$term}%");
        }

        return $q->whereFullText($column, $fullTextTerm, ['mode' => 'boolean']);
    }

    /**
     * Construye el término boolean-mode (con comodín de sufijo) para FULLTEXT,
     * o null si debe usarse el LIKE '%term%' de siempre:
     *  - términos por debajo de self::FULLTEXT_MIN_LENGTH quedarían por debajo
     *    de innodb_ft_min_token_size y FULLTEXT los ignoraría en silencio;
     *  - los operadores boolean-mode (+ - < > ( ) ~ * " @) se retiran para que
     *    un término pegado con esos símbolos no rompa la sintaxis SQL; si tras
     *    retirarlos no queda nada indexable, también se cae al LIKE.
     */
    private function fullTextBooleanTerm(string $term): ?string
    {
        if (mb_strlen($term) < self::FULLTEXT_MIN_LENGTH) {
            return null;
        }

        $sanitized = trim((string) preg_replace('/[+\-<>()~*"@]/', ' ', $term));

        return $sanitized === '' ? null : $sanitized.'*';
    }

    /**
     * Build a snippet centered around the first match. Returns plain text;
     * the view layer is responsible for highlighting.
     */
    private function buildSnippet(string $text, string $term): string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return '';
        }

        $position = mb_stripos($text, $term);

        if ($position === false) {
            return Str::limit($text, self::SNIPPET_LENGTH);
        }

        $contextBefore = (int) floor(self::SNIPPET_LENGTH / 3);
        $start = max(0, $position - $contextBefore);
        $excerpt = mb_substr($text, $start, self::SNIPPET_LENGTH);

        $prefix = $start > 0 ? '… ' : '';
        $suffix = ($start + self::SNIPPET_LENGTH) < mb_strlen($text) ? ' …' : '';

        return $prefix.$excerpt.$suffix;
    }
}
