{{-- Validation Workflow Sidebar --}}
@php
    $hasWorkflow = $document->total_stages > 0;
    $isInValidation = in_array($document->validation_status, ['pending', 'in_validation']);

    // Verificar si el usuario está en el grupo de validadores actual
    $userIsInValidatorGroup = false;
    if ($isInValidation && $document->current_validator_group) {
        $userIsInValidatorGroup = auth()->user()->documentValidatorGroups()
            ->where('key', $document->current_validator_group)
            ->where('is_active', true)
            ->exists();
    }

    $canApproveStage = $hasWorkflow && $isInValidation && $userIsInValidatorGroup && $document->current_stage <= $document->total_stages;

    // Get validation history
    $validationHistory = $document->validationHistory()->with('validator')->orderBy('validated_at', 'desc')->get();

    // Progress percentage
    $progressPercentage = $hasWorkflow ? (($document->current_stage - 1) / $document->total_stages) * 100 : 0;
    if ($document->validation_status === 'approved') {
        $progressPercentage = 100;
    }

    // Status badge config - all using primary color
    $statusLabels = [
        'pending' => 'Pendiente',
        'in_validation' => 'En validación',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
    ];
    $currentStatusLabel = $statusLabels[$document->validation_status] ?? 'Pendiente';
@endphp

