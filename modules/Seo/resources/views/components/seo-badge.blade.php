@props(['model' => null, 'modelType' => null, 'modelId' => null])

@php
    if ($model) {
        $seoMeta = \Modules\Seo\Models\SeoMeta::where('seoable_type', get_class($model))
            ->where('seoable_id', $model->id)
            ->select(['id', 'seo_score', 'seo_grade'])
            ->first();
    } elseif ($modelType && $modelId) {
        $seoMeta = \Modules\Seo\Models\SeoMeta::where('seoable_type', $modelType)
            ->where('seoable_id', $modelId)
            ->select(['id', 'seo_score', 'seo_grade'])
            ->first();
    } else {
        $seoMeta = null;
    }

    $score   = $seoMeta?->seo_score;
    $grade   = $seoMeta?->seo_grade;
    $metaId  = $seoMeta?->id;

    $badgeClass = match (true) {
        $grade === 'A'  => 'bg-success',
        $grade === 'B'  => 'bg-info',
        $grade === 'C'  => 'bg-warning text-dark',
        $grade !== null => 'bg-danger',
        default         => 'bg-secondary',
    };
@endphp

@if($seoMeta)
    <a href="{{ route('settings.seo.metas.edit', $metaId) }}"
       class="badge {{ $badgeClass }} text-decoration-none"
       title="Score SEO: {{ $score }}/100 — Grado: {{ $grade }}">
        SEO {{ $grade ?? '?' }}
        @if($score !== null)
            <span class="ms-1">{{ $score }}</span>
        @endif
    </a>
@else
    <span class="badge bg-secondary opacity-50" title="Sin datos SEO">SEO —</span>
@endif
