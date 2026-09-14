<?php

namespace Modules\Helpdesk\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ConversationFilter
{
    /**
     * Por debajo de este largo el término cae al LIKE '%term%' de siempre en
     * vez de FULLTEXT: innodb_ft_min_token_size (3 en esta BD) hace que
     * MariaDB ignore en silencio los términos más cortos y devuelva 0 filas,
     * así que se deja margen para no romper búsquedas cortas.
     */
    private const FULLTEXT_MIN_LENGTH = 4;

    /** @var array<string, mixed> Filters of the active view, remembered so apply()'s base scope doesn't fight them. */
    protected array $viewFilters = [];

    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        return $query
            ->when(
                $this->request->has('status') && $this->request->status !== 'all',
                fn ($q) => $this->applyStatusValue($q, $this->request->status)
            )
            ->when(
                $this->request->has('assignee') && $this->request->assignee !== 'all',
                fn ($q) => $this->applyAssigneeValue($q, $this->request->assignee)
            )
            ->when(
                $this->request->has('group') && $this->request->group !== 'all',
                fn ($q) => $q->where('group_id', $this->request->group)
            )
            ->when(
                $this->request->has('priority') && $this->request->priority !== 'all',
                fn ($q) => $q->where('priority', $this->request->priority)
            )
            ->when(
                $this->request->has('search') && ! empty($this->request->search),
                fn ($q) => $this->applySearchValue($q, $this->request->search)
            )
            ->when(
                $this->request->filled('channel'),
                fn ($q) => $q->where('channel', $this->request->input('channel'))
            )
            ->when(
                $this->request->filled('inbox'),
                fn ($q) => $q->where('inbox_id', (int) $this->request->input('inbox'))
            )
            // Sidebar "Bloqueados" / "Spam" links (?view=blocked / ?view=spam)
            // used to fall through to the plain default-view query — same
            // result as clicking "Todas" — because nothing here ever read
            // the `view` param for these two cases.
            ->when(
                $this->request->input('view') === 'blocked',
                fn ($q) => $q->whereHas('customer', fn ($c) => $c->whereNotNull('banned_at'))
            )
            ->when(
                $this->request->input('view') === 'spam',
                fn ($q) => $q->where('is_spam', true)
            )
            ->when(
                true,
                fn ($q) => $this->applyArchived($q)
            );
    }

    /**
     * Apply a saved/seeded view's stored filters.
     *
     * Handles both vocabularies: the seeded views use is_open / is_archived /
     * status_id / snoozed, while user-saved views store the raw URL params
     * (status / archived / channel / inbox / tag / search / quick chips). Two
     * rules keep it correct:
     *
     *  - false/0 are meaningful (a "closed only" view stores is_open=false), so
     *    they must NOT be treated as "empty" and skipped.
     *  - an explicit request param on the same dimension wins over the (base)
     *    view — otherwise the default view's is_open=true / is_archived=false
     *    silently masked an explicit ?archived=1 or a closed-status filter.
     *
     * @param  array<string, mixed>  $filters
     */
    public function applyViewFilters(Builder $query, array $filters): void
    {
        // Remembered so apply()'s "hide archived" base scope yields to a view
        // that already constrains archived (e.g. a saved "archived" view).
        $this->viewFilters = $filters;

        foreach ($filters as $key => $value) {
            if ($this->isBlank($value) || $this->requestOverrides($key)) {
                continue;
            }

            match ($key) {
                // Seeded-view vocabulary.
                'status_id' => $query->where('status_id', $value),
                'snoozed' => $this->toBool($value) ? $query->snoozed() : null,
                'is_open' => $query->whereHas('status', fn ($q) => $q->where('is_open', $this->toBool($value))),
                'is_archived' => $query->where('is_archived', $this->toBool($value)),
                // Shared.
                'assignee' => $this->applyAssigneeValue($query, $value),
                'group' => $query->where('group_id', $value),
                'priority' => $query->where('priority', $value),
                // User-saved (raw URL) vocabulary.
                'status' => $this->applyStatusValue($query, (string) $value),
                'archived' => $query->where('is_archived', $this->toBool($value)),
                'channel' => $query->where('channel', $value),
                'inbox' => $query->where('inbox_id', (int) $value),
                'tag' => $query->whereHas('conversationTags', fn ($t) => $t->where('helpdesk_conversation_tags.id', (int) $value)),
                'search' => $this->applySearchValue($query, (string) $value),
                'urgent' => $query->where('priority', 'urgent'),
                'mine' => $query->where('assignee_id', auth()->id()),
                'unread' => $query->whereDoesntHave('reads', fn ($r) => $r->where('user_id', auth()->id())),
                'vip' => $query->whereHas('customer', fn ($c) => $c->where('total_conversations', '>=', 5)),
                default => null,
            };
        }
    }

    protected function applyStatusValue(Builder $query, string $status): Builder
    {
        if ($status === 'snoozed') {
            return $query->snoozed();
        }

        // "Cerradas" (sidebar) means resolved/closed — is_open=false. There are
        // two seeded statuses with is_open=false ("Resuelto", "Cerrado"), so this
        // can't be resolved by a single status name the way "pending" is below.
        if ($status === 'closed') {
            return $query->whereHas('status', fn ($q) => $q->where('is_open', false));
        }

        // Non-numeric status keywords (the sidebar "En espera" link sends
        // status=pending) must be resolved by name — the status_id column is an
        // integer, so where('status_id', 'pending') matched nothing and the view
        // came up empty even though its counter (which filters by the "Esperando"
        // status name) showed a count. Keep filter and counter consistent.
        if (! is_numeric($status)) {
            $name = ['pending' => 'Esperando'][$status] ?? $status;

            return $query->whereHas('status', fn ($q) => $q->where('name', $name));
        }

        return $query->where('status_id', $status);
    }

    protected function applyAssigneeValue(Builder $query, mixed $assignee): Builder
    {
        if ($assignee === 'mine') {
            return $query->where('assignee_id', auth()->id());
        }

        if ($assignee === 'unassigned') {
            return $query->whereNull('assignee_id');
        }

        return $query->where('assignee_id', $assignee);
    }

    protected function applySearchValue(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $this->applySubjectSearch($q, $search)
                ->orWhereHas('customer', fn ($c) => $c
                    // Sin FULLTEXT en estos campos: un agente puede buscar por
                    // apellido en medio del nombre, dominio del email o los
                    // últimos dígitos del teléfono, así que se mantiene el
                    // LIKE '%term%' de siempre (un prefijo rompería esos casos).
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));

            // A bare number also matches the conversation id (agents paste it).
            if (ctype_digit($search)) {
                $q->orWhere('helpdesk_conversations.id', (int) $search);
            }
        });
    }

    /**
     * Filtra `subject` usando el índice FULLTEXT (helpdesk_conversations_subject_fulltext)
     * en boolean mode con comodín de sufijo, para que MariaDB use el índice en
     * vez de escanear la tabla con LIKE '%term%'. Cae al LIKE original cuando
     * el término es corto o queda vacío tras retirar operadores boolean-mode.
     */
    private function applySubjectSearch(Builder $q, string $search): Builder
    {
        $fullTextTerm = $this->fullTextBooleanTerm($search);

        if ($fullTextTerm === null) {
            return $q->where('subject', 'like', "%{$search}%");
        }

        return $q->whereFullText('subject', $fullTextTerm, ['mode' => 'boolean']);
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
    private function fullTextBooleanTerm(string $search): ?string
    {
        if (mb_strlen($search) < self::FULLTEXT_MIN_LENGTH) {
            return null;
        }

        $sanitized = trim((string) preg_replace('/[+\-<>()~*"@]/', ' ', $search));

        return $sanitized === '' ? null : $sanitized.'*';
    }

    protected function applyArchived(Builder $query): Builder
    {
        // La papelera (view=deleted) muestra TODO lo eliminado, archivado o no:
        // el base scope "ocultar archivadas" no debe enmascarar la papelera.
        if ($this->request->input('view') === 'deleted') {
            return $query;
        }

        if ($this->request->has('archived')) {
            return $query->where('is_archived', $this->toBool($this->request->archived));
        }

        // Base scope hides archived — unless the active view already constrains
        // it (a saved "archived" view would otherwise be masked to zero results).
        if (array_key_exists('is_archived', $this->viewFilters) || array_key_exists('archived', $this->viewFilters)) {
            return $query;
        }

        return $query->where('is_archived', false);
    }

    /**
     * Whether an explicit request param overrides a view filter on the same
     * dimension, so the (base/default) view does not mask the user's intent.
     */
    protected function requestOverrides(string $viewKey): bool
    {
        $r = $this->request;

        return match ($viewKey) {
            'is_archived', 'archived' => $r->has('archived') || $r->filled('view'),
            'is_open', 'status_id', 'status', 'snoozed' => $r->has('status') || $r->boolean('archived'),
            'assignee', 'mine' => ($r->has('assignee') && $r->assignee !== 'all') || $r->boolean('mine'),
            'group' => $r->has('group') && $r->group !== 'all',
            'priority', 'urgent' => ($r->has('priority') && $r->priority !== 'all') || $r->boolean('urgent'),
            'channel' => $r->filled('channel'),
            'inbox' => $r->filled('inbox'),
            'tag' => $r->filled('tag'),
            'search' => $r->filled('search'),
            default => false,
        };
    }

    private function isBlank(mixed $value): bool
    {
        // Unlike empty(), false/0/'0' are kept — they are meaningful for the
        // boolean view filters (is_open=false = "closed only").
        return $value === null || $value === '' || $value === 'all' || (is_array($value) && $value === []);
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
