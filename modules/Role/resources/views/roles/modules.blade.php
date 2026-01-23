@extends('layouts.theme')

@section('content')
<div class="container-fluid">

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Gestionar módulos del rol',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Roles', 'url' => route('settings.roles.index')],
            ['label' => $role->name, 'url' => route('settings.roles.edit', $role->id)],
            ['label' => 'Módulos', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Módulos visibles para: {{ $role->name }}</h5>
                        <p class="small mb-0 text-muted">Selecciona los módulos que este rol puede visualizar en el panel de navegación</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.roles.edit', $role->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Statistics Cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    @php
                        $totalModules = count($modules);
                        $enabledModules = count($roleModules);
                        $disabledModules = $totalModules - $enabledModules;
                        $coveragePercentage = $totalModules > 0 ? round(($enabledModules / $totalModules) * 100) : 0;
                    @endphp
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $totalModules }}</h4>
                                        <small class="text-muted">Módulos disponibles</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Habilitados</h6>
                                        <h4 class="mb-1 fw-bold module-total-count">{{ $enabledModules }}</h4>
                                        <small class="text-muted">Módulos activos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Deshabilitados</h6>
                                        <h4 class="mb-1 fw-bold module-disabled-count">{{ $disabledModules }}</h4>
                                        <small class="text-muted">Módulos inactivos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Cobertura</h6>
                                        <h4 class="mb-1 fw-bold module-percentage">{{ $coveragePercentage }}%</h4>
                                        <small class="text-muted">Módulos asignados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Control de visibilidad de módulos</h6>
                            <p class="mb-0">
                                Los usuarios con este rol solo verán los módulos habilitados aquí en su panel de navegación.
                                Los super-admin siempre tienen acceso a todos los módulos del sistema.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if ($errors->any())
                <div class="card-body border-bottom">
                    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-exclamation-circle fs-4 me-2 mt-1"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-2">Errores de validación</h6>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="card-body border-bottom">
                    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-check fs-4 me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @include('core::components.alerts')

            <form id="formModules" enctype="multipart/form-data" onSubmit="return false">
                @csrf
                <input type="hidden" name="role_id" value="{{ $role->id }}">

                {{-- Quick Actions Bar --}}
                <div class="card-body border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-semibold">Acciones rápidas</h6>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="selectAll">
                                Seleccionar todo
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAll">
                                Deseleccionar todo
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Modules Grid --}}
                @if(count($modules) > 0)
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($modules as $moduleId => $moduleName)
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-check p-3 border rounded module-checkbox {{ in_array($moduleId, $roleModules) ? 'border-primary' : '' }}"
                                         style="{{ in_array($moduleId, $roleModules) ? 'background-color: rgba(144, 187, 19, 0.02);' : '' }}">
                                        <input class="form-check-input module-input" type="checkbox" name="modules[]"
                                               value="{{ $moduleId }}" id="module_{{ $moduleId }}"
                                               {{ in_array($moduleId, $roleModules) ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="module_{{ $moduleId }}" style="cursor: pointer;">
                                            <strong class="d-block">{{ $moduleName }}</strong>
                                            <small class="text-muted d-block mt-1">Módulo del sistema</small>
                                            <small class="badge bg-light text-dark mt-2">{{ strtolower(str_replace(' ', '-', $moduleName)) }}</small>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="text-center py-5">
                            <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                            <h5 class="fw-bold mb-2">No hay módulos disponibles</h5>
                            <p class="text-muted mb-4">
                                No se encontraron módulos configurados en el sistema.
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Footer Actions --}}
                <div class="card-footer bg-white border-top">
                            <button type="submit" class="btn btn-primary w-100 mb-1">
                                Guardar
                            </button>
                            <a href="{{ route('settings.roles.edit', $role->id) }}" class="btn btn-secondary w-100">
                                Cancelar
                            </a>
                </div>
            </form>

        </div>

    </div>

</div>

@endsection

