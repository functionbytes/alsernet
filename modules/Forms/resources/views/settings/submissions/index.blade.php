@extends('layouts.theme')

@section('title', 'Envíos: ' . $form->name)

@section('content')

    @include('core::components.card', ['title' => 'Envíos: ' . $form->name])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $form->name }}</h5>
                        <p class="small mb-0 text-muted">Gestión de envíos</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.forms.analytics', $form) }}">Analytics</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#exportModal">Exportar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold text-primary">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Submissions recibidas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">No leídas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['unread'] }}</h4>
                                <small class="text-muted">Pendientes de revisión</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Spam</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['spam'] }}</h4>
                                <small class="text-muted">Marcadas como spam</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con estrella</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['starred'] }}</h4>
                                <small class="text-muted">Destacadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Barra de búsqueda + filtros --}}
            @php
                $activeFilters = collect([
                    request('status'), request('assigned_to'), request('date_from'), request('date_to'),
                    request()->has('spam') && request('spam') !== '0' ? request('spam') : null,
                    request()->has('read') ? request('read') : null,
                ])->filter()->count();
            @endphp

            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.forms.submissions.index', $form) }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar en valores..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-sliders me-1"></i>
                            @if($activeFilters > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                    {{ $activeFilters }}
                                </span>
                            @endif
                        </button>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search') || $activeFilters > 0)
                            <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>

                    {{-- Campos ocultos para preservar filtros del modal al buscar --}}
                    @foreach(['status','spam','read','assigned_to','date_from','date_to'] as $f)
                        @if(request()->has($f))
                            <input type="hidden" name="{{ $f }}" value="{{ request($f) }}">
                        @endif
                    @endforeach
                </form>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                @if ($submissions->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted opacity-50 mb-3"></i>
                        <h5 class="text-muted mb-2">No hay envíos</h5>
                        <p class="text-muted mb-0">No se encontraron resultados para los filtros aplicados</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" class="form-check-input" id="select-all"></th>
                                    <th>Radicado</th>
                                    <th>Ciudadano</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($submissions as $submission)
                                    @php
                                        $statusMap = [
                                            'new'       => ['label' => 'Nuevo',       'class' => 'bg-primary'],
                                            'in_review' => ['label' => 'En revisión', 'class' => 'bg-success'],
                                            'resolved'  => ['label' => 'Resuelto',    'class' => 'bg-success'],
                                            'rejected'  => ['label' => 'Rechazado',   'class' => 'bg-danger'],
                                        ];
                                        $st           = $statusMap[$submission->status ?? 'new'] ?? $statusMap['new'];
                                        $citizenName  = $submission->getCitizenName();
                                        $citizenEmail = $submission->getEmailValue();
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $submission->id }}"></td>
                                        <td>
                                            <strong class="text-primary">{{ $submission->getRadicado() }}</strong>
                                        </td>
                                        <td>
                                            @if($citizenName)
                                                <span class="fw-semibold">{{ $citizenName }}</span><br>
                                            @endif
                                            <small class="text-muted">{{ $citizenEmail ?? '—' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                                            @if($submission->is_spam)
                                                <span class="badge bg-danger ms-1">Spam</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $submission->created_at?->format('d/m/Y H:i') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.submissions.show', [$form, $submission]) }}">Ver detalles</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.submissions.pdf', [$form, $submission]) }}" target="_blank">Descargar PDF</a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.forms.submissions.destroy', [$form, $submission]) }}"
                                                           data-title="Eliminar submission #{{ $submission->id }}">
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

            @if ($submissions->hasPages())
                <div class="card-footer">
                    {{ $submissions->withQueryString()->links() }}
                </div>
            @endif

        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> envío(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar</option>
                            <option value="anonymize">Anonimizar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-2">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal filtros avanzados --}}
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('settings.forms.submissions.index', $form) }}" id="filterModalForm">
                    {{-- Preservar búsqueda de texto --}}
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">Filtros avanzados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="new"       {{ request('status') === 'new'       ? 'selected' : '' }}>Nuevo</option>
                                <option value="in_review" {{ request('status') === 'in_review' ? 'selected' : '' }}>En revisión</option>
                                <option value="resolved"  {{ request('status') === 'resolved'  ? 'selected' : '' }}>Resuelto</option>
                                <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Rechazado</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Spam</label>
                            <select name="spam" class="form-select">
                                <option value="0" {{ request('spam', '0') === '0' ? 'selected' : '' }}>No spam</option>
                                <option value="1" {{ request('spam') === '1' ? 'selected' : '' }}>Solo spam</option>
                                <option value=""  {{ request()->has('spam') && request('spam') === '' ? 'selected' : '' }}>Todos (spam + no spam)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lectura</label>
                            <select name="read" class="form-select">
                                <option value="">Leído y no leído</option>
                                <option value="1" {{ request('read') === '1' ? 'selected' : '' }}>Solo leídos</option>
                                <option value="0" {{ request('read') === '0' ? 'selected' : '' }}>Solo no leídos</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Asignado a</label>
                            <select name="assigned_to" class="form-select select2" data-dropdown-parent="#filterModal">
                                <option value="">Todos los usuarios</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rango de fechas</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                <span class="text-muted">—</span>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Aplicar filtros
                        </button>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-secondary w-100">
                            Limpiar filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('core::components.delete')

    {{-- Modal exportar --}}
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('settings.forms.submissions.export', $form) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Exportar submissions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Formato</label>
                            <select name="format" class="form-select">
                                <option value="xlsx">Excel (.xlsx)</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_spam" id="includeSpam" value="1">
                            <label class="form-check-label" for="includeSpam">Incluir spam</label>
                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-download me-1"></i> Exportar
                        </button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const bulkDeleteUrl    = '{{ route('settings.forms.submissions.bulk-delete', $form) }}';
    const bulkAnonymizeUrl = '{{ route('settings.forms.submissions.anonymize', $form) }}';

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
        if (!ids.length) { toastr.warning('Selecciona al menos un envío.'); return; }

        const url = action === 'delete' ? bulkDeleteUrl : bulkAnonymizeUrl;
        const confirmMsg = action === 'delete'
            ? '¿Eliminar los ' + ids.length + ' envío(s) seleccionados?'
            : '¿Anonimizar los datos de los ' + ids.length + ' envío(s) seleccionados?';

        if (!confirm(confirmMsg)) return;

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: url,
            method: 'POST',
            data: { ids: ids },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                const count = res.deleted ?? res.anonymized ?? ids.length;
                toastr.success(count + ' envío(s) procesados correctamente.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $(document).on('click', '.btn-star', function () {
        const $btn = $(this);
        $.ajax({
            url: $btn.data('url'),
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $btn.html(res.is_starred
                    ? '<span class="badge bg-warning text-dark">Destacada</span>'
                    : '<span class="text-muted">—</span>'
                );
            }
        });
    });

    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
