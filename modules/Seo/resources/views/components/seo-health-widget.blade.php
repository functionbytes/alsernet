@php
$data = \Illuminate\Support\Facades\Cache::remember('seo.health_widget', 300, function () {
    $since = now()->subDays(7);

    // Meta stats
    $metaStats = [
        'total' => \Modules\Seo\Models\SeoMeta::count(),
        'avg_score' => (int) \Modules\Seo\Models\SeoMeta::whereNotNull('seo_score')->avg('seo_score'),
        'low_score' => \Modules\Seo\Models\SeoMeta::where('seo_score', '<', 50)->count(),
    ];

    // Web Vitals p75 (last 7 days)
    $vitals = [];
    foreach (['LCP', 'INP', 'CLS'] as $metric) {
        $p75 = \Modules\Seo\Models\SeoWebVital::p75($metric, null, 7);
        $vitals[$metric] = $p75 !== null ? [
            'value' => $p75,
            'rating' => \Modules\Seo\Models\SeoWebVital::rate($metric, $p75),
        ] : null;
    }

    // Active alerts
    $alerts = [
        'chains' => \Modules\Seo\Models\SeoRedirect::where('is_active', true)->count() > 0
            ? (new \Modules\Seo\Services\RedirectChainDetector)->detectAll()->count()
            : 0,
        'open_404s' => \Modules\Seo\Models\Seo404Log::where('created_at', '>=', $since)
            ->where(function ($q) { $q->whereNull('has_redirect')->orWhere('has_redirect', false); })
            ->count(),
        'missing_desc' => \Modules\Seo\Models\SeoMeta::whereNull('description')->orWhere('description', '')->count(),
    ];

    return compact('metaStats', 'vitals', 'alerts');
});

$vitalColor = fn ($rating) => match ($rating) {
    'good' => 'success',
    'needs-improvement' => 'warning',
    'poor' => 'danger',
    default => 'secondary',
};
@endphp

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0"><i class="fas fa-tachometer-alt me-1"></i> Estado SEO</h6>
        <a href="{{ route('setting.seo.dashboard') }}" class="small">Ver detalle</a>
    </div>

    {{-- Score + alerts --}}
    <div class="card-body border-bottom">
        <div class="row g-3 text-center">
            <div class="col">
                <div class="fs-4 fw-bold {{ $data['metaStats']['avg_score'] >= 70 ? 'text-success' : ($data['metaStats']['avg_score'] >= 50 ? 'text-warning' : 'text-danger') }}">
                    {{ $data['metaStats']['avg_score'] ?: '—' }}
                </div>
                <small class="text-muted">Score promedio</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold">{{ number_format($data['metaStats']['total']) }}</div>
                <small class="text-muted">Páginas</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold {{ $data['metaStats']['low_score'] > 0 ? 'text-danger' : 'text-muted' }}">
                    {{ $data['metaStats']['low_score'] }}
                </div>
                <small class="text-muted">Score bajo</small>
            </div>
        </div>
    </div>

    {{-- Web Vitals (p75, 7 días) --}}
    @if(array_filter($data['vitals']))
    <div class="card-body border-bottom">
        <small class="text-uppercase text-muted d-block mb-2">Core Web Vitals (p75, 7d)</small>
        <div class="row g-2 text-center">
            @foreach($data['vitals'] as $metric => $v)
                <div class="col">
                    @if($v)
                        @php
                            $unit = $metric === 'CLS' ? '' : 'ms';
                            $display = $metric === 'CLS' ? number_format($v['value'], 2) : number_format($v['value']);
                        @endphp
                        <span class="badge bg-{{ $vitalColor($v['rating']) }}-subtle text-{{ $vitalColor($v['rating']) }} w-100 py-2">
                            <div class="small">{{ $metric }}</div>
                            <div class="fw-bold">{{ $display }}<small>{{ $unit }}</small></div>
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary w-100 py-2">
                            <div class="small">{{ $metric }}</div>
                            <div class="fw-bold">—</div>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Alerts --}}
    @if(array_sum($data['alerts']) > 0)
    <div class="card-body">
        <small class="text-uppercase text-muted d-block mb-2">Alertas</small>
        <ul class="list-unstyled mb-0 small">
            @if($data['alerts']['chains'] > 0)
                <li class="mb-1">
                    <i class="fas fa-link text-warning me-1"></i>
                    <a href="{{ route('setting.seo.redirects.detect-chains') }}">{{ $data['alerts']['chains'] }} cadenas de redirects</a>
                </li>
            @endif
            @if($data['alerts']['open_404s'] > 0)
                <li class="mb-1">
                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                    <a href="{{ route('setting.seo.404-logs.index') }}">{{ $data['alerts']['open_404s'] }} errores 404 sin resolver</a>
                </li>
            @endif
            @if($data['alerts']['missing_desc'] > 0)
                <li>
                    <i class="fas fa-align-left text-warning me-1"></i>
                    <a href="{{ route('setting.seo.metas.index', ['tab' => 'unoptimized']) }}">{{ $data['alerts']['missing_desc'] }} páginas sin descripción</a>
                </li>
            @endif
        </ul>
    </div>
    @else
    <div class="card-body">
        <div class="text-center text-muted small">
            <i class="fas fa-check-circle text-success me-1"></i> Sin alertas SEO activas
        </div>
    </div>
    @endif
</div>