@push('styles')
<style>
    .module-checkbox {
        transition: all 0.2s ease;
        cursor: pointer;
        border: 2px solid #dee2e6;
        position: relative;
    }

    /* Hide the checkbox input but keep it functional */
    .module-checkbox.form-check .form-check-input.module-input,
    .module-checkbox .module-input {
        position: absolute !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
        visibility: hidden !important;
    }

    /* Normal hover effect */
    .module-checkbox:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: #adb5bd;
    }

    /* Checked state - green theme */
    .module-checkbox.border-primary {
        background-color: rgba(144, 187, 19, 0.05);
        border-color: #90bb13 !important;
    }

    /* Checked hover - enhanced green effect */
    .module-checkbox.border-primary:hover {
        background-color: rgba(144, 187, 19, 0.1);
        border-color: #7a9e11 !important;
        box-shadow: 0 4px 12px rgba(144, 187, 19, 0.3);
    }

    /* Visual indicator for checked state */
    .module-checkbox.border-primary::before {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background-color: #90bb13;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    /* Unchecked state visual indicator */
    .module-checkbox:not(.border-primary)::before {
        content: '';
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background-color: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .module-checkbox:not(.border-primary):hover::before {
        border-color: #adb5bd;
        background-color: #e9ecef;
    }

    /* Make label non-selectable */
    .module-checkbox .form-check-label {
        user-select: none;
        padding-right: 40px; /* Space for the check indicator */
    }

    .form-check-input:checked {
        background-color: #90bb13;
        border-color: #90bb13;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Update statistics when checkboxes change
    function updateStats() {
        const totalModules = {{ count($modules) }};
        const enabledCount = $('.module-input:checked').length;
        const disabledCount = totalModules - enabledCount;
        const percentage = totalModules > 0 ? Math.round((enabledCount / totalModules) * 100) : 0;

        $('.module-total-count').text(enabledCount);
        $('.module-disabled-count').text(disabledCount);
        $('.module-percentage').text(percentage + '%');
    }

    // Update visual feedback for checkboxes
    function updateVisualFeedback() {
        $('.module-checkbox').each(function() {
            const $card = $(this);
            const $input = $card.find('.module-input');

            if ($input.is(':checked')) {
                $card.addClass('border-primary');
                $card.css('background-color', 'rgba(144, 187, 19, 0.05)');
            } else {
                $card.removeClass('border-primary');
                $card.css('background-color', '');
            }
        });
    }

    // Make entire card clickable
    $('.module-checkbox').on('click', function(e) {
        // Don't toggle if clicking directly on the checkbox
        if ($(e.target).hasClass('module-input')) {
            return;
        }

        const $checkbox = $(this).find('.module-input');
        $checkbox.prop('checked', !$checkbox.prop('checked'));
        updateStats();
        updateVisualFeedback();
    });

    // Handle checkbox changes
    $('.module-input').on('change', function() {
        updateStats();
        updateVisualFeedback();
    });

    // Select All
    $('#selectAll').on('click', function() {
        $('.module-input').prop('checked', true);
        updateStats();
        updateVisualFeedback();
    });

    // Deselect All
    $('#deselectAll').on('click', function() {
        $('.module-input').prop('checked', false);
        updateStats();
        updateVisualFeedback();
    });

    // Form submission
    $('#formModules').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $(this).find('button[type="submit"]');
        const originalHtml = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

        $.ajax({
            url: "{{ route('settings.roles.update.modules', $role->id) }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                toastr.success(response.message || 'Módulos actualizados correctamente', 'Éxito', {
                    positionClass: 'toast-bottom-right'
                });
                setTimeout(function() {
                    window.location.href = "{{ route('settings.roles.edit', $role->id) }}";
                }, 1500);
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(originalHtml);

                let errorMessage = 'Error al actualizar módulos. Intente nuevamente.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                toastr.error(errorMessage, 'Error', {
                    positionClass: 'toast-bottom-right'
                });

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, value) {
                        toastr.error(value[0], 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            }
        });
    });
});
</script>
@endpush
