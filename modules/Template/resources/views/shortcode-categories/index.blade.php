@extends('layouts.theme')

@section('title', 'Categorias de shortcodes')

@section('page_header')
    @include('core::components.card', ['title' => 'Categorias de shortcodes'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Categorias de shortcodes</h5>
                        <p class="small mb-0 text-muted">Organiza los shortcodes del editor de páginas por categorias</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.shortcodes.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-code me-1"></i> Shortcodes
                        </a>
                        <a href="{{ route('settings.shortcode-categories.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva categoria
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            @include('template::partials.stats-cards', ['cards' => [
                ['label' => 'Total',     'value' => $stats['total'],    'subtitle' => 'Categorias registradas'],
                ['label' => 'Activas',   'value' => $stats['active'],   'subtitle' => 'Habilitadas'],
                ['label' => 'Inactivas', 'value' => $stats['inactive'], 'subtitle' => 'Deshabilitadas'],
            ]])

            {{-- Table --}}
            <div class="card-body">
                @if($categories->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-tags fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay categorias definidas</h6>
                            <p class="text-muted mb-3">Crea la primera categoria para organizar los shortcodes</p>
                            <a href="{{ route('settings.shortcode-categories.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva categoria
                            </a>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Etiqueta</th>
                                    <th>Slug</th>
                                    <th class="text-center">Orden</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-body">
                                @foreach($categories as $category)
                                    <tr data-id="{{ $category->id }}">
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $category->id }}"></td>
                                        <td><strong>{{ $category->label }}</strong></td>
                                        <td><code class="small">{{ $category->slug }}</code></td>
                                        <td class="text-center">{{ $category->sort_order }}</td>
                                        <td class="text-center">
                                            @if($category->is_active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-light text-black">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.shortcode-categories.edit', $category) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item toggle-category" href="javascript:void(0)"
                                                           data-url="{{ route('settings.shortcode-categories.toggle', $category) }}">
                                                            {{ $category->is_active ? 'Desactivar' : 'Activar' }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="javascript:void(0)"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.shortcode-categories.destroy', $category) }}"
                                                           data-title="Eliminar: {{ $category->label }}">
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
        'entity'       => 'categoria',
        'entityPlural' => 'categoria(s)',
    ])

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');
    var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una categoria.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' categoria(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.shortcode-categories.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: CSRF }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                var msg = res.count + ' categoria(s) actualizadas.';
                if (res.skipped) { msg += ' ' + res.skipped + ' omitida(s) por tener shortcodes asociados.'; }
                toastr.success(msg);
                setTimeout(function () { location.reload(); }, 800);
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

    $(document).on('click', '.toggle-category', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).data('url'),
            method: 'POST',
            data: { _token: CSRF },
        })
        .done(function (data) {
            toastr.success(data.is_active ? 'Categoria activada.' : 'Categoria desactivada.');
            location.reload();
        })
        .fail(function () {
            toastr.error('Error al cambiar el estado.');
        });
    });

    // Drag & drop reorder with SortableJS (if available)
    if (typeof Sortable !== 'undefined') {
        Sortable.create(document.getElementById('sortable-body'), {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                var ids = [];
                $('#sortable-body tr').each(function () {
                    ids.push($(this).data('id'));
                });
                $.ajax({
                    url: '{{ route('settings.shortcode-categories.order') }}',
                    method: 'POST',
                    data: JSON.stringify({ ids: ids, _token: CSRF }),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                })
                .done(function () { toastr.success('Orden guardado.'); })
                .fail(function () { toastr.error('Error al guardar el orden.'); });
            },
        });
    }

    @if(session('success'))
        toastr.success(@json(session('success')), 'Éxito');
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')), 'Error');
    @endif
});
</script>
@endpush
