@extends('layouts.theme')

@section('title', 'Respuestas predefinidas')

@push('styles')
<style>
.hd-filter-badge { font-size: 0.6rem; }
.hd-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Respuestas predefinidas'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Respuestas predefinidas</h5>
                        <p class="small mb-0 text-muted">Textos de respuesta rapida que los agentes pueden insertar durante una conversacion</p>
                    </div>
                    @can('helpdesk.canned-replies.create')
                        <a href="{{ route('settings.helpdesk.canned-replies.create') }}" class="btn btn-primary">
                            Nueva respuesta predefinida
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Respuestas configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Globales</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['global'] }}</h4>
                                <small class="text-muted">Disponibles para todos los agentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Personales</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['personal'] }}</h4>
                                <small class="text-muted">Privadas por agente</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['category', 'scope'])->filter(fn ($k) => request($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="canned-replies-filter-form" method="GET" action="{{ route('settings.helpdesk.canned-replies.index') }}">
                    <input type="hidden" name="category" id="filter-category" value="{{ request('category') }}">
                    <input type="hidden" name="scope"    id="filter-scope"    value="{{ request('scope') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por titulo, cuerpo o categoria..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#canned-replies-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary hd-filter-badge">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.helpdesk.canned-replies.index') }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <div>
                                <h6 class="mb-1">Filtrados:</h6>
                            </div>
                            @if(request('category'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Categoria: {{ request('category') }}
                                </span>
                            @endif
                            @if(request('scope'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Alcance: {{ request('scope') === 'global' ? 'Globales' : 'Personales' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($cannedReplies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th scope="col">Titulo</th>
                                    <th scope="col">Atajo</th>
                                    <th scope="col">Categoria</th>
                                    <th scope="col" class="text-center">Alcance</th>
                                    <th scope="col" class="text-center">Uso</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cannedReplies as $cannedReply)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $cannedReply->id }}"></td>
                                        <td>
                                            <strong>{{ $cannedReply->title }}</strong>
                                            @if($cannedReply->body)
                                                <div>
                                                    <small class="text-muted">{{ Str::limit(strip_tags($cannedReply->body), 60) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($cannedReply->shortcut)
                                                <code class="small">/{{ $cannedReply->shortcut }}</code>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($cannedReply->category)
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $cannedReply->category }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($cannedReply->is_global)
                                                <span class="badge bg-success-subtle text-success">Global</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Personal</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-semibold">{{ number_format($cannedReply->usage_count) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('helpdesk.canned-replies.update')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.helpdesk.canned-replies.edit', $cannedReply) }}">
                                                                Editar
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('helpdesk.canned-replies.delete')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item btn-delete"
                                                                data-id="{{ $cannedReply->id }}"
                                                                data-url="{{ route('settings.helpdesk.canned-replies.destroy', $cannedReply) }}"
                                                                data-name="{{ $cannedReply->title }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </div>
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
                                <i class="fas fa-comment-dots fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay respuestas predefinidas</h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('category') || request('scope'))
                                    No se encontraron resultados para los filtros aplicados
                                @else
                                    Crea tu primera respuesta predefinida para agilizar las conversaciones
                                @endif
                            </p>
                            @if(! request('search') && ! request('category') && ! request('scope'))
                                @can('helpdesk.canned-replies.create')
                                    <a href="{{ route('settings.helpdesk.canned-replies.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i> Crear primera respuesta
                                    </a>
                                @endcan
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($cannedReplies->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $cannedReplies->firstItem() }}</strong> a <strong>{{ $cannedReplies->lastItem() }}</strong>
                            de <strong>{{ $cannedReplies->total() }}</strong> respuestas
                        </div>
                        {{ $cannedReplies->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

    </div>

    @include('core::components.delete')

    {{-- Filter modal --}}
    <div class="modal fade" id="canned-replies-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Categoria</label>
                        <select id="modal-category" class="form-control select2-filter-modal">
                            <option value="">Todas las categorias</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Alcance</label>
                        <select id="modal-scope" class="form-control select2-filter-modal">
                            <option value="">Cualquier alcance</option>
                            <option value="global" {{ request('scope') === 'global' ? 'selected' : '' }}>Globales</option>
                            <option value="personal" {{ request('scope') === 'personal' ? 'selected' : '' }}>Personales</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="canned-replies-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="canned-replies-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="hd-bulk-toolbar position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> respuesta(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="set_global">Marcar como global</option>
                            <option value="set_personal">Marcar como personal</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // ── Filter modal ─────────────────────────────────────────────────
    $('.select2-filter-modal').select2({ dropdownParent: $('#canned-replies-filter-modal'), width: '100%' });

    $('#canned-replies-filter-apply-btn').on('click', function () {
        $('#filter-category').val($('#modal-category').val());
        $('#filter-scope').val($('#modal-scope').val());
        $('#canned-replies-filter-modal').modal('hide');
        $('#canned-replies-filter-form').submit();
    });

    $('#canned-replies-filter-clear-btn').on('click', function () {
        $('#modal-category, #modal-scope').val(null).trigger('change');
    });

    // ── Bulk actions ──────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una respuesta.'); return; }

        const applyBulkAction = function () {
            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

            $.ajax({
                url: '{{ route('settings.helpdesk.canned-replies.bulk-action') }}',
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 800);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message ?? 'Error al procesar la acción.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
        };

        if (action === 'delete') {
            window.__confirm('¿Eliminar ' + ids.length + ' respuesta(s) predefinida(s)? Esta acción no se puede deshacer.', applyBulkAction);
        } else {
            applyBulkAction();
        }
    });
});
</script>
@endpush
