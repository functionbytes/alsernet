@extends('layouts.theme')

@section('title', 'Shortcodes')

@section('content')
    @include('core::components.card', ['title' => 'Shortcodes'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Shortcodes</h5>
                        <p class="small mb-0 text-muted">Gestiona los componentes dinámicos disponibles en el editor de páginas</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.shortcode-categories.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-tags me-1"></i> Categorias
                        </a>
                        <a href="{{ route('settings.shortcodes.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo shortcode
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            @include('template::partials.stats-cards', ['cards' => [
                ['label' => 'Total',         'value' => $stats['total'],       'subtitle' => 'Shortcodes registrados'],
                ['label' => 'Activos',       'value' => $stats['active'],      'subtitle' => 'Habilitados'],
                ['label' => 'Inactivos',     'value' => $stats['inactive'],    'subtitle' => 'Deshabilitados'],
                ['label' => 'Con plantilla', 'value' => $stats['with_render'], 'subtitle' => 'Tienen render'],
            ]])

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.shortcodes.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre o key..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('settings.shortcodes.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($shortcodes->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-code fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('status'))
                                    No se encontraron shortcodes
                                @else
                                    No hay shortcodes definidos
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('status'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Crea el primer shortcode para el editor de páginas
                                @endif
                            </p>
                            @if(!request('search') && !request('status'))
                                <a href="{{ route('settings.shortcodes.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nuevo shortcode
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Key</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Plantilla</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shortcodes as $shortcode)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $shortcode->id }}"></td>
                                        <td>
                                            <strong>{{ $shortcode->name }}</strong>
                                            @if($shortcode->description)
                                                <br><small class="text-muted">{{ $shortcode->description }}</small>
                                            @endif
                                        </td>
                                        <td><code class="small">{{ $shortcode->key }}</code></td>
                                        <td class="text-center">
                                            @if($shortcode->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-light text-black">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($shortcode->render_template)
                                                <span class="badge bg-success-subtle text-success">Sí</span>
                                            @else
                                                <span class="badge bg-light text-black">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.shortcodes.edit', $shortcode) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item toggle-shortcode" href="javascript:void(0)"
                                                           data-url="{{ route('settings.shortcodes.toggle', $shortcode) }}">
                                                            {{ $shortcode->is_active ? 'Desactivar' : 'Activar' }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.shortcodes.destroy', $shortcode) }}"
                                                           data-title="Eliminar: {{ $shortcode->name }}">
                                                            Eliminar
                                                        </a>
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

        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    @include('template::partials.bulk-action-modal', [
        'entity'       => 'shortcode',
        'entityPlural' => 'shortcode(s)',
    ])

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
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
        if (!ids.length) { toastr.warning('Selecciona al menos un shortcode.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' shortcode(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.shortcodes.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' shortcode(s) actualizados.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    $(document).on('click', '.toggle-shortcode', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).data('url'),
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
        })
        .done(function (data) {
            toastr.success(data.is_active ? 'Shortcode activado.' : 'Shortcode desactivado.');
            location.reload();
        })
        .fail(function () {
            toastr.error('Error al cambiar el estado.');
        });
    });

    @if(session('success'))
        toastr.success(@json(session('success')), 'Éxito');
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')), 'Error');
    @endif
});
</script>
@endpush
