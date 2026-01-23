@extends('layouts.theme')

@section('title', 'Configuracion de Documentos')

@section('content')

    @include('core::components.card', ['title' => 'Configuracion de Documentos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Header Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1">Ajustes del Sistema de Documentos</h5>
                        <p class="text-muted mb-0">Configura el comportamiento general, notificaciones por email y politicas SLA para la gestion de documentos</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="btnResetDefaults" data-bs-toggle="tooltip" title="Restaurar valores Por defecto">
                            <i class="ti ti-refresh me-1"></i> Restaurar
                        </button>
                        <button type="submit" form="documentSettingsForm" class="btn btn-primary" id="btnSaveSettings" disabled>
                            <i class="ti ti-device-floppy me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card bg-light-primary stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="round-48 rounded-circle bg-primary d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="ti ti-file-text text-white fs-5"></i>
                            </div>
                            <div>
                                <h6 class="card-title text-primary mb-1">Total Procesados</h6>
                                <h3 class="mb-0 fw-bold">{{ number_format($documentStats['total_processed'] ?? 0) }}</h3>
                                <small class="text-muted">Documentos procesados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card bg-light-success stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="round-48 rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="ti ti-chart-pie text-white fs-5"></i>
                            </div>
                            <div>
                                <h6 class="card-title text-success mb-1">Cumplimiento SLA</h6>
                                <h3 class="mb-0 fw-bold">{{ number_format($slaStats['compliance_rate'] ?? 0, 1) }}%</h3>
                                <small class="text-muted">Tasa de cumplimiento</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card bg-light-warning stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="round-48 rounded-circle bg-warning d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="ti ti-clock-hour-4 text-white fs-5"></i>
                            </div>
                            <div>
                                <h6 class="card-title text-warning mb-1">Politicas SLA</h6>
                                <h3 class="mb-0 fw-bold">{{ $slaStats['active_policies'] ?? 0 }}</h3>
                                <small class="text-muted">Politicas activas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card bg-light-danger stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="round-48 rounded-circle bg-danger d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="ti ti-alert-triangle text-white fs-5"></i>
                            </div>
                            <div>
                                <h6 class="card-title text-danger mb-1">Escalamientos</h6>
                                <h3 class="mb-0 fw-bold">{{ $slaStats['escalations_30_days'] ?? 0 }}</h3>
                                <small class="text-muted">Ultimos 30 dias</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Settings Card with Tabs -->
        <div class="card">
            <div class="card-header border-bottom p-0">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs nav-fill" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-content" type="button" role="tab" aria-controls="general-content" aria-selected="true">
                            <i class="ti ti-settings me-2"></i>
                            <span class="d-none d-sm-inline">Configuracion General</span>
                            <span class="d-sm-none">General</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-content" type="button" role="tab" aria-controls="email-content" aria-selected="false">
                            <i class="ti ti-mail me-2"></i>
                            <span class="d-none d-sm-inline">Notificaciones Email</span>
                            <span class="d-sm-none">Email</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3" id="sla-tab" data-bs-toggle="tab" data-bs-target="#sla-content" type="button" role="tab" aria-controls="sla-content" aria-selected="false">
                            <i class="ti ti-clock me-2"></i>
                            <span class="d-none d-sm-inline">Politicas SLA</span>
                            <span class="d-sm-none">SLA</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('settings.documents.settings.update') }}" id="documentSettingsForm">
                    @csrf
                    @method('PUT')

                    <!-- Tab Content -->
                    <div class="tab-content" id="settingsTabContent">
                        <!-- General Settings Tab -->
                        <div class="tab-pane fade show active" id="general-content" role="tabpanel" aria-labelledby="general-tab">
                            @include('documents::settings.settings.sections.general')
                        </div>

                        <!-- Email Settings Tab -->
                        <div class="tab-pane fade" id="email-content" role="tabpanel" aria-labelledby="email-tab">
                            @include('documents::settings.settings.sections.email')
                        </div>

                        <!-- SLA Settings Tab -->
                        <div class="tab-pane fade" id="sla-content" role="tabpanel" aria-labelledby="sla-tab">
                            @include('documents::settings.settings.sections.sla')
                        </div>
                    </div>
                </form>
            </div>

            <!-- Card Footer -->
            <div class="card-footer bg-light border-top">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                    <div class="text-muted small">
                        <i class="ti ti-info-circle me-1"></i>
                        Los cambios se aplicaran inmediatamente despues de guardar
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.documents.configurations') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Volver
                        </a>
                        <button type="submit" form="documentSettingsForm" class="btn btn-primary" id="btnSaveSettingsFooter" disabled>
                            <i class="ti ti-device-floppy me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Reset Defaults Modal -->
    <div class="modal fade" id="resetDefaultsModal" tabindex="-1" aria-labelledby="resetDefaultsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="resetDefaultsModalLabel">
                        <i class="ti ti-alert-triangle text-warning me-2"></i>
                        Restaurar Valores Por defecto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Esta accion restaurara todas las configuraciones a sus valores Por defecto. Esta accion no puede deshacerse.</p>
                    <div class="alert alert-warning mb-0">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>Nota:</strong> Las politicas SLA existentes no seran eliminadas, solo se restauraran los ajustes generales.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="btnConfirmReset">
                        <i class="ti ti-refresh me-1"></i> Restaurar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Preview Modal -->
    <div class="modal fade" id="emailPreviewModal" tabindex="-1" aria-labelledby="emailPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="emailPreviewModalLabel">
                        <i class="ti ti-mail me-2"></i>
                        Vista Previa del Email
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="emailPreviewContent">
                        <!-- Dynamic email preview content -->
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnSendTestEmail">
                        <i class="ti ti-send me-1"></i> Enviar Email de Prueba
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .round-48 {
        width: 48px;
        height: 48px;
    }
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #6c757d;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    .nav-tabs .nav-link:hover {
        color: #90bb13;
        border-bottom-color: rgba(144, 187, 19, 0.3);
    }
    .nav-tabs .nav-link.active {
        color: #90bb13;
        border-bottom-color: #90bb13;
        background: transparent;
    }
    .setting-section {
        border-left: 3px solid #e9ecef;
        padding-left: 1rem;
        margin-bottom: 1.5rem;
    }
    .setting-section.active {
        border-left-color: #90bb13;
    }
    .form-check-input:checked {
        background-color: #90bb13;
        border-color: #90bb13;
    }
    .input-validation-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
    }
    .input-validation-icon.valid {
        display: block;
        color: #13C672;
    }
    .input-validation-icon.invalid {
        display: block;
        color: #FA896B;
    }
    .char-counter {
        font-size: 0.75rem;
        color: #6c757d;
    }
    .char-counter.warning {
        color: #FEC90F;
    }
    .char-counter.danger {
        color: #FA896B;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: inherit;
    }
    .btn-primary {
        background-color: #90bb13;
        border-color: #90bb13;
    }
    .btn-primary:hover {
        background-color: #7da310;
        border-color: #7da310;
    }
    .btn-primary:disabled {
        background-color: #90bb13;
        border-color: #90bb13;
        opacity: 0.65;
    }
    .bg-light-primary { background-color: rgba(144, 187, 19, 0.1) !important; }
    .bg-light-success { background-color: rgba(19, 198, 114, 0.1) !important; }
    .bg-light-warning { background-color: rgba(254, 201, 15, 0.1) !important; }
    .bg-light-danger { background-color: rgba(250, 137, 107, 0.1) !important; }
    .text-primary { color: #90bb13 !important; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    const form = $('#documentSettingsForm');
    const saveBtns = $('#btnSaveSettings, #btnSaveSettingsFooter');
    let originalFormData = form.serialize();

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Form dirty detection
    function checkFormDirty() {
        const currentFormData = form.serialize();
        const isDirty = originalFormData !== currentFormData;
        saveBtns.prop('disabled', !isDirty);

        if (isDirty) {
            saveBtns.removeClass('btn-secondary').addClass('btn-primary');
        }
    }

    // Monitor all form inputs for changes
    form.on('change input', 'input, select, textarea', function() {
        checkFormDirty();
    });

    // Character counter for textareas
    $('textarea[data-max-chars]').each(function() {
        const textarea = $(this);
        const maxChars = parseInt(textarea.data('max-chars'));
        const counterId = textarea.attr('id') + '-counter';

        // Create counter element if not exists
        if (!$('#' + counterId).length) {
            textarea.after('<div class="char-counter" id="' + counterId + '"><span class="current">0</span> / ' + maxChars + ' caracteres</div>');
        }

        function updateCounter() {
            const currentLength = textarea.val().length;
            const counter = $('#' + counterId);
            counter.find('.current').text(currentLength);

            counter.removeClass('warning danger');
            if (currentLength > maxChars * 0.9) {
                counter.addClass('danger');
            } else if (currentLength > maxChars * 0.75) {
                counter.addClass('warning');
            }
        }

        textarea.on('input', updateCounter);
        updateCounter();
    });

    // Form submission with AJAX
    form.on('submit', function(e) {
        e.preventDefault();

        saveBtns.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> Guardando...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: form.serialize(),
            headers: {
                'X-HTTP-Method-Override': 'PUT'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Configuracion guardada correctamente', 'Exito');
                    originalFormData = form.serialize();
                    saveBtns.html('<i class="ti ti-device-floppy me-1"></i> Guardar Cambios');
                    checkFormDirty();
                } else {
                    toastr.error(response.message || 'Error al guardar la configuracion', 'Error');
                    saveBtns.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Guardar Cambios');
                }
            },
            error: function(xhr) {
                saveBtns.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Guardar Cambios');

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessages = [];
                    for (let field in errors) {
                        errorMessages.push(errors[field][0]);
                    }
                    toastr.error(errorMessages.join('<br>'), 'Error de validacion');
                } else {
                    const error = xhr.responseJSON?.message || 'Error al guardar la configuracion';
                    toastr.error(error, 'Error');
                }
            }
        });
    });

    // Reset defaults button
    $('#btnResetDefaults').on('click', function() {
        $('#resetDefaultsModal').modal('show');
    });

    $('#btnConfirmReset').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> Restaurando...');

        $.ajax({
            url: '{{ route("settings.documents.settings.reset-defaults") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Valores restaurados correctamente', 'Exito');
                    $('#resetDefaultsModal').modal('hide');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Error al restaurar valores', 'Error');
                    btn.prop('disabled', false).html('<i class="ti ti-refresh me-1"></i> Restaurar');
                }
            },
            error: function(xhr) {
                toastr.error('Error al restaurar valores Por defecto', 'Error');
                btn.prop('disabled', false).html('<i class="ti ti-refresh me-1"></i> Restaurar');
            }
        });
    });

    // Toastr notifications for session messages
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Tab persistence
    const activeTab = localStorage.getItem('documentSettingsActiveTab');
    if (activeTab) {
        $('#settingsTabs button[data-bs-target="' + activeTab + '"]').tab('show');
    }

    $('#settingsTabs button').on('shown.bs.tab', function (e) {
        localStorage.setItem('documentSettingsActiveTab', $(e.target).data('bs-target'));
    });
});
</script>
@endpush
