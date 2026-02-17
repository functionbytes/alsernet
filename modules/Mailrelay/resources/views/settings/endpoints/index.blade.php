@extends('layouts.theme')

@section('title', 'Endpoints de email')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="card-title fw-bold mb-1">Endpoints de email</h5>
                    <p class="card-subtitle text-muted mb-0">Endpoints HTTP para enviar emails mediante templates</p>
                </div>
                <a href="{{ route('settings.mailrelay.endpoints.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nuevo endpoint
                </a>
            </div>

            {{-- Filters --}}
            <div class="card bg-light-info mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('settings.mailrelay.endpoints.index') }}" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="search" class="form-control" placeholder="Nombre, clave o descripción..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-info flex-fill">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('settings.mailrelay.endpoints.index') }}" class="btn btn-light">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Endpoints List --}}
            @if($endpoints->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    No se encontraron endpoints. <a href="{{ route('settings.mailrelay.endpoints.create') }}">Crea el primero</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Clave</th>
                                <th>Template</th>
                                <th>Rate limit</th>
                                <th>Último uso</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($endpoints as $endpoint)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $endpoint->name }}</div>
                                        @if($endpoint->description)
                                            <small class="text-muted">{{ Str::limit($endpoint->description, 40) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="small bg-light px-2 py-1 rounded">{{ $endpoint->endpoint_key }}</code>
                                    </td>
                                    <td>
                                        @if($endpoint->template)
                                            <small class="text-muted">{{ $endpoint->template->name }}</small>
                                        @else
                                            <span class="badge bg-warning">Sin template</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $endpoint->rate_limit }}/min</small>
                                    </td>
                                    <td>
                                        @if($endpoint->last_used_at)
                                            <small class="text-muted" title="{{ $endpoint->last_used_at->format('Y-m-d H:i:s') }}">
                                                {{ $endpoint->last_used_at->diffForHumans() }}
                                            </small>
                                        @else
                                            <small class="text-muted">Nunca</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-status"
                                                type="checkbox"
                                                data-id="{{ $endpoint->id }}"
                                                {{ $endpoint->status === 'active' ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a href="{{ route('settings.mailrelay.endpoints.logs', $endpoint->id) }}" class="btn btn-sm btn-info" title="Ver logs">
                                                <i class="fas fa-list"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success test-btn" data-id="{{ $endpoint->id }}" title="Probar">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <a href="{{ route('settings.mailrelay.endpoints.edit', $endpoint->id) }}" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('settings.mailrelay.endpoints.destroy', $endpoint->id) }}" method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Mostrando {{ $endpoints->firstItem() ?? 0 }} a {{ $endpoints->lastItem() ?? 0 }} de {{ $endpoints->total() }} endpoints
                    </div>
                    {{ $endpoints->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Test Modal --}}
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Probar endpoint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Email de prueba</label>
                    <input type="email" class="form-control" id="test-email" placeholder="test@example.com" value="{{ auth()->user()->email ?? '' }}">
                </div>
                <div id="test-result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="execute-test">
                    <i class="fas fa-play me-2"></i>Ejecutar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentEndpointId = null;

$(document).ready(function() {
    // Toggle status
    $('.toggle-status').on('change', function() {
        const id = $(this).data('id');
        const $switch = $(this);

        $.ajax({
            url: `/mailrelay/setting/endpoints/${id}/toggle-status`,
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                }
            },
            error: function() {
                $switch.prop('checked', !$switch.prop('checked'));
                toastr.error('Error al cambiar el estado');
            }
        });
    });

    // Test endpoint
    $('.test-btn').on('click', function() {
        currentEndpointId = $(this).data('id');
        $('#testModal').modal('show');
        $('#test-result').html('');
    });

    $('#execute-test').on('click', function() {
        const email = $('#test-email').val();

        if (!email) {
            toastr.warning('Ingresa un email de prueba');
            return;
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Ejecutando...');
        $('#test-result').html('');

        $.ajax({
            url: `/mailrelay/setting/endpoints/${currentEndpointId}/test`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { test_email: email },
            success: function(response) {
                if (response.success) {
                    $('#test-result').html('<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Prueba ejecutada correctamente</div>');
                    toastr.success('Endpoint ejecutado correctamente');
                } else {
                    $('#test-result').html('<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + (response.error || 'Error desconocido') + '</div>');
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.error || 'Error al ejecutar el endpoint';
                $('#test-result').html('<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + error + '</div>');
            },
            complete: function() {
                $('#execute-test').prop('disabled', false).html('<i class="fas fa-play me-2"></i>Ejecutar');
            }
        });
    });

    // Delete confirmation
    $('.delete-form').on('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar este endpoint? Se eliminarán también todos sus logs.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
