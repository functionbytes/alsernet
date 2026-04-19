@can('cookie.settings.view')
@php
$stats = \Illuminate\Support\Facades\Cache::remember('cookie.widget.stats', 300, fn () => [
    'total'      => \Modules\Cookie\Models\CookieConsentLog::count(),
    'accept_all' => \Modules\Cookie\Models\CookieConsentLog::where('action', 'accept_all')->count(),
    'reject_all' => \Modules\Cookie\Models\CookieConsentLog::where('action', 'reject_all')->count(),
    'custom'     => \Modules\Cookie\Models\CookieConsentLog::where('action', 'custom')->count(),
    'today'      => \Modules\Cookie\Models\CookieConsentLog::whereDate('created_at', today())->count(),
]);
$acceptRate = $stats['total'] > 0 ? round($stats['accept_all'] / $stats['total'] * 100) : 0;
$progressClass = $acceptRate >= 60 ? 'bg-success' : ($acceptRate >= 40 ? 'bg-warning' : 'bg-danger');
@endphp

<div class="card mb-4">
    <div class="card-header bg-transparent d-flex align-items-center gap-2 pb-2">
        <i class="fas fa-shield-halved text-primary"></i>
        <h6 class="mb-0 fw-semibold">Consentimiento de cookies</h6>
    </div>
    <div class="card-body pb-2">
        <div class="row g-3 text-center">
            <div class="col">
                <div class="fs-4 fw-bold">{{ number_format($stats['total']) }}</div>
                <small class="text-muted">Total</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold text-success">{{ number_format($stats['accept_all']) }}</div>
                <small class="text-muted">Aceptaron todo</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold text-danger">{{ number_format($stats['reject_all']) }}</div>
                <small class="text-muted">Rechazaron</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold text-warning">{{ number_format($stats['custom']) }}</div>
                <small class="text-muted">Personalizaron</small>
            </div>
            <div class="col">
                <div class="fs-4 fw-bold text-primary">{{ number_format($stats['today']) }}</div>
                <small class="text-muted">Hoy</small>
            </div>
        </div>

        <div class="mt-3">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">Tasa de aceptación</small>
                <small class="fw-semibold">{{ $acceptRate }}%</small>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar {{ $progressClass }}" role="progressbar"
                     style="width: {{ $acceptRate }}%"
                     aria-valuenow="{{ $acceptRate }}" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-transparent border-0 pt-0">
        <a href="{{ route('settings.cookie.logs') }}" class="small text-muted">
            Ver registros <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
</div>
@endcan
