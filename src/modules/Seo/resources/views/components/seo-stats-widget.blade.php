@php
$stats = \Illuminate\Support\Facades\Cache::remember('seo.widget.stats', 300, fn () => [
    'total_metas'     => \Modules\Seo\Models\SeoMeta::count(),
    'avg_score'       => (int) \Modules\Seo\Models\SeoMeta::whereNotNull('seo_score')->avg('seo_score'),
    'total_redirects' => \Modules\Seo\Models\SeoRedirect::where('is_active', true)->count(),
    'open_404s'       => \Modules\Seo\Models\Seo404Log::where('has_redirect', false)->count(),
    'orphans'         => \Modules\Seo\Models\SeoMeta::whereNull('title')->count(),
]);
@endphp
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0"><i class="fas fa-search-plus me-1"></i> Estado SEO del sitio</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 text-center">
            <div class="col">
                <div class="fs-4 fw-bold">{{ $stats['total_metas'] }}</div>
                <small class="text-muted">Páginas SEO</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold {{ $stats['avg_score'] >= 70 ? 'text-success' : ($stats['avg_score'] >= 50 ? 'text-warning' : 'text-danger') }}">
                    {{ $stats['avg_score'] }}
                </div>
                <small class="text-muted">Score promedio</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold text-primary">{{ $stats['total_redirects'] }}</div>
                <small class="text-muted">Redirects</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold {{ $stats['open_404s'] > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $stats['open_404s'] }}
                </div>
                <small class="text-muted">404s</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold {{ $stats['orphans'] > 0 ? 'text-warning' : 'text-success' }}">
                    {{ $stats['orphans'] }}
                </div>
                <small class="text-muted">Sin SEO</small>
            </div>
        </div>
    </div>
</div>
