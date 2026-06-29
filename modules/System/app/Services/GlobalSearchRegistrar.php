<?php

namespace Modules\System\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Attention\Models\Attention;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\Page\Models\Page;

class GlobalSearchRegistrar
{
    public function __construct(
        private readonly GlobalSearchService $search
    ) {}

    public function register(): void
    {
        $this->registerUsersSearcher();
        $this->registerConversationsSearcher();
        $this->registerTicketsSearcher();
        $this->registerCustomersSearcher();
        $this->registerPagesSearcher();
        $this->registerAttentionSearcher();
    }

    private function registerUsersSearcher(): void
    {
        $this->search->register('users', function (string $query, int $limit) {
            return User::query()
                ->where(function ($q) use ($query) {
                    $q->where('firstname', 'LIKE', "%{$query}%")
                        ->orWhere('lastname', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn ($u) => [
                    'type' => 'user',
                    'type_label' => 'Usuario',
                    'icon' => 'fas fa-user',
                    'color' => 'primary',
                    'title' => trim("{$u->firstname} {$u->lastname}"),
                    'subtitle' => $u->email,
                    'url' => route('settings.users.show', $u->uid),
                    'relevance' => 2,
                ]);
        });
    }

    private function registerConversationsSearcher(): void
    {
        if (! class_exists(Conversation::class)) {
            return;
        }

        $this->search->register('conversations', function (string $query, int $limit) {
            return Conversation::query()
                ->where('subject', 'LIKE', "%{$query}%")
                ->latest('last_message_at')
                ->limit($limit)
                ->get()
                ->map(fn ($c) => [
                    'type' => 'conversation',
                    'type_label' => 'Conversación',
                    'icon' => 'fas fa-comments',
                    'color' => 'info',
                    'title' => $c->subject ?? "Conversación #{$c->id}",
                    'subtitle' => "#{$c->id} · ".($c->created_at?->diffForHumans() ?? ''),
                    'url' => route('manager.helpdesk.conversations.show', $c->id),
                    'relevance' => 1,
                ]);
        });
    }

    private function registerTicketsSearcher(): void
    {
        if (! class_exists(Ticket::class)) {
            return;
        }

        $this->search->register('tickets', function (string $query, int $limit) {
            return Ticket::query()
                ->where(function ($q) use ($query) {
                    $q->where('ticket_number', 'LIKE', "%{$query}%")
                        ->orWhere('subject', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($t) => [
                    'type' => 'ticket',
                    'type_label' => 'Ticket',
                    'icon' => 'fas fa-ticket',
                    'color' => 'secondary',
                    'title' => "#{$t->ticket_number} — {$t->subject}",
                    'subtitle' => Str::limit(strip_tags($t->description ?? ''), 80),
                    'url' => route('manager.helpdesk.tickets.show', $t->id),
                    'relevance' => 2,
                ]);
        });
    }

    private function registerCustomersSearcher(): void
    {
        if (! class_exists(Customer::class)) {
            return;
        }

        $this->search->register('customers', function (string $query, int $limit) {
            return Customer::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%")
                        ->orWhere('phone', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn ($c) => [
                    'type' => 'customer',
                    'type_label' => 'Contacto',
                    'icon' => 'fas fa-address-card',
                    'color' => 'success',
                    'title' => $c->name,
                    'subtitle' => $c->email,
                    'url' => route('manager.helpdesk.customers.show', $c->id),
                    'relevance' => 2,
                ]);
        });
    }

    private function registerPagesSearcher(): void
    {
        if (! class_exists(Page::class)) {
            return;
        }

        $this->search->register('pages', function (string $query, int $limit) {
            return Page::query()
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn ($p) => [
                    'type' => 'page',
                    'type_label' => 'Página',
                    'icon' => 'fas fa-file-alt',
                    'color' => 'primary',
                    'title' => $p->title,
                    'subtitle' => Str::limit(strip_tags($p->description ?? ''), 80),
                    'url' => route('pages.edit', $p->id),
                    'relevance' => 1,
                ]);
        });
    }

    private function registerAttentionSearcher(): void
    {
        if (! class_exists(Attention::class)) {
            return;
        }

        $this->search->register('attention', function (string $query, int $limit) {
            return Attention::query()
                ->where(function ($q) use ($query) {
                    $q->where('radicado', 'LIKE', "%{$query}%")
                        ->orWhere('subject', 'LIKE', "%{$query}%")
                        ->orWhere('customer_firstname', 'LIKE', "%{$query}%")
                        ->orWhere('customer_lastname', 'LIKE', "%{$query}%")
                        ->orWhere('customer_email', 'LIKE', "%{$query}%");
                })
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($a) => [
                    'type' => 'attention',
                    'type_label' => 'PQRSF',
                    'icon' => 'fas fa-clipboard-list',
                    'color' => 'danger',
                    'title' => "{$a->radicado} — {$a->subject}",
                    'subtitle' => Str::limit($a->description ?? '', 80),
                    'url' => route('attention.view', $a->uid),
                    'relevance' => 1,
                ]);
        });
    }
}
