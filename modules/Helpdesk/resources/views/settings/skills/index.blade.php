@extends('layouts.theme')

@section('title', 'Skills')

@push('styles')
<style>
.hd-skills-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Skills'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Skills</h5>
                    <p class="small mb-0 text-muted">Habilidades que pueden asignarse a los agentes para el enrutamiento automatico de conversaciones</p>
                </div>
                @can('helpdesk.skills.manage')
                    <a href="{{ route('settings.helpdesk.skills.create') }}" class="btn btn-primary">
                        Nuevo skill
                    </a>
                @endcan
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Skills configurados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title  mb-2">Con agentes asignados</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['withAgents'] }}</h4>
                            <small class="text-muted">Skills con al menos un agente</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Búsqueda --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.skills.index') }}">
                <div class="d-flex align-items-center gap-2">
                    <input type="search" name="search" class="form-control flex-grow-1"
                        placeholder="Buscar por nombre o slug..."
                        value="{{ request('search') }}">

                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="submit" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk.skills.index') }}"
                               class="btn btn-secondary" title="Limpiar filtros">
                                <i class="fas fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($skills->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                @can('helpdesk.skills.manage')
                                    <th scope="col" width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                @endcan
                                <th scope="col">Nombre</th>
                                <th scope="col">Descripcion</th>
                                <th scope="col" class="text-center">Agentes</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($skills as $skill)
                                <tr>
                                    @can('helpdesk.skills.manage')
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $skill->id }}"></td>
                                    @endcan
                                    <td>
                                        <strong>{{ $skill->name }}</strong>
                                        <div>
                                            <code class="small text-muted">{{ $skill->slug }}</code>
                                        </div>
                                    </td>
                                    <td>
                                        @if($skill->description)
                                            <span class="text-muted small">{{ Str::limit($skill->description, 60) }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $skill->users_count ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.skills.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.skills.edit', $skill) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-modal"
                                                            data-url="{{ route('settings.helpdesk.skills.destroy', $skill) }}"
                                                            data-title="Eliminar skill: {{ $skill->name }}">
                                                            Eliminar
                                                        </a>
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
                            <i class="fas fa-star fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay skills configurados</h6>
                        <p class="text-muted mb-3">
                            @if(request('search'))
                                No se encontraron resultados para "{{ request('search') }}"
                            @else
                                Crea el primer skill para comenzar a asignarlo a los agentes
                            @endif
                        </p>
                        @if(! request('search'))
                            @can('helpdesk.skills.manage')
                                <a href="{{ route('settings.helpdesk.skills.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer skill
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($skills->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $skills->firstItem() }}</strong> a <strong>{{ $skills->lastItem() }}</strong>
                        de <strong>{{ $skills->total() }}</strong> skills
                    </div>
                    {{ $skills->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    @include('core::components.delete')

    @can('helpdesk.skills.manage')
        {{-- Bulk toolbar flotante --}}
        <div id="bulk-toolbar" class="hd-skills-bulk-toolbar position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none">
            <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
                <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
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
                        <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> skill(s)</strong>. Los skills con agentes asignados serán omitidos.</p>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Acción</label>
                            <select id="bulk-action-select" class="form-select">
                                <option value="">Seleccionar acción...</option>
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
    @endcan

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function () {
    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // ── Bulk actions ──────────────────────────────────────────────────
    if ($('#bulk-toolbar').length) {
        const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-action-select').val('');
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            bulk.reset();
        });

        $('#bulk-apply-btn').on('click', function () {
            const action = $('#bulk-action-select').val();
            const ids = bulk.getIds();

            if (!action) { toastr.warning('Selecciona una acción.'); return; }
            if (!ids.length) { toastr.warning('Selecciona al menos un skill.'); return; }

            const applyBulkAction = function () {
                const $btn = $('#bulk-apply-btn');
                $btn.prop('disabled', true).text('Procesando...');

                $.ajax({
                    url: '{{ route('settings.helpdesk.skills.bulk-action') }}',
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
                        $btn.prop('disabled', false).text('Aplicar');
                    },
                });
            };

            if (action === 'delete') {
                window.__confirm('¿Eliminar ' + ids.length + ' skill(s)? Esta acción no se puede deshacer.', applyBulkAction);
            } else {
                applyBulkAction();
            }
        });
    }
});
</script>
@endpush
