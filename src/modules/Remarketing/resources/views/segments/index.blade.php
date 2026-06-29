@extends('layouts.theme')

@section('title', 'Segmentos')

@section('page_header')
    @include('core::components.card', ['title' => 'Segmentos'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Segmentos de audiencia</h5>
                        <p class="small mb-0 text-muted">Grupos estáticos y dinámicos de clientes</p>
                    </div>
                    <a href="{{ route('remarketing.segments.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Crear segmento
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                @if($segments->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-users-rectangle fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">No hay segmentos creados todavía</p>
                        <a href="{{ route('remarketing.segments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear primer segmento
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Miembros</th>
                                    <th>Última cálculo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($segments as $segment)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $segment->name }}</div>
                                            @if($segment->description)
                                                <small class="text-muted">{{ Str::limit($segment->description, 60) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($segment->type === 'dynamic')
                                                <span class="badge bg-primary-subtle text-primary">Dinámico</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Estático</span>
                                            @endif
                                        </td>
                                        <td class="fw-semibold">{{ number_format($segment->member_count) }}</td>
                                        <td class="small text-muted">
                                            {{ $segment->last_calculated_at?->diffForHumans() ?? 'Nunca' }}
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('remarketing.segments.edit', $segment) }}">Editar</a>
                                                    </li>
                                                    @if($segment->type === 'dynamic')
                                                        <li>
                                                            <button class="dropdown-item btn-recalculate" data-id="{{ $segment->id }}">Recalcular</button>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                                data-id="{{ $segment->id }}"
                                                                data-name="{{ $segment->name }}"
                                                                data-url="{{ route('remarketing.segments.destroy', $segment) }}">
                                                            Eliminar
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if($segments->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $segments->links() }}
                </div>
            @endif

        </div>

    </div>

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar segmento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Eliminar el segmento <strong id="delete-name"></strong>? Las campañas que lo usen perderán su audiencia.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar segmento</button>
                    </form>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $(document).on('click', '.btn-delete', function () {
        $('#delete-name').text($(this).data('name'));
        $('#deleteForm').attr('action', $(this).data('url'));
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-recalculate', function () {
        var id = $(this).data('id');
        $.ajax({
            url: '/api/remarketing/segments/' + id + '/recalculate',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) { toastr.success(res.message || 'Recálculo iniciado'); },
            error: function ()    { toastr.error('Error al iniciar el recálculo'); }
        });
    });

});
</script>
@endpush
