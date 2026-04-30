@extends('layouts.theme')

@section('title', 'Detalle del Segmento')

@section('content')

    @include('core::components.card', ['title' => 'Detalle del Segmento'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Segment Info Card -->
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $segment->name }}</h5>
                        <p class="small mb-0 text-muted">{{ $segment->description ?? 'Sin descripción' }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('chat.customers.segments.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <a href="{{ route('chat.customers.segments.edit', $segment->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <button type="button" class="btn btn-secondary" id="refreshSegmentBtn">
                            <i class="fas fa-sync me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    <!-- Segment Stats -->
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fs-1 text-primary mb-2"></i>
                                <h3 class="fw-bold mb-1">{{ $segment->customers_count ?? 0 }}</h3>
                                <small class="text-muted">Clientes en el segmento</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-filter fs-1 text-info mb-2"></i>
                                <h3 class="fw-bold mb-1">{{ count($segment->criteria ?? []) }}</h3>
                                <small class="text-muted">Filtros activos</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-{{ $segment->is_active ? 'check-circle' : 'pause-circle' }} fs-1 text-{{ $segment->is_active ? 'success' : 'warning' }} mb-2"></i>
                                <h3 class="fw-bold mb-1">{{ $segment->is_active ? 'Activo' : 'Inactivo' }}</h3>
                                <small class="text-muted">Estado del segmento</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fs-1 text-secondary mb-2"></i>
                                <h3 class="fw-bold mb-1">{{ $segment->updated_at->diffForHumans() }}</h3>
                                <small class="text-muted">Última actualización</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Criteria Card -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">Criterios de segmentación</h6>
            </div>
            <div class="card-body">
                @if(!empty($segment->criteria))
                    <div class="list-group list-group-flush">
                        @foreach($segment->criteria as $criterion)
                            <div class="list-group-item">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-primary-subtle text-primary">{{ $criterion['field'] ?? 'N/A' }}</span>
                                    <span class="text-muted">{{ $criterion['operator'] ?? 'N/A' }}</span>
                                    <span class="fw-semibold">
                                        @if(is_array($criterion['value'] ?? null))
                                            {{ isset($criterion['value']['from']) ? $criterion['value']['from'] . ' - ' . $criterion['value']['to'] : json_encode($criterion['value']) }}
                                        @else
                                            {{ $criterion['value'] ?? 'N/A' }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Este segmento no tiene criterios definidos
                    </div>
                @endif
            </div>
        </div>

        <!-- Customers List Card -->
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Clientes en este segmento</h6>
                        <p class="text-muted small mb-0">Total: {{ $customers->total() }} clientes</p>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('chat.customers.segments.show', $segment->id) }}">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search"
                                       name="search"
                                       class="form-control"
                                       placeholder="Buscar cliente..."
                                       value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary">
                                    Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-info-circle mt-1"></i>
                        <div>
                            <small class="fw-semibold">Información:</small>
                            <small class="d-block">Los clientes en esta lista cumplen todos los criterios del segmento y se actualizan automáticamente.</small>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Cliente</th>
                                <th>País / Idioma</th>
                                <th>Última actividad</th>
                                <th class="text-center">Conversaciones</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <!-- Cliente -->
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <img src="{{ $customer->getAvatarUrl() }}"
                                                 alt="{{ $customer->name }}"
                                                 class="rounded-circle"
                                                 width="44"
                                                 height="44"
                                                 style="object-fit: cover; border: 2px solid #f0f0f0;">
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">
                                                <a href="{{ route('chat.customers.show', $customer->id) }}"
                                                   class="text-dark text-decoration-none">
                                                    {{ $customer->name }}
                                                </a>
                                                @if($customer->email_verified_at)
                                                    <i class="fas fa-check-circle text-success ms-1" title="Verificado"></i>
                                                @endif
                                            </h6>
                                            <small class="text-muted d-flex align-items-center gap-1">
                                                <i class="fas fa-envelope fs-5"></i>
                                                {{ $customer->email }}
                                            </small>
                                            @if($customer->phone)
                                                <small class="text-muted d-flex align-items-center gap-1">
                                                    <i class="fas fa-phone fs-5"></i>
                                                    {{ $customer->phone }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- País / Idioma -->
                                <td>
                                    @if($customer->country)
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-globe"></i> {{ strtoupper($customer->country) }}
                                        </span>
                                    @endif
                                    @if($customer->language)
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-language"></i> {{ strtoupper($customer->language) }}
                                        </span>
                                    @endif
                                    @if(!$customer->country && !$customer->language)
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Última Actividad -->
                                <td>
                                    @if($customer->last_seen_at)
                                        <small class="text-muted">
                                            {{ $customer->last_seen_at->diffForHumans() }}
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Conversaciones -->
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $customer->conversations_count ?? 0 }}
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td>
                                    @if($customer->is_banned)
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="fas fa-ban"></i> Suspendido
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check"></i> Activo
                                        </span>
                                    @endif
                                </td>

                                <!-- Acciones -->
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('chat.customers.show', $customer->id) }}">
                                                    <i class="fas fa-eye"></i> Ver detalles
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('chat.customers.edit', $customer->id) }}">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fas fa-users fs-7"></i>
                                        </div>
                                        <h6 class="mb-1">No hay clientes en este segmento</h6>
                                        <p class="text-muted mb-0">Ajusta los criterios para incluir clientes</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($customers->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $customers->appends(['search' => request('search')])->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Refresh segment
    $('#refreshSegmentBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...');

        axios.post('{{ route('chat.customers.segments.refresh', $segment->id) }}')
            .then(response => {
                toastr.success(response.data.message || 'Segmento actualizado correctamente', 'Éxito');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                const message = error.response?.data?.message || 'Error al actualizar el segmento';
                toastr.error(message, 'Error');
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-sync me-1"></i> Actualizar');
            });
    });
});
</script>
@endpush
