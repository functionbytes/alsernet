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
                                <h4 class="mb-1 fw-bold ">{{ $stats['unread'] }}</h4>
                                <small class="text-muted">Pendientes de revisión</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Spam</h6>
                                <h4 class="mb-1 fw-bold ">{{ $stats['spam'] }}</h4>
                                <small class="text-muted">Marcadas como spam</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con estrella</h6>
                                <h4 class="mb-1 fw-bold" >{{ $stats['starred'] }}</h4>
                                <small class="text-muted">Destacadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.forms.submissions.index', $form) }}" id="filterForm">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar en valores..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <select name="status" class="form-select select2 h-100" onchange="this.form.submit()">
                                <option value="">Todos los estados</option>
                                <option value="new"       {{ request('status') === 'new'       ? 'selected' : '' }}>Nuevo</option>
                                <option value="in_review" {{ request('status') === 'in_review' ? 'selected' : '' }}>En revisión</option>
                                <option value="resolved"  {{ request('status') === 'resolved'  ? 'selected' : '' }}>Resuelto</option>
                                <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Rechazado</option>
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 130px;">
                            <select name="spam" class="form-select select2 h-100" onchange="this.form.submit()">
                                <option value="0" {{ request('spam', '0') === '0' ? 'selected' : '' }}>No spam</option>
                                <option value="1" {{ request('spam') === '1' ? 'selected' : '' }}>Spam</option>
                                <option value=""  {{ request()->has('spam') && request('spam') === '' ? 'selected' : '' }}>Todos</option>
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 130px;">
                            <select name="read" class="form-select select2 h-100" onchange="this.form.submit()">
                                <option value="">Leído / No leído</option>
                                <option value="1" {{ request('read') === '1' ? 'selected' : '' }}>Leído</option>
                                <option value="0" {{ request('read') === '0' ? 'selected' : '' }}>No leído</option>
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <select name="assigned_to" class="form-select select2 h-100" onchange="this.form.submit()">
                                <option value="">Todos los asignados</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-shrink-0 d-flex gap-2 align-items-center">
                            <input type="date" name="date_from" class="form-control" style="width: 140px;"
                                   value="{{ request('date_from') }}" onchange="this.form.submit()">
                            <span class="text-muted">—</span>
                            <input type="date" name="date_to" class="form-control" style="width: 140px;"
                                   value="{{ request('date_to') }}" onchange="this.form.submit()">
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search') || request('status') || request()->has('spam') && request('spam') !== '0' || request('read') !== null && request()->has('read') || request('assigned_to') || request('date_from') || request('date_to'))
                                <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Bulk action bar --}}
            <div id="bulkBar" class="card-body border-bottom py-2 d-none">
                <div class="d-flex align-items-center gap-3">
                    <span class="small fw-semibold"><span id="selectedCount">0</span> seleccionadas</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkDeleteBtn">Eliminar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkAnonymizeBtn">Anonimizar</button>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="card-body p-0">
                @if ($submissions->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-inbox fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay envíos</h6>
                            <p class="text-muted mb-0">No se encontraron resultados para los filtros aplicados</p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 36px;">
                                        <input type="checkbox" class="form-check-input" id="selectAll">
                                    </th>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Datos</th>
                                    <th>Estado</th>
                                    <th>Asignado</th>
                                    <th class="text-center">Leído</th>
                                    <th class="text-center">Destacada</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($submissions as $submission)
                                    <tr>
                                        <td class="ps-3">
                                            <input type="checkbox" class="form-check-input" name="ids[]" value="{{ $submission->id }}">
                                        </td>
                                        <td class="text-muted">#{{ $submission->id }}</td>
                                        <td class="small text-nowrap">{{ $submission->created_at?->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @foreach ($submission->values->take(2) as $value)
                                                <div class="small text-truncate" style="max-width: 200px;" title="{{ $value->field_label }}: {{ $value->getDisplayValue() }}">
                                                    <span class="text-muted">{{ $value->field_label }}:</span>
                                                    {{ Str::limit($value->getDisplayValue(), 40) }}
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'new'       => ['label' => 'Nuevo',       'class' => 'bg-primary'],
                                                    'in_review' => ['label' => 'En revisión',  'class' => 'bg-warning text-dark'],
                                                    'resolved'  => ['label' => 'Resuelto',     'class' => 'bg-success'],
                                                    'rejected'  => ['label' => 'Rechazado',    'class' => 'bg-danger'],
                                                ];
                                                $st = $statusMap[$submission->status ?? 'new'] ?? $statusMap['new'];
                                            @endphp
                                            <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                                        </td>
                                        <td class="small">{{ $submission->assignedTo?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            @if ($submission->is_read)
                                                <i class="fas fa-envelope-open text-muted" title="Leído"></i>
                                            @else
                                                <i class="fas fa-envelope text-primary" title="No leído"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-link p-0 text-decoration-none btn-star"
                                                    data-url="{{ route('settings.forms.submissions.toggle-star', [$form, $submission]) }}">
                                                @if ($submission->is_starred)
                                                    <span class="badge bg-warning text-dark">Destacada</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.submissions.show', [$form, $submission]) }}">Ver</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.forms.submissions.pdf', [$form, $submission]) }}" target="_blank">PDF</a>
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

    @include('core::components.delete')

    {{-- Modal exportar --}}
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download me-1"></i> Exportar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const bulkDeleteUrl    = '{{ route('settings.forms.submissions.bulk-delete', $form) }}';
    const bulkAnonymizeUrl = '{{ route('settings.forms.submissions.anonymize', $form) }}';

    $('#selectAll').on('change', function () {
        $('input[name="ids[]"]').prop('checked', $(this).is(':checked'));
        updateBulkBar();
    });

    $(document).on('change', 'input[name="ids[]"]', updateBulkBar);

    function updateBulkBar() {
        const count = $('input[name="ids[]"]:checked').length;
        $('#bulkBar').toggleClass('d-none', count === 0);
        $('#selectedCount').text(count);
    }

    $('#bulkDeleteBtn').on('click', function () {
        if (!confirm('¿Eliminar las submissions seleccionadas?')) return;
        const ids = $('input[name="ids[]"]:checked').map(function () { return $(this).val(); }).get();
        $.ajax({
            url: bulkDeleteUrl,
            method: 'POST',
            data: { ids: ids },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.deleted + ' submissions eliminadas');
                location.reload();
            },
            error: function () { toastr.error('Error al eliminar'); }
        });
    });

    $('#bulkAnonymizeBtn').on('click', function () {
        if (!confirm('¿Anonimizar los datos personales de las submissions seleccionadas?')) return;
        const ids = $('input[name="ids[]"]:checked').map(function () { return $(this).val(); }).get();
        $.ajax({
            url: bulkAnonymizeUrl,
            method: 'POST',
            data: { ids: ids },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.anonymized + ' submissions anonimizadas');
                location.reload();
            },
            error: function () { toastr.error('Error al anonimizar'); }
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
</script>
@endpush
