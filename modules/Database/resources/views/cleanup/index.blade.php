@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' => 'Limpieza de base de datos'])
@endsection

@section('content')

        @include('core::components.alerts')

        <!-- Warning Alert -->
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="alert alert-dismissible fade show border-0 bg-warning-subtle" role="alert">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas fa-triangle-exclamation text-warning fs-9"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading fw-bold text-warning mb-1">
                                Atención: operación destructiva e irreversible
                            </h6>
                            <p class="mb-1 text-warning">
                                Esta operación eliminará permanentemente todos los registros de las tablas seleccionadas.
                                <strong>No podrás recuperar los datos eliminados.</strong>
                                Se creará un backup automático antes de ejecutar la limpieza.
                            </p>
                            <p class="mb-0 text-warning">
                                <strong>Tablas protegidas</strong> (no se pueden vaciar):
                                @foreach($protectedTables as $pt)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">{{ $pt }}</span>
                                @endforeach
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>

        <form id="cleanupForm">
            @csrf
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="mb-4">
                                <div class="d-flex no-block align-items-center">
                                    <div>
                                        <h5 class="m-0">Selecciona las tablas a limpiar</h5>
                                        <p class="card-subtitle m-0">
                                            Las tablas marcadas como protegidas no son seleccionables.
                                        </p>
                                    </div>
                                    <div class="ms-auto d-flex gap-2 align-items-center">
                                        <div class="btn-group" role="group">
                                            <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-secondary">
                                                Todos
                                            </button>
                                            <button type="button" id="deselectAllBtn" class="btn btn-sm btn-outline-secondary">
                                                Ninguno
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Stats -->
                            <div class="row mb-4 g-3">
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title mb-2">Total de tablas</h6>
                                            <h2>{{ count($tables) }}</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title mb-2">Registros totales</h6>
                                            <h2 id="totalRecords">{{ array_sum(array_column($tables, 'records')) }}</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title mb-2">Seleccionadas</h6>
                                            <h2 id="selectedCount">0</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title mb-2">A eliminar</h6>
                                            <h2 id="recordsToDelete">0</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Search -->
                            <div class="mb-4">
                                <input type="text" class="form-control" id="tableSearch" placeholder="Buscar tabla...">
                            </div>

                            <!-- Tables List -->
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">
                                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox">
                                            </th>
                                            <th>Nombre de la tabla</th>
                                            <th style="width: 150px;" class="text-end">Registros</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablesList">
                                        @foreach($tables as $table)
                                            <tr class="table-row {{ $table['protected'] ? 'table-secondary' : '' }}"
                                                data-table-name="{{ $table['name'] }}"
                                                data-records="{{ $table['records'] }}">
                                                <td>
                                                    @if($table['protected'])
                                                        <i class="fas fa-lock text-muted" title="Tabla protegida"></i>
                                                    @else
                                                        <input type="checkbox" class="form-check-input table-checkbox"
                                                               name="tables[]" value="{{ $table['name'] }}">
                                                    @endif
                                                </td>
                                                <td>
                                                    <code class="bg-light-secondary p-2 rounded small text-black">{{ $table['name'] }}</code>
                                                    @if($table['protected'])
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">Protegida</span>
                                                    @elseif($table['records'] === 0)
                                                        <span class="badge bg-success ms-2">Vacía</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-light-secondary text-dark table-record-count">{{ $table['records'] }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-primary w-100 mb-1" id="cleanupBtn" disabled>
                                    Limpiar tablas seleccionadas
                                </button>
                                <a href="{{ route('settings.database.index') }}" class="btn btn-secondary w-100">
                                    Cancelar
                                </a>
                            </div>

                            @if(empty($tables))
                                <div class="alert alert-info mt-3" role="alert">
                                    <i class="fas fa-circle-info"></i> No hay tablas disponibles.
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </form>


    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirmación de limpieza</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        <strong>Esta acción es irreversible.</strong> Se eliminarán todos los registros de las siguientes tablas:
                    </p>
                    <div class="bg-light-secondary p-3 rounded mb-3" style="max-height: 250px; overflow-y: auto;">
                        <ul id="tablesToDeleteList" class="mb-0"></ul>
                    </div>
                    <p class="mb-3">
                        Total de registros a eliminar: <strong id="totalToDelete" class="text-danger">0</strong>
                    </p>

                    <!-- Password confirmation -->
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirma tu contraseña</label>
                        <input type="password" class="form-control" id="confirmPassword" placeholder="Tu contraseña actual">
                        <div id="passwordError" class="invalid-feedback"></div>
                    </div>

                    <!-- Risk acknowledgement -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmCheckbox">
                        <label class="form-check-label" for="confirmCheckbox">
                            Entiendo que esto eliminará todos los datos de forma permanente y que se creará un backup automático antes de continuar
                        </label>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="confirmCleanupBtn" disabled>
                        Sí, limpiar ahora
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Limpieza completada</h5>
                </div>
                <div class="modal-body">
                    <p id="successMessage" class="mb-2"></p>
                    <p class="text-muted mb-0">
                        Los registros de las tablas seleccionadas han sido eliminados permanentemente.
                        Esta operación no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    var confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    var successModal = new bootstrap.Modal(document.getElementById('successModal'));

    function updateCounts() {
        var selected = $('.table-checkbox:checked');
        var recordsToDelete = 0;

        selected.each(function () {
            recordsToDelete += parseInt($(this).closest('.table-row').data('records'));
        });

        $('#selectedCount').text(selected.length);
        $('#recordsToDelete').text(recordsToDelete.toLocaleString());
        $('#cleanupBtn').prop('disabled', selected.length === 0);
        $('#selectAllCheckbox').prop('checked', selected.length > 0 && selected.length === $('.table-checkbox').length);
    }

    function resetModal() {
        $('#confirmPassword').val('').removeClass('is-invalid');
        $('#passwordError').text('');
        $('#confirmCheckbox').prop('checked', false);
        $('#confirmCleanupBtn').prop('disabled', true);
    }

    function canSubmit() {
        return $('#confirmCheckbox').is(':checked') && $('#confirmPassword').val().length > 0;
    }

    $('#selectAllBtn').on('click', function () {
        $('.table-checkbox').prop('checked', true);
        updateCounts();
    });

    $('#deselectAllBtn').on('click', function () {
        $('.table-checkbox').prop('checked', false);
        updateCounts();
    });

    $('#selectAllCheckbox').on('change', function () {
        $('.table-row:visible .table-checkbox').prop('checked', $(this).is(':checked'));
        updateCounts();
    });

    $(document).on('change', '.table-checkbox', updateCounts);

    $('#tableSearch').on('input', function () {
        var term = $(this).val().toLowerCase();
        $('.table-row').each(function () {
            $(this).toggle($(this).data('table-name').toLowerCase().indexOf(term) !== -1);
        });
    });

    $('#cleanupForm').on('submit', function (e) {
        e.preventDefault();

        var selectedTables = [];
        var totalRecords = 0;

        $('.table-checkbox:checked').each(function () {
            var records = parseInt($(this).closest('.table-row').data('records'));
            selectedTables.push({ name: $(this).val(), records: records });
            totalRecords += records;
        });

        var $list = $('#tablesToDeleteList').empty();
        $.each(selectedTables, function (i, table) {
            $list.append('<li><strong>' + table.name + '</strong> — <span class="text-muted">' + table.records.toLocaleString() + ' registros</span></li>');
        });

        $('#totalToDelete').text(totalRecords.toLocaleString());
        resetModal();
        confirmationModal.show();
    });

    $('#confirmCheckbox, #confirmPassword').on('change keyup', function () {
        $('#confirmCleanupBtn').prop('disabled', !canSubmit());
    });

    $('#confirmCleanupBtn').on('click', function () {
        var tables = $('.table-checkbox:checked').map(function () { return $(this).val(); }).get();
        var password = $('#confirmPassword').val();

        $('#confirmCleanupBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Limpiando...');
        $('#confirmPassword').removeClass('is-invalid');

        $.ajax({
            url: '{{ route("settings.database.cleanup.truncate") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            contentType: 'application/json',
            data: JSON.stringify({ tables: tables, password: password, confirmed: true }),
            success: function (data) {
                confirmationModal.hide();
                $('#successMessage').text(data.message);
                successModal.show();
                setTimeout(function () { location.reload(); }, 3000);
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};

                if (xhr.status === 422 && response.errors) {
                    if (response.errors.password) {
                        $('#confirmPassword').addClass('is-invalid');
                        $('#passwordError').text(response.errors.password[0]);
                    } else {
                        confirmationModal.hide();
                        toastr.error(response.message || 'Error de validación');
                    }
                } else {
                    confirmationModal.hide();
                    toastr.error(response.message || 'Error en la solicitud');
                }
            },
            complete: function () {
                $('#confirmCleanupBtn').prop('disabled', !canSubmit()).html('Sí, limpiar ahora');
            }
        });
    });

    updateCounts();
});
</script>
@endpush
