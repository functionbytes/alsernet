@extends('layouts.theme')

@section('title', 'Seguimiento PQRSF')

@section('content')

    @include('core::components.card', ['title' => 'Seguimiento de PQRSF'])

    <div class="row justify-content-center">
        <div class="col-lg-12">
            @if(!isset($attention))
                <!-- Formulario de busqueda -->
                <div class="card shadow-sm border-0 tracking-search-card">
                    <div class="card-body text-center px-4 px-md-5 py-5">
                        <div class="search-icon-wrapper mb-4 mt-3">
                            <div class="search-icon-circle">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>

                        <h3 class="fw-bold">Consulte el estado de su pqrsf</h3>
                        <p class="text-muted mb-5 fs-3">Ingrese su numero de radicado para ver el estado actual y el historial completo de su solicitud</p>

                        <form action="{{ route('attention.tracking') }}" method="GET" class="mx-auto" style="max-width: 540px;">
                            <div class="position-relative mb-4">
                                <input type="text"
                                       class="form-control form-control-lg tracking-input"
                                       name="radicado"
                                       placeholder="Ej: PQRSF-2026-000001"
                                       required
                                       autocomplete="off">
                                <button class="btn btn-primary tracking-submit-btn" type="submit">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </form>

                        <div class="info-badge">
                            <i class="fas fa-info-circle me-2"></i>
                            <span>El numero de radicado fue enviado a su correo electronico al momento de radicar su PQRSF</span>
                        </div>
                    </div>
                </div>
            @else
                <!-- Cabecera del radicado -->
                <div class="card shadow-sm border-0 mb-4 tracking-header-card">
                    <div class="card-header border-0 p-4 header-gradient">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="radicado-badge">
                                        {{ $attention->radicado }}
                                    </span>
                                </div>
                                <p class="mb-0 text-white-50 fs-6">{{ $attention->type->name }} · {{ $attention->category->name }}</p>
                            </div>
                            <div class="status-badge-wrapper">
                                <span class="status-badge status-{{ $attention->status->value }}">
                                    <i class="fas fa-circle status-indicator"></i>
                                    {{ $attention->status->label() }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="info-block">
                                    <div class="info-label">
                                        <i class="fas fa-file-alt me-2"></i>Asunto
                                    </div>
                                    <div class="info-value">{{ $attention->subject }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-block">
                                    <div class="info-label">
                                        <i class="fas fa-calendar me-2"></i>Fecha de radicacion
                                    </div>
                                    <div class="info-value">
                                        {{ $attention->created_at->format('d/m/Y') }}
                                        <span class="text-muted ms-1">{{ $attention->created_at->format('H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-block">
                                    <div class="info-label">
                                        <i class="fas fa-building me-2"></i>Departamento
                                    </div>
                                    <div class="info-value">
                                        {{ $attention->department?->name ?? 'Pendiente de asignacion' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progreso visual de estados -->
                @php
                    $statusOrder = ['received', 'in_process', 'resolved', 'closed'];
                    $currentIndex = array_search($attention->status->value, $statusOrder);
                    $steps = [
                        ['key' => 'received',   'label' => 'Recibido',   'icon' => 'fas fa-inbox',        'date' => $attention->created_at],
                        ['key' => 'in_process', 'label' => 'En proceso', 'icon' => 'fas fa-cogs',         'date' => null],
                        ['key' => 'resolved',   'label' => 'Resuelto',   'icon' => 'fas fa-check-circle', 'date' => $attention->resolved_at],
                        ['key' => 'closed',     'label' => 'Cerrado',    'icon' => 'fas fa-lock',         'date' => $attention->closed_at],
                    ];
                @endphp

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4 p-md-5">
                        <h6 class="text-uppercase text-muted fw-semibold mb-4 text-center">Progreso de su solicitud</h6>
                        <div class="tracking-stepper">
                            @foreach($steps as $i => $step)
                                @php
                                    $stepIndex = array_search($step['key'], $statusOrder);
                                    $isCompleted = $stepIndex < $currentIndex;
                                    $isActive = $stepIndex === $currentIndex;
                                    $isPending = $stepIndex > $currentIndex;
                                    $stateClass = $isCompleted ? 'completed' : ($isActive ? 'active' : 'pending');
                                @endphp
                                <div class="stepper-step {{ $stateClass }}">
                                    <div class="stepper-icon">
                                        @if($isCompleted)
                                            <i class="fas fa-check"></i>
                                        @else
                                            <i class="{{ $step['icon'] }}"></i>
                                        @endif
                                    </div>
                                    <div class="stepper-content">
                                        <div class="stepper-label">{{ $step['label'] }}</div>
                                        @if($step['date'])
                                            <div class="stepper-date">{{ $step['date']->format('d/m/Y') }}</div>
                                            <div class="stepper-time">{{ $step['date']->format('H:i') }}</div>
                                        @elseif($isActive)
                                            <div class="stepper-date">
                                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning px-2 py-1">
                                                    En curso
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @if($i < count($steps) - 1)
                                    <div class="stepper-connector {{ $stepIndex < $currentIndex ? 'completed' : '' }}"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Historial de acciones detallado -->
                <div class="card shadow-sm border-0 mb-4 history-timeline-card">
                    <div class="card-header border-0 bg-transparent p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="history-header-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold">Historial de estados</h5>
                                    <p class="mb-0 text-muted small">Seguimiento completo de su solicitud</p>
                                </div>
                            </div>
                            @if($attention->actions->count() > 0)
                                <span class="badge history-count-badge">
                                    <i class="fas fa-list-ul me-1"></i>
                                    {{ $attention->actions->count() }} {{ $attention->actions->count() === 1 ? 'registro' : 'registros' }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($attention->actions->count() > 0)
                            @php
                                $sortedActions = $attention->actions->sortByDesc('created_at');
                                $now = now();
                            @endphp

                            <div class="action-history-list">
                                @foreach($sortedActions as $index => $action)
                                    @php
                                        $actionConfig = match($action->action) {
                                            'created'              => ['icon' => 'fas fa-plus-circle',    'color' => 'primary',  'label' => 'Creado'],
                                            'status_changed'       => ['icon' => 'fas fa-exchange-alt',   'color' => 'info',     'label' => 'Cambio de estado'],
                                            'assigned'             => ['icon' => 'fas fa-user-check',     'color' => 'success',  'label' => 'Asignado'],
                                            'department_assigned'  => ['icon' => 'fas fa-building',       'color' => 'warning',  'label' => 'Departamento asignado'],
                                            'resolved'             => ['icon' => 'fas fa-check-double',   'color' => 'success',  'label' => 'Resuelto'],
                                            'closed'               => ['icon' => 'fas fa-lock',           'color' => 'secondary','label' => 'Cerrado'],
                                            'reopened'             => ['icon' => 'fas fa-redo',           'color' => 'warning',  'label' => 'Reabierto'],
                                            'survey_completed'     => ['icon' => 'fas fa-star',           'color' => 'warning',  'label' => 'Encuesta completada'],
                                            'note_added'           => ['icon' => 'fas fa-sticky-note',    'color' => 'info',     'label' => 'Nota agregada'],
                                            'email_sent'           => ['icon' => 'fas fa-envelope',       'color' => 'primary',  'label' => 'Correo enviado'],
                                            default                => ['icon' => 'fas fa-circle',         'color' => 'secondary','label' => ucfirst(str_replace('_', ' ', $action->action))],
                                        };
                                        $isRecent = $action->created_at->diffInHours($now) < 24;
                                        $animationDelay = $index * 0.1;
                                    @endphp
                                    <div class="action-history-item" style="animation-delay: {{ $animationDelay }}s">
                                        <div class="action-icon-wrap">
                                            <div class="action-icon action-icon-{{ $actionConfig['color'] }} {{ $isRecent ? 'recent-action' : '' }}">
                                                <i class="{{ $actionConfig['icon'] }}"></i>
                                            </div>
                                            @if(!$loop->last)
                                                <div class="action-connector action-connector-{{ $actionConfig['color'] }}"></div>
                                            @endif
                                        </div>
                                        <div class="action-card-wrapper">
                                            <div class="action-card action-card-{{ $actionConfig['color'] }}">
                                                <div class="action-card-inner">
                                                    <div class="action-header-row">
                                                        <div class="action-badge-container">
                                                            <span class="action-badge action-badge-{{ $actionConfig['color'] }}">
                                                                <i class="{{ $actionConfig['icon'] }} me-1"></i>
                                                                {{ $actionConfig['label'] }}
                                                            </span>
                                                            @if($isRecent)
                                                                <span class="recent-indicator">
                                                                    <i class="fas fa-circle-notch fa-spin"></i>
                                                                    Reciente
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="action-timestamp">
                                                            <div class="timestamp-date">{{ $action->created_at->format('d/m/Y') }}</div>
                                                            <div class="timestamp-time">{{ $action->created_at->format('H:i:s') }}</div>
                                                        </div>
                                                    </div>

                                                    @if($action->description)
                                                        <div class="action-description">
                                                            {{ $action->description }}
                                                        </div>
                                                    @endif

                                                    <div class="action-meta-row">
                                                        <div class="action-meta">
                                                            <div class="meta-item meta-user">
                                                                <div class="user-avatar">
                                                                    <i class="fas fa-user"></i>
                                                                </div>
                                                                <span>{{ $action->user?->full_name ?? 'Sistema' }}</span>
                                                            </div>
                                                            <span class="meta-separator">·</span>
                                                            <div class="meta-item meta-time" title="{{ $action->created_at->format('d/m/Y H:i:s') }}">
                                                                <i class="far fa-clock"></i>
                                                                <span>{{ $action->created_at->diffForHumans() }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-clock-rotate-left"></i>
                                </div>
                                <p class="empty-state-title">No hay historial disponible</p>
                                <p class="empty-state-description">
                                    Las acciones realizadas sobre esta solicitud apareceran aqui automaticamente
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Respuesta oficial (si existe) -->
                @if($attention->isResolved() || $attention->isClosed())
                    <div class="card shadow-sm border-0 mb-4 resolution-card">
                        <div class="card-header border-0 p-4 resolution-header">
                            <h5 class="mb-0 text-white fw-bold">Respuesta oficial</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <span class="text-muted small text-uppercase fw-semibold">Tipo de respuesta</span>
                                <div class="mt-1">
                                    <span class="badge bg-success bg-opacity-15 text-success border border-success px-3 py-2">
                                        {{ $attention->response_type?->label() ?? 'Correo electronico' }}
                                    </span>
                                </div>
                            </div>
                            <div class="resolution-content">
                                {!! nl2br(e($attention->resolution)) !!}
                            </div>

                            @if(!$attention->satisfaction_rating && $attention->isResolved())
                                <div class="survey-prompt">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="fas fa-star text-warning"></i>
                                                <strong class="fs-6">Califique nuestro servicio</strong>
                                            </div>
                                            <p class="mb-0 text-muted small">Su opinion nos ayuda a mejorar la atencion que le brindamos</p>
                                        </div>
                                        <a href="{{ route('attention.survey', ['radicado' => $attention->radicado, 'token' => $attention->uid]) }}"
                                           class="btn btn-primary px-4 flex-shrink-0">
                                            Calificar ahora
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Boton nueva busqueda -->
                <div class="text-center pb-4">
                    <a href="{{ route('attention.tracking') }}" class="btn btn-lg btn-outline-primary px-5">
                        <i class="fas fa-search me-2"></i>Consultar otro radicado
                    </a>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Limpieza del input de radicado antes de enviar el formulario
        $('.tracking-input').closest('form').on('submit', function(e) {
            const input = $(this).find('.tracking-input');
            let value = input.val();

            // Quitar espacios, tabs, saltos de línea y otros caracteres invisibles
            value = value.trim().replace(/[\t\n\r\f\v]/g, '');

            // Actualizar el input con el valor limpio
            input.val(value);
        });

        // Limpieza en tiempo real mientras el usuario escribe
        $('.tracking-input').on('input', function() {
            let value = $(this).val();
            // Quitar caracteres invisibles mientras escribe
            value = value.replace(/[\t\n\r\f\v]/g, '');
            $(this).val(value);
        });
    });
</script>
@endpush

@push('css')
<style>
    /* ============================================================
     * GLOBAL CARD STYLES - Modern shadows and borders
     * ============================================================ */
    .card.shadow-sm {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 2px 6px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card.shadow-sm:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06) !important;
    }

    /* ============================================================
     * SEARCH CARD - Empty state with modern design
     * ============================================================ */
    .tracking-search-card {
        border-radius: 16px;
        overflow: hidden;
    }

    .search-icon-wrapper {
        position: relative;
        display: inline-block;
    }

    .search-icon-circle {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 85%, white) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        color: white;
        box-shadow: 0 8px 24px color-mix(in srgb, var(--bs-primary) 25%, transparent), 0 4px 12px color-mix(in srgb, var(--bs-primary) 15%, transparent);
        animation: searchIconFloat 3s ease-in-out infinite;
        position: relative;
    }

    .search-icon-circle::before {
        content: '';
        position: absolute;
        inset: -8px;
        border-radius: 50%;
        background: radial-gradient(circle, color-mix(in srgb, var(--bs-primary) 15%, transparent) 0%, transparent 70%);
        animation: searchIconPulse 3s ease-in-out infinite;
    }

    @keyframes searchIconFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    @keyframes searchIconPulse {
        0%, 100% { opacity: 0.5; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.1); }
    }

    .tracking-input {
        height: 60px;
        border-radius: 30px;
        border: 2px solid #e9ecef;
        padding: 0 76px 0 28px;
        font-size: 16px;
        font-weight: 500;
        letter-spacing: 0.3px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff;
    }

    .tracking-input:hover {
        border-color: #ced4da;
    }

    .tracking-input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 5px color-mix(in srgb, var(--bs-primary) 12%, transparent), 0 4px 12px color-mix(in srgb, var(--bs-primary) 8%, transparent);
        outline: none;
    }

    .tracking-submit-btn {
        position: absolute;
        right: 5px;
        top: 5px;
        bottom: 5px;
        width: 50px;
        height: 50px;
        border-radius: 25px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 85%, white) 100%);
        border: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px color-mix(in srgb, var(--bs-primary) 25%, transparent);
    }

    .tracking-submit-btn:hover {
        transform: scale(1.08);
        box-shadow: 0 6px 16px color-mix(in srgb, var(--bs-primary) 35%, transparent);
        background: linear-gradient(135deg, color-mix(in srgb, var(--bs-primary) 85%, white) 0%, var(--bs-primary) 100%);
    }

    .tracking-submit-btn:active {
        transform: scale(1.02);
    }

    .info-badge {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border: 1px solid #e9ecef;
        border-radius: 16px;
        padding: 14px 24px;
        font-size: 14px;
        color: #6c757d;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
    }

    .info-badge:hover {
        border-color: var(--bs-primary);
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        box-shadow: 0 4px 8px color-mix(in srgb, var(--bs-primary) 10%, transparent);
    }

    .info-badge i {
        color: var(--bs-primary);
        font-size: 18px;
    }

    /* ============================================================
     * HEADER CARD - Gradient header with modern badges
     * ============================================================ */
    .tracking-header-card {
        border-radius: 16px;
        overflow: hidden;
    }

    .header-gradient {
        background: linear-gradient(135deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 75%, black) 100%);
        position: relative;
    }

    .header-gradient::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.3;
    }

    .radicado-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(12px);
        border: 1.5px solid rgba(255, 255, 255, 0.4);
        color: white;
        padding: 10px 20px;
        border-radius: 12px;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: 0.8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }

    .radicado-badge:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }

    .status-badge-wrapper {
        position: relative;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: white;
        padding: 12px 24px;
        border-radius: 28px;
        font-weight: 600;
        font-size: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0, 0, 0, 0.08);
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }

    .status-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15), 0 3px 8px rgba(0, 0, 0, 0.1);
    }

    .status-indicator {
        font-size: 9px;
        animation: statusPulse 2.5s ease-in-out infinite;
        filter: drop-shadow(0 0 4px currentColor);
    }

    @keyframes statusPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.9); }
    }

    .status-received {
        color: #0d6efd;
    }
    .status-received .status-badge {
        border-color: rgba(13, 110, 253, 0.2);
    }

    .status-in_process {
        color: #FEC90F;
    }
    .status-in_process .status-badge {
        border-color: rgba(254, 201, 15, 0.2);
    }

    .status-resolved {
        color: #13C672;
    }
    .status-resolved .status-badge {
        border-color: rgba(19, 198, 114, 0.2);
    }

    .status-closed {
        color: #6c757d;
    }
    .status-closed .status-badge {
        border-color: rgba(108, 117, 125, 0.2);
    }

    .info-block {
        padding: 16px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        transition: all 0.3s ease;
    }

    .info-block:hover {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-color: color-mix(in srgb, var(--bs-primary) 30%, transparent);
        box-shadow: 0 4px 12px color-mix(in srgb, var(--bs-primary) 8%, transparent);
        transform: translateY(-2px);
    }

    .info-label {
        font-size: 12px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
    }

    .info-label i {
        color: var(--bs-primary);
        font-size: 14px;
    }

    .info-value {
        font-size: 16px;
        color: #2a3547;
        font-weight: 600;
        line-height: 1.5;
    }

    /* ============================================================
     * STEPPER - Enhanced horizontal progress tracker
     * ============================================================ */
    .tracking-stepper {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        gap: 0;
        padding: 0;
    }

    .stepper-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        min-width: 120px;
        flex: 0 0 auto;
    }

    .stepper-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        border: 4px solid #e9ecef;
        background: #fff;
        color: #adb5bd;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 2;
        position: relative;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .stepper-step.completed .stepper-icon {
        background: linear-gradient(135deg, #13C672 0%, #10b464 100%);
        border-color: #13C672;
        color: #fff;
        box-shadow: 0 6px 20px rgba(19, 198, 114, 0.3), 0 0 0 0 rgba(19, 198, 114, 0.2);
        animation: completedPulse 3s ease-in-out infinite;
    }

    .stepper-step.active .stepper-icon {
        background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
        border-color: var(--bs-primary);
        border-width: 5px;
        color: var(--bs-primary);
        box-shadow: 0 0 0 8px color-mix(in srgb, var(--bs-primary) 12%, transparent), 0 4px 16px color-mix(in srgb, var(--bs-primary) 20%, transparent);
        animation: stepperPulse 2.5s infinite;
    }

    @keyframes completedPulse {
        0%, 100% {
            box-shadow: 0 6px 20px rgba(19, 198, 114, 0.3), 0 0 0 0 rgba(19, 198, 114, 0.2);
        }
        50% {
            box-shadow: 0 6px 20px rgba(19, 198, 114, 0.4), 0 0 0 6px rgba(19, 198, 114, 0.1);
        }
    }

    .stepper-content {
        margin-top: 12px;
        text-align: center;
    }

    .stepper-label {
        font-weight: 600;
        font-size: 15px;
        color: #adb5bd;
        margin-bottom: 6px;
        transition: all 0.3s ease;
    }

    .stepper-step.completed .stepper-label {
        color: #2a3547;
        font-weight: 700;
    }

    .stepper-step.active .stepper-label {
        color: var(--bs-primary);
        font-weight: 700;
        font-size: 16px;
    }

    .stepper-date {
        font-size: 14px;
        color: #6c757d;
        font-weight: 600;
    }

    .stepper-time {
        font-size: 13px;
        color: #adb5bd;
        margin-top: 2px;
    }

    .stepper-connector {
        flex: 1;
        height: 5px;
        background: #e9ecef;
        margin-top: 30px;
        min-width: 60px;
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 3px;
        position: relative;
        overflow: hidden;
    }

    .stepper-connector.completed {
        background: linear-gradient(90deg, #13C672 0%, #10b464 100%);
        box-shadow: 0 2px 8px rgba(19, 198, 114, 0.25);
    }

    .stepper-connector.completed::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        animation: connectorShine 2s ease-in-out infinite;
    }

    @keyframes connectorShine {
        0% { left: -100%; }
        50%, 100% { left: 100%; }
    }

    @keyframes stepperPulse {
        0%, 100% {
            box-shadow: 0 0 0 8px color-mix(in srgb, var(--bs-primary) 12%, transparent), 0 4px 16px color-mix(in srgb, var(--bs-primary) 20%, transparent);
        }
        50% {
            box-shadow: 0 0 0 12px color-mix(in srgb, var(--bs-primary) 6%, transparent), 0 4px 16px color-mix(in srgb, var(--bs-primary) 25%, transparent);
        }
    }

    /* ============================================================
     * ACTION HISTORY - Enhanced modern timeline
     * ============================================================ */

    /* History header styles */
    .history-timeline-card {
        border-radius: 16px;
        overflow: hidden;
    }

    .history-header-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 80%, white) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        box-shadow: 0 4px 12px color-mix(in srgb, var(--bs-primary) 20%, transparent);
        transition: all 0.3s ease;
    }

    .history-header-icon:hover {
        transform: rotate(-10deg) scale(1.05);
        box-shadow: 0 6px 16px color-mix(in srgb, var(--bs-primary) 30%, transparent);
    }

    .history-count-badge {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #6c757d;
        border: 2px solid #dee2e6;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }

    .history-count-badge:hover {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-color: var(--bs-primary);
        color: var(--bs-primary);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px color-mix(in srgb, var(--bs-primary) 15%, transparent);
    }

    /* Timeline list */
    .action-history-list {
        padding: 2rem 2.5rem;
    }

    .action-history-item {
        display: flex;
        gap: 1.5rem;
        position: relative;
        opacity: 0;
        animation: fadeInUp 0.5s ease forwards;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Icon wrapper */
    .action-icon-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
        position: relative;
    }

    .action-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        z-index: 2;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .action-icon::after {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    /* Recent action pulse animation */
    .action-icon.recent-action::after {
        background: radial-gradient(circle, currentColor 0%, transparent 70%);
        animation: iconPulse 2s ease-in-out infinite;
        opacity: 0.3;
    }

    @keyframes iconPulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.3;
        }
        50% {
            transform: scale(1.3);
            opacity: 0;
        }
    }

    /* Color variants for icons */
    .action-icon-primary {
        background: linear-gradient(135deg, #e7f1ff 0%, #d0e5ff 100%);
        color: #0d6efd;
        border: 3px solid rgba(13, 110, 253, 0.2);
    }

    .action-icon-success {
        background: linear-gradient(135deg, #e6f9f2 0%, #d1f4e6 100%);
        color: #13C672;
        border: 3px solid rgba(19, 198, 114, 0.2);
    }

    .action-icon-warning {
        background: linear-gradient(135deg, #fff9e6 0%, #fff3d1 100%);
        color: #FEC90F;
        border: 3px solid rgba(254, 201, 15, 0.2);
    }

    .action-icon-danger {
        background: linear-gradient(135deg, #ffe8e6 0%, #ffd4d1 100%);
        color: #FA896B;
        border: 3px solid rgba(250, 137, 107, 0.2);
    }

    .action-icon-info {
        background: linear-gradient(135deg, #e6f7ff 0%, #d1eeff 100%);
        color: #17a2b8;
        border: 3px solid rgba(23, 162, 184, 0.2);
    }

    .action-icon-secondary {
        background: linear-gradient(135deg, #f0f0f0 0%, #e5e5e5 100%);
        color: #6c757d;
        border: 3px solid rgba(108, 117, 125, 0.2);
    }

    /* Connector line between icons */
    .action-connector {
        width: 4px;
        flex: 1;
        min-height: 48px;
        background: linear-gradient(180deg, #dee2e6 0%, #f1f3f5 100%);
        border-radius: 2px;
        position: relative;
        overflow: hidden;
        margin-top: 8px;
    }

    .action-connector::after {
        content: '';
        position: absolute;
        top: -50%;
        left: 0;
        right: 0;
        height: 50%;
        background: linear-gradient(180deg, transparent 0%, rgba(255, 255, 255, 0.5) 100%);
        animation: connectorFlow 2s linear infinite;
    }

    @keyframes connectorFlow {
        0% {
            top: -50%;
        }
        100% {
            top: 150%;
        }
    }

    /* Colored connectors based on action type */
    .action-connector-primary { background: linear-gradient(180deg, #d0e5ff 0%, #f1f3f5 100%); }
    .action-connector-success { background: linear-gradient(180deg, #d1f4e6 0%, #f1f3f5 100%); }
    .action-connector-warning { background: linear-gradient(180deg, #fff3d1 0%, #f1f3f5 100%); }
    .action-connector-info { background: linear-gradient(180deg, #d1eeff 0%, #f1f3f5 100%); }

    /* Action card wrapper */
    .action-card-wrapper {
        flex: 1;
        padding-bottom: 2rem;
    }

    .action-history-item:last-child .action-card-wrapper {
        padding-bottom: 0;
    }

    /* Action card */
    .action-card {
        background: #ffffff;
        border: 2px solid #f0f0f0;
        border-radius: 14px;
        padding: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .action-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    /* Colored left border on hover */
    .action-card-primary::before { background: linear-gradient(180deg, #0d6efd 0%, #4d94ff 100%); }
    .action-card-success::before { background: linear-gradient(180deg, #13C672 0%, #5ed99a 100%); }
    .action-card-warning::before { background: linear-gradient(180deg, #FEC90F 0%, #ffd84d 100%); }
    .action-card-info::before { background: linear-gradient(180deg, #17a2b8 0%, #5bc0d0 100%); }
    .action-card-secondary::before { background: linear-gradient(180deg, #6c757d 0%, #9ba3ab 100%); }

    .action-card:hover {
        border-color: #e0e0e0;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        transform: translateX(6px);
    }

    .action-card:hover::before {
        opacity: 1;
    }

    .action-card:hover ~ .action-icon-wrap .action-icon {
        transform: scale(1.15) rotate(8deg);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    /* Action header row */
    .action-header-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .action-badge-container {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Action badge */
    .action-badge {
        display: inline-flex;
        align-items: center;
        padding: 9px 18px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 700;
        border: 2px solid;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    .action-badge i {
        font-size: 13px;
    }

    .action-badge-primary {
        background: linear-gradient(135deg, #e7f1ff 0%, #d0e5ff 100%);
        color: #0d6efd;
        border-color: #b6d7ff;
    }

    .action-badge-success {
        background: linear-gradient(135deg, #e6f9f2 0%, #d1f4e6 100%);
        color: #13C672;
        border-color: #b3efd9;
    }

    .action-badge-warning {
        background: linear-gradient(135deg, #fff9e6 0%, #fff3d1 100%);
        color: #FEC90F;
        border-color: #ffeeb3;
    }

    .action-badge-danger {
        background: linear-gradient(135deg, #ffe8e6 0%, #ffd4d1 100%);
        color: #FA896B;
        border-color: #ffc9c0;
    }

    .action-badge-info {
        background: linear-gradient(135deg, #e6f7ff 0%, #d1eeff 100%);
        color: #17a2b8;
        border-color: #b3e5f7;
    }

    .action-badge-secondary {
        background: linear-gradient(135deg, #f0f0f0 0%, #e5e5e5 100%);
        color: #6c757d;
        border-color: #d5d5d5;
    }

    /* Recent indicator */
    .recent-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
        color: #FEC90F;
        border: 2px solid #ffeeb3;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(254, 201, 15, 0.15);
    }

    .recent-indicator i {
        font-size: 11px;
    }

    /* Action description */
    .action-description {
        margin: 14px 0;
        padding: 14px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-left: 4px solid #dee2e6;
        border-radius: 8px;
        color: #2a3547;
        font-size: 14px;
        line-height: 1.7;
        font-weight: 500;
    }

    /* Action metadata row */
    .action-meta-row {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #f0f0f0;
    }

    .action-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #6c757d;
    }

    .meta-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-weight: 500;
    }

    .meta-user {
        padding: 6px 12px;
        background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
        border-radius: 20px;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .meta-user:hover {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-color: var(--bs-primary);
        color: var(--bs-primary);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px color-mix(in srgb, var(--bs-primary) 10%, transparent);
    }

    .user-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 80%, white) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 11px;
    }

    .meta-time {
        padding: 6px 12px;
        background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
        border-radius: 20px;
        border: 1px solid #ffeeb3;
        transition: all 0.3s ease;
    }

    .meta-time:hover {
        background: linear-gradient(135deg, #ffffff 0%, #fff9e6 100%);
        border-color: #FEC90F;
        color: #FEC90F;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(254, 201, 15, 0.15);
    }

    .meta-item i {
        font-size: 12px;
        opacity: 0.8;
    }

    .meta-separator {
        opacity: 0.4;
        font-weight: 700;
    }

    /* Timestamp */
    .action-timestamp {
        text-align: right;
        flex-shrink: 0;
        padding: 8px 14px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        border: 2px solid #dee2e6;
        transition: all 0.3s ease;
    }

    .action-timestamp:hover {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-color: var(--bs-primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 10px color-mix(in srgb, var(--bs-primary) 12%, transparent);
    }

    .timestamp-date {
        font-weight: 700;
        font-size: 15px;
        color: #2a3547;
        line-height: 1.3;
        letter-spacing: 0.3px;
    }

    .timestamp-time {
        font-size: 13px;
        color: #6c757d;
        margin-top: 2px;
        font-weight: 600;
    }

    /* ============================================================
     * RESOLUTION CARD - Success state with gradient
     * ============================================================ */
    .resolution-card {
        border-radius: 16px;
        overflow: hidden;
        border: 2px solid #13C672;
    }

    .resolution-header {
        background: linear-gradient(135deg, #13C672 0%, #10b464 100%);
        position: relative;
    }

    .resolution-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'%3E%3Cpath d='M0 40L40 0H20L0 20M40 40V20L20 40'/%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.4;
    }

    .resolution-content {
        background: linear-gradient(135deg, #f8fffe 0%, #f0fdf9 100%);
        border: 2px solid #d1f4e6;
        border-radius: 16px;
        padding: 24px;
        line-height: 1.9;
        color: #2a3547;
        font-size: 15px;
        box-shadow: 0 2px 8px rgba(19, 198, 114, 0.08);
        transition: all 0.3s ease;
    }

    .resolution-content:hover {
        border-color: #b3efd9;
        box-shadow: 0 4px 12px rgba(19, 198, 114, 0.12);
    }

    .survey-prompt {
        margin-top: 28px;
        padding: 24px;
        background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
        border: 2px solid #ffeeb3;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(254, 201, 15, 0.08);
        transition: all 0.3s ease;
    }

    .survey-prompt:hover {
        border-color: #ffd966;
        box-shadow: 0 4px 12px rgba(254, 201, 15, 0.12);
    }

    /* ============================================================
     * EMPTY STATES - Enhanced modern design
     * ============================================================ */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-state-icon {
        width: 96px;
        height: 96px;
        margin: 0 auto 2rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: #adb5bd;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        border: 4px solid #dee2e6;
        position: relative;
        animation: emptyIconFloat 3s ease-in-out infinite;
    }

    .empty-state-icon::before {
        content: '';
        position: absolute;
        inset: -12px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(173, 181, 189, 0.1) 0%, transparent 70%);
        animation: emptyIconPulse 3s ease-in-out infinite;
    }

    @keyframes emptyIconFloat {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-8px);
        }
    }

    @keyframes emptyIconPulse {
        0%, 100% {
            opacity: 0.4;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.1);
        }
    }

    .empty-state-title {
        color: #2a3547;
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 10px 0;
        letter-spacing: 0.2px;
    }

    .empty-state-description {
        color: #6c757d;
        font-size: 15px;
        font-weight: 500;
        margin: 0;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    .empty-state-text {
        color: #6c757d;
        font-size: 16px;
        font-weight: 500;
        margin: 0;
    }

    /* ============================================================
     * RESPONSIVE DESIGN - Mobile optimizations
     * ============================================================ */
    @media (max-width: 768px) {
        .tracking-search-card .card-body {
            padding: 2rem 1.5rem !important;
        }

        .search-icon-circle {
            width: 72px;
            height: 72px;
            font-size: 28px;
        }

        .tracking-input {
            height: 56px;
            padding: 0 68px 0 22px;
            font-size: 15px;
        }

        .tracking-submit-btn {
            width: 46px;
            height: 46px;
        }

        .radicado-badge {
            font-size: 17px;
            padding: 8px 14px;
        }

        .status-badge {
            font-size: 14px;
            padding: 10px 18px;
        }

        .info-block {
            padding: 14px;
        }

        .info-label {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 15px;
        }

        .action-history-list {
            padding: 1.5rem 1.25rem;
        }

        .history-header-icon {
            width: 42px;
            height: 42px;
            font-size: 18px;
        }

        .history-count-badge {
            font-size: 12px;
            padding: 6px 12px;
        }

        .action-icon {
            width: 46px;
            height: 46px;
            font-size: 18px;
        }

        .action-card {
            padding: 16px;
        }

        .action-header-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .action-timestamp {
            text-align: left;
            align-self: flex-start;
        }

        .action-badge {
            padding: 7px 14px;
            font-size: 13px;
        }

        .recent-indicator {
            font-size: 11px;
            padding: 4px 10px;
        }

        .action-description {
            font-size: 13px;
            padding: 12px;
        }

        .meta-user,
        .meta-time {
            padding: 5px 10px;
            font-size: 12px;
        }

        .user-avatar {
            width: 22px;
            height: 22px;
            font-size: 10px;
        }

        .empty-state {
            padding: 3rem 1.5rem;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            font-size: 34px;
        }

        .empty-state-title {
            font-size: 17px;
        }

        .empty-state-description {
            font-size: 14px;
        }
    }

    @media (max-width: 576px) {
        .tracking-stepper {
            flex-direction: column;
            align-items: stretch;
            gap: 0;
        }

        .stepper-step {
            flex-direction: row;
            gap: 1rem;
            min-width: auto;
            align-items: flex-start;
        }

        .stepper-icon {
            width: 52px;
            height: 52px;
            font-size: 19px;
            flex-shrink: 0;
            border-width: 3px;
        }

        .stepper-step.active .stepper-icon {
            border-width: 4px;
        }

        .stepper-content {
            flex: 1;
            text-align: left;
            margin-top: 0;
        }

        .stepper-label {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .stepper-step.active .stepper-label {
            font-size: 15px;
        }

        .stepper-date {
            font-size: 13px;
        }

        .stepper-time {
            font-size: 12px;
        }

        .stepper-label,
        .stepper-date,
        .stepper-time {
            text-align: left;
        }

        .stepper-connector {
            width: 4px;
            height: 32px;
            min-width: auto;
            margin-top: 0;
            margin-left: 24px;
        }

        .action-icon {
            width: 42px;
            height: 42px;
            font-size: 16px;
        }

        .action-history-list {
            padding: 1.25rem;
        }

        .action-connector {
            width: 3px;
            min-height: 40px;
        }

        .action-card {
            padding: 14px;
        }

        .action-badge {
            padding: 6px 12px;
            font-size: 12px;
        }

        .action-badge i {
            font-size: 11px;
        }

        .recent-indicator {
            padding: 4px 8px;
            font-size: 10px;
        }

        .action-description {
            font-size: 13px;
            padding: 10px;
            margin: 10px 0;
        }

        .action-meta {
            font-size: 12px;
            gap: 6px;
        }

        .meta-user,
        .meta-time {
            padding: 4px 8px;
            font-size: 11px;
        }

        .user-avatar {
            width: 20px;
            height: 20px;
            font-size: 9px;
        }

        .action-timestamp {
            padding: 6px 10px;
        }

        .timestamp-date {
            font-size: 14px;
        }

        .timestamp-time {
            font-size: 12px;
        }

        .history-header-icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }

        .empty-state {
            padding: 2.5rem 1rem;
        }

        .empty-state-icon {
            width: 72px;
            height: 72px;
            font-size: 30px;
        }

        .empty-state-title {
            font-size: 16px;
        }

        .empty-state-description {
            font-size: 13px;
        }
    }
</style>
@endpush
