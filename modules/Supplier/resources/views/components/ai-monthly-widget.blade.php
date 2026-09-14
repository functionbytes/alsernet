@php
    use Modules\Supplier\Models\Ai\AiContent;
    use Modules\Supplier\Models\Ai\AiCost;
    use Modules\Supplier\Models\Ai\AiBudget;

    $from = now()->startOfMonth()->toDateTimeString();
    $to = now()->endOfMonth()->toDateTimeString();
    $cost = AiCost::getTotalCostForPeriod($from, $to);
    $tokens = AiCost::getTotalTokensForPeriod($from, $to);
    $requests = AiCost::whereBetween('created_at', [$from, $to])->count();
    $pending = AiContent::where('status', 'pending_validation')->count();
    $openai = AiBudget::where('provider', 'openai')->first();
    $pct = $openai ? $openai->usagePercentage() : 0;
    $barColor = $pct < 50 ? '#13deb9' : ($pct < 80 ? '#ffae1f' : '#fa896b');
@endphp

<div class="card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title fw-semibold mb-1"><i class="fas fa-robot me-2 text-success"></i>IA — este mes</h5>
                <p class="card-subtitle text-muted small mb-0">Consumo de OpenAI/Anthropic</p>
            </div>
            @can('suppliers.monitoring.view')
                <a href="{{ route('settings.suppliers.monitoring.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            @endcan
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small">Costo acumulado</div>
                <h4 class="fw-semibold mb-0">${{ number_format($cost, 2) }}</h4>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Solicitudes</div>
                <h4 class="fw-semibold mb-0">{{ number_format($requests) }}</h4>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Pendientes validar</div>
                <h4 class="fw-semibold mb-0 {{ $pending > 0 ? 'text-warning' : '' }}">{{ number_format($pending) }}</h4>
            </div>
        </div>

        @if($openai && $openai->monthly_limit > 0)
            <div class="mt-3">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>OpenAI: ${{ number_format($openai->currentMonthUsage(), 2) }} / ${{ number_format($openai->monthly_limit, 2) }}</span>
                    <span>{{ number_format($pct, 1) }}%</span>
                </div>
                <div style="height: 6px; background: #f0f0f0; border-radius: 3px;">
                    <div style="height: 6px; width: {{ min($pct, 100) }}%; background: {{ $barColor }}; border-radius: 3px;"></div>
                </div>
            </div>
        @endif
    </div>
</div>
