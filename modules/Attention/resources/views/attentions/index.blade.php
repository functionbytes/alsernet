@extends('layouts.theme')

@section('title', 'PQRSF Pendientes')

@section('content')

    @include('core::components.card', ['title' => 'PQRSF Pendientes'])

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title mb-2">Total pendientes</h6>
                            <h2 >{{ $stats['total'] ?? 0 }}</h2>
                            <small class="text-muted">PQRSF sin finalizar</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title mb-2">Hoy</h6>
                            <h2 >{{ $stats['today'] ?? 0 }}</h2>
                            <small class="text-muted">Registrados hoy</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title mb-2">Vencidos</h6>
                            <h2 >{{ $stats['overdue'] ?? 0 }}</h2>
                            <small class="text-muted">Fuera de tiempo</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title mb-2">Asignados a mí</h6>
                            <h2 >{{ $stats['assigned_to_me'] ?? 0 }}</h2>
                            <small class="text-muted">En mi bandeja</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="widget-content searchable-container list">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Filtros de búsqueda</h6>
                        <p class="small mb-0 text-muted">Encuentra PQRSF usando múltiples criterios de filtrado</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form class="form-search" action="{{ route('attention.pending') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input class="form-control border-start-0" type="text" id="search" name="search"
                                       placeholder="Buscar por radicado, nombre, apellido, DNI o asunto..."
                                       @isset($searchKey) value="{{ $searchKey }}" @endisset>
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <select class="form-select select2" name="type_id">
                                <option value="">Todos los tipos</option>
                                @foreach($types ?? [] as $type)
                                    <option value="{{ $type->id }}" {{ ($typeId ?? '') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <select class="form-select select2" name="category_id">
                                <option value="">Todas las categorías</option>
                                @foreach($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" {{ ($categoryId ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <select class="form-select select2" name="department_id">
                                <option value="">Todos los departamentos</option>
                                @foreach($departments ?? [] as $department)
                                    <option value="{{ $department->id }}" {{ ($departmentId ?? '') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <select class="form-select select2" name="sede_id">
                                <option value="">Todas las sedes</option>
                                @foreach($sedes ?? [] as $sede)
                                    <option value="{{ $sede->id }}" {{ ($sedeId ?? '') == $sede->id ? 'selected' : '' }}>
                                        {{ $sede->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <input type="text" class="form-control daterange" id="daterange" placeholder="Rango de fechas"
                                   value="{{ ($dateFrom && $dateTo) ? $dateFrom . ' - ' . $dateTo : '' }}"
                                   autocomplete="off">
                            <input type="hidden" id="date_from" name="date_from" value="{{ $dateFrom ?? '' }}">
                            <input type="hidden" id="date_to" name="date_to" value="{{ $dateTo ?? '' }}">
                        </div>
                        <div class="col-md-4 col-lg-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill" data-bs-toggle="tooltip" data-bs-placement="top" title="Buscar">
                                <i class="fa fa-search me-1"></i>
                            </button>
                            <a href="{{ route('attention.pending') }}" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Limpiar filtros">
                                <i class="fa fa-xmark"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap">
                        <thead class="table-light">
                        <tr>
                            <th>Radicado</th>
                            <th>Tipo</th>
                            <th>Ciudadano</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                            <th>Asignado a</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($attentions ?? [] as $attention)
                            <tr>
                                <td>
                                    <strong class="text-primary">{{ $attention->radicado }}</strong>
                                </td>
                                <td>
                                    @if($attention->type)
                                        <span class="badge bg-info-subtle text-info">{{ $attention->type->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $attention->full_name }}</strong>
                                        @if($attention->customer_email)
                                            <br><small class="text-muted">{{ $attention->customer_email }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                        <span title="{{ $attention->subject }}">
                                            {{ \Illuminate\Support\Str::limit($attention->subject, 40) }}
                                        </span>
                                </td>
                                <td>
                                        <span class="badge bg-sm bg-success-subtle text-white me-1">
                                            {{ $attention->status->label() }}
                                        </span>
                                </td>
                                <td>
                                    @if($attention->assigned_user_id)
                                        <span class="badge bg-success-subtle text-success">
                                                {{ $attention->assignedUser->name }}
                                            </span>
                                    @elseif($attention->department_id)
                                        <span class="badge bg-warning-subtle text-warning">
                                                {{ $attention->department->name }}
                                            </span>
                                    @else
                                        <span class="text-muted">Sin asignar</span>
                                    @endif
                                </td>2
                                <td>
                                    {{ $attention->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('attention.show', $attention->uid) }}">
                                                    Ver detalles
                                                </a>
                                            </li>
                                            @if($attention->canBeEdited())
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('attention.edit', $attention->uid) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('attention.tracking', $attention->radicado) }}">
                                                    Seguimiento
                                                </a>
                                            </li>
                                            @if($attention->mails()->count() > 0)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('attention.emails.index', $attention->uid) }}">
                                                        Ver emails
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fa fa-inbox fa-3x mb-3"></i>
                                        <p>No se encontraron PQRSF pendientes</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        @if(isset($attentions) && $attentions->hasPages())
            <div class="card card-body">
                {{ $attentions->links() }}
            </div>
        @endif
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '200px'
            });

            $('.daterange').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Limpiar',
                    applyLabel: 'Aplicar',
                    format: 'DD/MM/YYYY',
                    separator: ' - ',
                    fromLabel: 'Desde',
                    toLabel: 'Hasta',
                    customRangeLabel: 'Personalizado',
                    daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                    monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    firstDay: 1
                },
                ranges: {
                    'Hoy': [moment(), moment()],
                    'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
                    'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
                    'Este mes': [moment().startOf('month'), moment().endOf('month')],
                    'Mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            });

            $('.daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
                $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
            });

            $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $('#date_from').val('');
                $('#date_to').val('');
            });

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
@endpush
