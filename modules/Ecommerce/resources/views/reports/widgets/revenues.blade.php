<div class="card">
    <div class="card-header p-4 border-bottom">
        <h6 class="fw-bold mb-0">Ingresos por periodo</h6>
    </div>
    <div class="card-body p-0">
        @if($revenueByPeriod->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th class="text-center">Ordenes</th>
                            <th class="text-end">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenueByPeriod as $row)
                            <tr>
                                <td class="small">{{ $row->date }}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill">{{ $row->orders }}</span>
                                </td>
                                <td class="text-end fw-semibold">${{ number_format($row->revenue, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-chart-line fa-2x text-muted opacity-50 mb-2"></i>
                <p class="text-muted mb-0 small">No hay datos</p>
            </div>
        @endif
    </div>
</div>
