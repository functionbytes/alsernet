<div class="card h-100">
    <div class="card-header p-4 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Ultimos clientes</h6>
        <a href="{{ route('ecommerce.customers.index') }}" class="btn btn-sm btn-light">Ver todos</a>
    </div>
    <div class="card-body p-0">
        @if($recentCustomers->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentCustomers as $customer)
                            <tr>
                                <td>
                                    <a href="{{ route('ecommerce.customers.edit', $customer) }}" class="fw-semibold text-decoration-none small">
                                        {{ $customer->name }}
                                    </a>
                                </td>
                                <td><small class="text-muted">{{ $customer->email }}</small></td>
                                <td><small class="text-muted">{{ $customer->created_at->format('d/m/Y') }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-users fa-2x text-muted opacity-50 mb-2"></i>
                <p class="text-muted mb-0 small">Sin clientes recientes</p>
            </div>
        @endif
    </div>
</div>
