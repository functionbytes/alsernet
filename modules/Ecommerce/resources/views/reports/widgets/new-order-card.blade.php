<div class="card h-100">
    <div class="card-header p-4 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Ultimas ordenes</h6>
        <a href="{{ route('ecommerce.orders.index') }}" class="btn btn-sm btn-light">Ver todas</a>
    </div>
    <div class="card-body p-0">
        @if($recentOrders->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Codigo</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('ecommerce.orders.show', $order) }}" class="fw-semibold text-decoration-none small">
                                        {{ $order->code }}
                                    </a>
                                </td>
                                <td><small class="text-muted">{{ $order->customer->name ?? 'Invitado' }}</small></td>
                                <td class="fw-semibold small">${{ number_format($order->total, 2) }}</td>
                                <td>
                                    @php
                                        $statusVal = is_object($order->status) ? $order->status->value : $order->status;
                                        $statusColor = match($statusVal) {
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'pending'   => 'warning',
                                            default     => 'info',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }}">{{ $statusVal }}</span>
                                </td>
                                <td><small class="text-muted">{{ $order->created_at->format('d/m/Y') }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-receipt fa-2x text-muted opacity-50 mb-2"></i>
                <p class="text-muted mb-0 small">Sin ordenes recientes</p>
            </div>
        @endif
    </div>
</div>
