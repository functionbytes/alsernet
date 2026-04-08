<?php

namespace App\Services;

use Illuminate\Support\Str;
use Modules\Attention\Models\Attention;
use Modules\Blog\Models\BlogPost;
use Modules\Forms\Models\Form;
use Modules\Helpdesk\Models\HelpdeskTicket;
use Modules\Page\Models\Page;
use Modules\Reviews\Models\Review;

class GlobalSearchRegistrar
{
    public function __construct(
        private readonly GlobalSearchService $search
    ) {}

    public function register(): void
    {
        $this->registerReviewsSearcher();
        $this->registerPagesSearcher();
        $this->registerBlogSearcher();
        $this->registerAttentionSearcher();
        $this->registerFormsSearcher();
        $this->registerHelpdeskSearcher();
    }

    private function registerReviewsSearcher(): void
    {
        if (! class_exists(Review::class)) {
            return;
        }

        $this->search->register('reviews', function (string $query, int $limit) {
            return Review::query()
                ->where(function ($q) use ($query) {
                    $q->where('comment', 'LIKE', "%{$query}%")
                        ->orWhere('reviewer_name', 'LIKE', "%{$query}%");
                })
                ->latest('review_time')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => [
                    'type' => 'review',
                    'type_label' => 'Reseña',
                    'icon' => 'fas fa-star',
                    'color' => 'warning',
                    'title' => $r->reviewer_name ?? 'Reseña',
                    'subtitle' => Str::limit($r->comment ?? '', 80),
                    'url' => route('reviews.show', $r->id),
                    'relevance' => 1,
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
                        ->orWhere('description', 'LIKE', "%{$query}%")
                        ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn ($p) => [
                    'type' => 'page',
                    'type_label' => 'Página',
                    'icon' => 'fas fa-file-alt',
                    'color' => 'primary',
                    'title' => $p->title,
                    'subtitle' => Str::limit(strip_tags($p->description ?? $p->content ?? ''), 80),
                    'url' => route('pages.edit', $p->id),
                    'relevance' => 1,
                ]);
        });
    }

    private function registerBlogSearcher(): void
    {
        if (! class_exists(BlogPost::class)) {
            return;
        }

        $this->search->register('blog', function (string $query, int $limit) {
            return BlogPost::query()
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%")
                        ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn ($p) => [
                    'type' => 'blog',
                    'type_label' => 'Post',
                    'icon' => 'fas fa-newspaper',
                    'color' => 'info',
                    'title' => $p->title,
                    'subtitle' => Str::limit($p->description ?? strip_tags($p->content ?? ''), 80),
                    'url' => route('blog.posts.edit', $p->id),
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
                        ->orWhere('description', 'LIKE', "%{$query}%")
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

    private function registerFormsSearcher(): void
    {
        if (! class_exists(Form::class)) {
            return;
        }

        $this->search->register('forms', function (string $query, int $limit) {
            return Form::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn ($f) => [
                    'type' => 'form',
                    'type_label' => 'Formulario',
                    'icon' => 'fas fa-wpforms',
                    'color' => 'success',
                    'title' => $f->name,
                    'subtitle' => Str::limit($f->description ?? '', 80),
                    'url' => route('settings.forms.edit', $f->id),
                    'relevance' => 1,
                ]);
        });
    }

    private function registerHelpdeskSearcher(): void
    {
        try {
            if (! class_exists(HelpdeskTicket::class, false)) {
                return;
            }

            $this->search->register('helpdesk', function (string $query, int $limit) {
                return HelpdeskTicket::query()
                    ->where(function ($q) use ($query) {
                        $q->where('ticket_number', 'LIKE', "%{$query}%")
                            ->orWhere('title', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->map(fn ($t) => [
                        'type' => 'helpdesk',
                        'type_label' => 'Ticket',
                        'icon' => 'fas fa-headset',
                        'color' => 'secondary',
                        'title' => "{$t->ticket_number} — {$t->title}",
                        'subtitle' => Str::limit($t->description ?? '', 80),
                        'url' => route('helpdesk.tickets.show', $t->id),
                        'relevance' => 1,
                    ]);
            });
        } catch (\Throwable) {
            // module not installed
        }
    }
}
