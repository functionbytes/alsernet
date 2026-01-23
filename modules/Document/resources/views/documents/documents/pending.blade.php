@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Documentos'])

    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="form-search" action="{{ route('documents.index') }}" method="GET">
                        <div class="row justify-content-between g-2 ">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i data-feather="search"></i></span>
                                        <input class="form-control rounded-start w-100" type="text" id="search" name="search" placeholder="Buscar por ID, referencia, nombre, apellido o DNI..." @isset($searchKey) value="{{ $searchKey }}" @endisset>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <select class="form-select select2 select2" name="status_id">
                                        <option value="">Todos los estados</option>
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}" {{ ($statusId ?? '') == $status->id ? 'selected' : '' }}>
                                                {{ $status->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <select class="form-select select2 select2" name="load_id">
                                        <option value="">Todos los orígenes</option>
                                        @foreach($loads as $load)
                                            <option value="{{ $load->id }}" {{ ($loadId ?? '') == $load->id ? 'selected' : '' }}>
                                                {{ $load->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <input type="text" class="form-control daterange" id="daterange" placeholder="Rango de fechas"
                                           value="{{ ($dateFrom && $dateTo) ? $dateFrom . ' - ' . $dateTo : '' }}"
                                           autocomplete="off">
                                    <input type="hidden" id="date_from" name="date_from" value="{{ $dateFrom ?? '' }}">
                                    <input type="hidden" id="date_to" name="date_to" value="{{ $dateTo ?? '' }}">
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('documents.index') }}" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Limpiar filtros">
                                    <i class="fa-duotone fa-xmark"></i>
                                </a>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('documents.import') }}" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Importar órdenes específicas">
                                    <i class="fa-duotone fa-file-import"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card card-body">
            <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap">
                    <thead class="header-item">
                    <tr>
                        <th>Orden ID</th>
                        <th>Referencia</th>
                        <th>Cliente</th>
                        <th>Origen</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($documents as $key => $document)
                        <tr class="search-items">
                            <td>
                                <strong>{{ $document->order_id }}</strong>
                            </td>
                            <td>
                                {{ $document->order_reference ?? '-' }}
                            </td>
                            <td>
                                {{ $document->customer_firstname }} {{ $document->customer_lastname }}
                            </td>
                            <td>
                                @if($document->documentLoad)
                                    <span class="badge bg-light-info text-info">{{ $document->documentLoad->label }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($document->status)
                                    <span class="badge bg-light-primary text-primary">{{ $document->status->label }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="usr-ph-no">{{ $document->created_at->format('Y-m-d H:i') }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('documents.show', $document->uid) }}">
                                                Ver
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('documents.manage', $document->uid) }}">
                                                Gestionar
                                            </a>
                                        </li>
                                        @if($document->mails()->count() > 0)
                                            <li class="border-top my-2"></li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('api.documents.emails', $document->uid) }}">
                                                    Ver emails
                                                </a>
                                            </li>
                                        @endif

                                        @if($document->validationHistory()->count() > 1 && auth()->user()->canDocument('route-history-validation'))
                                            <li class="border-top my-2"></li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('documents.validation-history', $document->uid) }}">
                                                    <i class="fas fa-history me-2"></i>Historial de validación
                                                </a>
                                            </li>
                                        @endif

                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="result-body ">
                <span>Mostrar {{ $documents->firstItem() }}-{{ $documents->lastItem() }} de {{ $documents->total() }} resultados</span>
                <nav>
                    {{ $documents->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ url('theme/libs/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ url('theme/libs/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ===== Daterange Picker =====
            if ($('#daterange').length && typeof $.fn.daterangepicker !== 'undefined') {
                $('#daterange').daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD',
                        separator: ' - ',
                        applyLabel: 'Aplicar',
                        cancelLabel: 'Limpiar',
                        fromLabel: 'Desde',
                        toLabel: 'Hasta',
                        customRangeLabel: 'Personalizado',
                        weekLabel: 'S',
                        daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                        firstDay: 1
                    }
                });

                $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                    $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
                    $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
                });

                $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                    $('#date_from').val('');
                    $('#date_to').val('');
                });
            }
        });
    </script>
@endpush


