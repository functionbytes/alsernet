<div class="card h-100">
    <div class="card-header p-4 border-bottom">
        <h6 class="fw-bold mb-0">Productos mas vendidos</h6>
    </div>
    <div class="card-body p-0">
        @if($topProducts->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Vendidos</th>
                            <th class="text-end">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topProducts as $item)
                            <tr>
                                <td class="fw-semibold small">{{ $item->name ?? $item->product_name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill">{{ $item->total_sold ?? $item->total_qty }}</span>
                                </td>
                                <td class="text-end fw-semibold small">${{ number_format($item->revenue ?? $item->total_revenue, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-box-open fa-2x text-muted opacity-50 mb-2"></i>
                <p class="text-muted mb-0 small">Sin ventas en el periodo</p>
            </div>
        @endif
    </div>
</div>