@if($hasWorkflow)
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Validación etapa</h5>
        <p class="small mb-0 text-muted">Progreso de validación del documento</p>
    </div>
    <div class="card-body">
        {{-- Progress Section --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold text-dark">
                    Etapa {{ $document->current_stage }} de {{ $document->total_stages }}
                </span>
                <span class="badge bg-primary-subtle text-primary">
                    {{ $currentStatusLabel }}
                </span>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" role="progressbar"
                     style="width: {{ $progressPercentage }}%"
                     aria-valuenow="{{ $progressPercentage }}"
                     aria-valuemin="0"
                     aria-valuemax="100">
                </div>
            </div>
        </div>

        {{-- Current Stage Info --}}
        @if($isInValidation)
            @if($userIsInValidatorGroup)
                {{-- Usuario pertenece al grupo validador actual --}}
                <div class="alert bg-light py-3 px-3 mb-3" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-check-circle text-success me-2 mt-1" style="font-size: 0.9rem;"></i>
                        <div>
                            <small class="fw-semibold d-block text-success">
                                Grupo validador activo
                            </small>
                            <small class="text-muted d-block">{{ $document->getCurrentStageLabel() }}</small>
                            <small class="text-success d-block mt-1">
                                Eres miembro de este grupo - Puedes validar este documento
                            </small>
                        </div>
                    </div>
                </div>
            @elseif($document->current_validator_group)
                {{-- Hay un grupo asignado pero el usuario no pertenece --}}
                <div class="alert bg-light py-3 px-3 mb-3" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-circle-exclamation text-warning me-2 mt-1" style="font-size: 0.9rem;"></i>
                        <div>
                            <small class="fw-semibold d-block text-warning">
                                Validación asignada a otro grupo
                            </small>
                            <small class="text-muted d-block">Grupo: {{ $document->getCurrentStageLabel() }}</small>
                            <small class="text-warning d-block mt-1">
                                Esta etapa solo puede ser validada por miembros de {{ $document->getCurrentStageLabel() }}
                            </small>
                        </div>
                    </div>
                </div>
            @else
                {{-- No hay grupo asignado --}}
                <div class="alert bg-light py-3 px-3 mb-3" role="alert">
                    <div class="d-flex align-items-start">

                        <div>
                            <small class="fw-semibold d-block text-info">
                                Pendiente de asignación
                            </small>
                            <small class="text-muted d-block mt-1">
                                Esta etapa aún no ha sido asignada a ningún grupo validador
                            </small>
                            <small class="text-info d-block mt-1">
                                <i class="fas fa-arrow-right me-1"></i>Se asignará cuando avance en el flujo de validación
                            </small>
                        </div>
                    </div>
                </div>
            @endif

            @if($document->assigned_user_id && $document->assignedUser)
                <div class="alert bg-light py-2 px-3 mb-3">
                    <small class="text-muted d-flex align-items-center">
                        <i class="fas fa-user-check me-2"></i>
                        <span>Asignado a: <strong>{{ $document->assignedUser->full_name }}</strong></span>
                    </small>
                </div>
            @endif
        @endif

        {{-- Action Buttons --}}
        @if($canApproveStage)
            <div class="d-grid gap-2 mb-3">
                @if(auth()->user()->canDocument('approve-documents'))
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#approveStageModal">
                        Aprobar
                    </button>
                @else
                    <button type="button" class="btn btn-primary" disabled title="No tienes permiso para aprobar documentos">
                        Aprobar
                    </button>
                @endif

                @if(auth()->user()->canDocument('reject-documents'))
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rejectStageModal">
                        Rechazar
                    </button>
                @else
                    <button type="button" class="btn btn-outline-primary" disabled title="No tienes permiso para rechazar documentos">
                        Rechazar
                    </button>
                @endif
            </div>
        @elseif($document->validation_status === 'approved')
            <div class="alert bg-primary-subtle py-3 px-3 mb-3" role="alert">
                <div class="d-flex align-items-start">
                    <div>
                        <small class="fw-semibold d-block text-primary">Documento aprobado</small>
                        <small class="text-muted">Todas las etapas han sido completadas</small>
                    </div>
                </div>
            </div>
        @elseif($document->validation_status === 'rejected')
            <div class="alert bg-primary-subtle py-3 px-3 mb-3" role="alert">
                <div class="d-flex align-items-start">
                    <div>
                        <small class="fw-semibold d-block text-primary">Documento rechazado</small>
                        <small class="text-muted">Requiere corrección por parte del cliente</small>
                    </div>
                </div>
            </div>
        @endif

        {{-- Validation History --}}
        @if($validationHistory->count() > 0)
            <div class="border-top my-3"></div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="fw-semibold text-dark">
                   Historial de validación
                </small>
                <span class="badge bg-primary-subtle text-primary">{{ $validationHistory->count() }}</span>
            </div>

            <div class="validation-history-scroll">
                @foreach($validationHistory as $history)
                    @php
                        // Define colors and styles based on action type
                        $actionConfig = match($history->action) {
                            'approved' => [
                                'badge_class' => 'bg-primary text-white',
                                'icon_bg' => 'bg-success',
                                'icon' => 'fa-duotone fa-check-circle',
                                'label' => 'Aprobado',
                            ],
                            'rejected' => [
                                'badge_class' => 'bg-danger text-white',
                                'icon_bg' => 'bg-danger',
                                'icon' => 'fa-duotone fa-times-circle',
                                'label' => 'Rechazado',
                            ],
                            default => [
                                'badge_class' => 'bg-light text-black',
                                'icon_bg' => 'bg-warning',
                                'icon' => 'fa-duotone fa-undo',
                                'label' => 'Devuelto',
                            ]
                        };

                        // Get initials for avatar
                        $validatorName = $history->validator->full_name ?? 'Sistema';
                        $initials = collect(explode(' ', $validatorName))
                            ->take(2)
                            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                            ->join('');
                    @endphp

                    <div class="d-flex flex-row validation-item border-bottom p-3 gap-3">


                        {{-- Content --}}
                        <div class="validation-text w-100">

                            {{-- Footer --}}
                            <div class="validation-footer mt-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $actionConfig['badge_class'] }}">
                                        {{ $actionConfig['label'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Validator Name --}}
                            <h6 class="fw-medium mb-2">{{ $validatorName }}</h6>


                            {{-- Stage and Action Info --}}
                            <p class="mb-2 fs-2 text-muted">
                                @php
                                    $stageLabel = app(\Modules\Document\Services\ValidationPermissionService::class)->getStageLabelByKey($history->validator_group);
                                @endphp
                                {{ $stageLabel }} - Etapa {{ $history->stage_number }}

                                <span class="text-muted fw-normal fs-2 d-block mt-2">
                                    {{ $history->validated_at->format('d/m/Y H:i') }}
                                </span>

                            </p>

                            {{-- Comments if exist --}}
                            @if($history->comments)
                                <p class="mb-2 fs-2 text-muted" style="line-height: 1.5;">
                                    {{ $history->comments }}
                                </p>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="border-top my-3"></div>
            <div class="alert bg-light-subtle py-3 px-3 mb-0" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fas fa-circle-info text-primary me-2 mt-1" style="font-size: 0.9rem;"></i>
                    <div>
                        <small class="fw-semibold d-block">Sin historial de validación</small>
                        <small class="text-muted">No hay acciones de validación registradas aún.</small>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endif

@push('styles')
<style>
    .validation-history-scroll {
        overflow-y: auto;
        max-height: 200px;
    }

    @media (min-width: 576px) { .validation-history-scroll { max-height: 220px; } }
    @media (min-width: 768px) { .validation-history-scroll { max-height: 250px; } }
    @media (min-width: 992px) { .validation-history-scroll { max-height: 280px; } }

    .validation-history-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .validation-history-scroll::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 3px;
    }

    .validation-history-scroll::-webkit-scrollbar-thumb {
        background: #dee2e6;
        border-radius: 3px;
        transition: background 0.2s ease;
    }

    .validation-history-scroll::-webkit-scrollbar-thumb:hover {
        background: #adb5bd;
    }

    .validation-item {
        transition: all 0.2s ease;
        border-color: #e9ecef !important;
        position: relative;
        background-color: #fff;
    }

    .validation-item:hover {
        background-color: #f8f9fa;
    }

    .validation-item:last-child {
        border-bottom: none !important;
    }

    .validation-text h6 {
        font-size: 0.95rem;
    }

    .validation-text p {
        font-size: 0.85rem;
        margin: 0;
    }

    .validation-footer {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
</style>
@endpush

{{-- MODALES DE WORKFLOW --}}
@include('documents::documents.documents.components.validation.modals.approve-stage')
@include('documents::documents.documents.components.validation.modals.reject-stage')
