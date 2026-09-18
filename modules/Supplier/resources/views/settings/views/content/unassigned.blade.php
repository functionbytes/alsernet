@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header border-bottom p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Contenido sin asignar</h5>
                        <p class="small mb-0 text-muted">
                            {{ $unassignedCount }} contenido(s) pendientes sin revisor asignado
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.content.assignable') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-users"></i>
                        </a>
                        <a href="{{ route('settings.suppliers.content.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Buscador --}}
            <div class="card-body border-bottom">
                <form id="filter-form" method="GET" action="{{ route('settings.suppliers.content.unassigned') }}">
                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre o referencia..."
                               value="{{ request('search') }}">

                        <select name="supplier_id" class="form-select" style="max-width:220px">
                            <option value="">Todos los proveedores</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                        {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->label }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                        @if(request('search') || request('supplier_id'))
                            <a href="{{ route('settings.suppliers.content.unassigned') }}"
                               class="btn btn-secondary flex-shrink-0">
                                <i class="fas fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tabla de contenido --}}
            <div class="card-body">
                @if($contents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Producto</th>
                                    <th>Proveedor</th>
                                    <th>Categoría</th>
                                    <th width="15%">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contents as $content)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox"
                                                   value="{{ $content->uid }}">
                                        </td>
                                        <td>
                                            <strong>{{ $content->supplierProduct?->name ?? $content->generated_name ?? $content->erp_reference ?? 'Sin nombre' }}</strong>
                                            @if($content->erp_reference)
                                                <br><small class="text-muted">Ref: {{ $content->erp_reference }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $content->supplier?->label ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $content->supplierProduct?->category?->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ $content->created_at?->format('d/m/Y H:i') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-check fs-7"></i>
                            </div>
                            <h6 class="mb-1">Todo asignado</h6>
                            <p class="text-muted mb-0">
                                @if(request('search') || request('supplier_id'))
                                    No se encontraron resultados con los filtros aplicados.
                                @else
                                    Todo el contenido pendiente ya tiene un revisor asignado.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Paginación --}}
            @if($contents->total() > 0)
            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">
                            Mostrando {{ $contents->firstItem() }}–{{ $contents->lastItem() }} de {{ $contents->total() }}
                        </span>
                        <form method="GET" action="{{ route('settings.suppliers.content.unassigned') }}"
                              class="d-inline-flex align-items-center gap-1 mb-0">
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <label class="text-muted small mb-0">Por página:</label>
                            <select name="per_page" class="form-select form-select-sm d-inline-block w-auto"
                                    onchange="this.form.submit()">
                                @foreach([10, 20, 50, 100] as $opt)
                                    <option value="{{ $opt }}" {{ request('per_page', 20) == $opt ? 'selected' : '' }}>
                                        {{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    @if($contents->hasPages())
                        <nav>{{ $contents->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Asignar a revisor
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar contenido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Se asignarán <strong><span data-bulk-count>0</span> contenido(s)</strong> al revisor seleccionado.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Asignar a</label>
                        <select id="bulk-assign-user" class="form-select">
                            <option value="">Seleccionar revisor...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Asignar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-assign-user').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    // Sincroniza el contador del modal al abrirse (bulk.js solo actualiza el toolbar span)
    $('#bulk-modal').on('show.bs.modal', function () {
        const count = $('.bulk-checkbox:checked').length;
        $(this).find('[data-bulk-count]').text(count);
    });

    $('#bulk-apply-btn').on('click', function () {
        const userId = $('#bulk-assign-user').val();
        const ids    = bulk.getIds();

        if (!userId)     { toastr.warning('Selecciona un revisor.');            return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un contenido.'); return; }

        const btn = $(this).prop('disabled', true).text('Asignando...');

        $.ajax({
            url: '{{ route("settings.suppliers.content.assign-batch") }}',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({ user_id: parseInt(userId), ids: ids }),
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al asignar el contenido.');
                btn.prop('disabled', false).text('Asignar');
            },
        });
    });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-assign-user').val(null).trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Asignar');
        bulk.reset();
    });
});
</script>
@endpush
