<div class="card h-100">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-success bg-opacity-10" style="width:48px;height:48px">
                <i class="fas fa-dollar-sign fa-lg text-success"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fs-4 fw-bold">${{ number_format($totalRevenue, 2) }}</div>
                <div class="small text-muted">Ingresos del periodo</div>
            </div>
            @isset($revenueChange)
                <div>
                    @if($revenueChange >= 0)
                        <span class="badge bg-success bg-opacity-15 text-success">
                            <i class="fas fa-arrow-up me-1"></i>{{ number_format(abs($revenueChange), 1) }}%
                        </span>
                    @else
                        <span class="badge bg-danger bg-opacity-15 text-danger">
                            <i class="fas fa-arrow-down me-1"></i>{{ number_format(abs($revenueChange), 1) }}%
                        </span>
                    @endif
                    <div class="small text-muted text-end mt-1">vs periodo anterior</div>
                </div>
            @endisset
        </div>
    </div>
</div>
