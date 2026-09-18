<?php

namespace Modules\Supplier\Presenters;

use Illuminate\Support\Collection;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Ai\AiContentStatus;

class AiContentPresenter
{
    private ?Collection $statusMap = null;

    public function formatContentType(AiContent $content): string
    {
        $types = [];
        if ($content->generated_name) {
            $types[] = 'Nombre';
        }
        if ($content->short_description) {
            $types[] = 'Descripción';
        }
        if ($content->seo_title) {
            $types[] = 'SEO';
        }

        return ! empty($types) ? implode(', ', $types) : 'Completo';
    }

    public function formatQuality(int $score): string
    {
        $badge = match (true) {
            $score >= 80 => 'success',
            $score >= 50 => 'warning',
            default => 'danger',
        };

        return view('supplier::components.quality-badge', [
            'score' => $score,
            'badge' => $badge,
        ])->render();
    }

    public function formatStatus(string $status): string
    {
        $this->statusMap ??= AiContentStatus::all()->keyBy('key');
        $s = $this->statusMap->get($status);

        return view('supplier::components.status-badge', [
            'label' => $s?->label ?? $status,
            'class' => $s?->badge_class ?? 'bg-secondary',
        ])->render();
    }

    public function getActions(AiContent $content): string
    {
        return view('supplier::components.content-actions', [
            'content' => $content,
        ])->render();
    }
}
