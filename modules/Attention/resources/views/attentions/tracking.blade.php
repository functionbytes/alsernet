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
                @php
                    $statusOrder  = ['received', 'in_process', 'resolved', 'closed'];
                    $currentIndex = array_search($attention->status->value, $statusOrder);
                    $steps = [
                        ['key' => 'received',   'label' => 'Recibido',   'icon' => 'fas fa-inbox',        'color' => 'primary',  'date' => $attention->created_at],
                        ['key' => 'in_process', 'label' => 'En proceso', 'icon' => 'fas fa-cogs',         'color' => 'warning',  'date' => null],
                        ['key' => 'resolved',   'label' => 'Resuelto',   'icon' => 'fas fa-check-circle', 'color' => 'success',  'date' => $attention->resolved_at],
                        ['key' => 'closed',     'label' => 'Cerrado',    'icon' => 'fas fa-lock',         'color' => 'secondary','date' => $attention->closed_at],
                    ];
                @endphp
                <div class="row">

                    {{-- COLUMNA IZQUIERDA --}}
                    <div class="col-lg-8">

                        {{-- Información general --}}
                        <div class="card mb-3">
                            <div class="card-header p-3 bg-white border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1 fw-bold">Información general</h5>
                                        <p class="small mb-0 text-muted">Detalles del PQRSF radicado</p>
                                    </div>
                                    <span class="badge bg-{{ $attention->status->color() }}">
                                        {{ $attention->status->label() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label fw-semibold text-muted">Radicado</label>
                                        <p class="mb-0 text-primary fw-semibold">{{ $attention->radicado }}</p>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label fw-semibold text-muted">Tipo</label>
                                        <p class="mb-0">
                                            <span class="badge bg-info">{{ $attention->type->name }}</span>
                                        </p>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label fw-semibold text-muted">Categoría</label>
                                        <p class="mb-0">
                                            <span class="badge bg-light-primary text-primary">{{ $attention->category->name }}</span>
                                        </p>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label fw-semibold text-muted">Sede</label>
                                        <p class="mb-0">{{ $attention->sede->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label fw-semibold text-muted">Fecha de radicación</label>
                                        <p class="mb-0">{{ $attention->created_at->format('d/m/Y H:i:s') }}</p>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label fw-semibold text-muted">Última actualización</label>
                                        <p class="mb-0">{{ $attention->updated_at->format('d/m/Y H:i:s') }}</p>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label fw-semibold text-muted">Departamento</label>
                                        <p class="mb-0">{{ $attention->department->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label fw-semibold text-muted">Usuario asignado</label>
                                        <p class="mb-0">{{ $attention->assignedUser->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-muted">Asunto</label>
                                        <p class="mb-0">{{ $attention->subject }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Progreso de la solicitud --}}
                        <div class="card mb-3">
                            <div class="card-header p-3 bg-white border-bottom">
                                <h5 class="mb-1 fw-bold">Progreso de la solicitud</h5>
                                <p class="small mb-0 text-muted">Estados por los que ha pasado su PQRSF</p>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Estado</th>
                                                <th>Descripción</th>
                                                <th class="text-end">Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($steps as $step)
                                                @php
                                                    $stepIndex   = array_search($step['key'], $statusOrder);
                                                    $isCompleted = $stepIndex < $currentIndex;
                                                    $isActive    = $stepIndex === $currentIndex;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        @if($isCompleted)
                                                            <span class="badge bg-success">{{ $step['label'] }}</span>
                                                        @elseif($isActive)
                                                            <span class="badge bg-{{ $step['color'] }}">{{ $step['label'] }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ $step['label'] }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">
                                                        @if($isCompleted) Completado
                                                        @elseif($isActive) En curso
                                                        @else Pendiente
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if($step['date'])
                                                            <small>{{ $step['date']->format('d/m/Y H:i') }}</small>
                                                        @elseif($isActive)
                                                            <span class="badge bg-warning">En curso</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Historial de estados --}}
                        <div class="card mb-3">
                            <div class="card-header p-3 bg-white border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1 fw-bold">Historial de estados</h5>
                                        <p class="small mb-0 text-muted">Registro de todas las acciones realizadas</p>
                                    </div>
                                    @if($attention->actions->count() > 0)
                                        <span class="badge bg-secondary rounded-pill">
                                            {{ $attention->actions->count() }} {{ $attention->actions->count() === 1 ? 'registro' : 'registros' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                @if($attention->actions->count() > 0)
                                    @php $sortedActions = $attention->actions->sortByDesc('created_at'); @endphp
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Acción</th>
                                                    <th>Descripción</th>
                                                    <th>Responsable</th>
                                                    <th class="text-end">Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sortedActions as $action)
                                                    @php
                                                        $actionConfig = match($action->action) {
                                                            'created'              => ['icon' => 'fas fa-plus-circle',  'color' => 'primary',  'label' => 'Creado'],
                                                            'status_changed'       => ['icon' => 'fas fa-exchange-alt', 'color' => 'info',     'label' => 'Cambio de estado'],
                                                            'assigned'             => ['icon' => 'fas fa-user-check',   'color' => 'success',  'label' => 'Asignado'],
                                                            'department_assigned'  => ['icon' => 'fas fa-building',     'color' => 'warning',  'label' => 'Depto. asignado'],
                                                            'resolved'             => ['icon' => 'fas fa-check-double', 'color' => 'success',  'label' => 'Resuelto'],
                                                            'closed'               => ['icon' => 'fas fa-lock',         'color' => 'secondary','label' => 'Cerrado'],
                                                            'reopened'             => ['icon' => 'fas fa-redo',         'color' => 'warning',  'label' => 'Reabierto'],
                                                            'survey_completed'     => ['icon' => 'fas fa-star',         'color' => 'warning',  'label' => 'Encuesta'],
                                                            'note_added'           => ['icon' => 'fas fa-sticky-note',  'color' => 'info',     'label' => 'Nota agregada'],
                                                            'email_sent'           => ['icon' => 'fas fa-envelope',     'color' => 'primary',  'label' => 'Correo enviado'],
                                                            default                => ['icon' => 'fas fa-circle',       'color' => 'secondary','label' => ucfirst(str_replace('_', ' ', $action->action))],
                                                        };
                                                        $isRecent = $action->created_at->diffInHours(now()) < 24;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-{{ $actionConfig['color'] }}">{{ $actionConfig['label'] }}</span>
                                                            @if($isRecent)
                                                                <span class="badge bg-warning ms-1">Reciente</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-muted small">{{ $action->description ?? '-' }}</td>
                                                        <td class="small">{{ $action->user?->full_name ?? 'Sistema' }}</td>
                                                        <td class="text-end">
                                                            <div class="fw-semibold small">{{ $action->created_at->format('d/m/Y') }}</div>
                                                            <div class="text-muted" style="font-size:11px">{{ $action->created_at->format('H:i:s') }}</div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <i class="fas fa-history fa-2x text-muted mb-3 d-block"></i>
                                        <p class="fw-semibold mb-1">No hay historial disponible</p>
                                        <p class="text-muted small mb-0">Las acciones aparecerán aquí automáticamente</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Respuesta oficial --}}
                        @if($attention->isResolved() || $attention->isClosed())
                            <div class="card mb-3">
                                <div class="card-header p-3 bg-white border-bottom">
                                    <h5 class="mb-1 fw-bold">Respuesta oficial</h5>
                                    <p class="small mb-0 text-muted">Respuesta emitida a su solicitud</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Tipo de respuesta</label>
                                        <p class="mb-0">
                                            <span class="badge bg-success">
                                                {{ $attention->response_type?->label() ?? 'Correo electrónico' }}
                                            </span>
                                        </p>
                                    </div>
                                    @if($attention->resolved_at)
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-muted">Fecha de resolución</label>
                                            <p class="mb-0">{{ $attention->resolved_at->format('d/m/Y H:i:s') }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <label class="form-label fw-semibold text-muted">Respuesta</label>
                                        <div class="border rounded p-3 bg-light-success">
                                            {!! nl2br(e($attention->resolution)) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- COLUMNA DERECHA --}}
                    <div class="col-lg-4">

{{-- Acciones --}}
                        <div class="card mb-3">
                            <div class="card-header p-3 bg-white border-bottom">
                                <h5 class="mb-0 fw-bold">Acciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    @if(!$attention->satisfaction_rating && $attention->isResolved())
                                        <a href="{{ route('attention.survey', ['radicado' => $attention->radicado, 'token' => $attention->uid]) }}"
                                           class="btn btn-warning">
                                            <i class="fas fa-star me-1"></i> Calificar servicio
                                        </a>
                                    @endif
                                    @auth
                                        @if($attention->canBeEdited())
                                            <a href="{{ route('attention.edit', $attention->uid) }}" class="btn btn-primary">
                                                Editar
                                            </a>
                                        @endif
                                        <a href="{{ route('attention.index') }}" class="btn btn-secondary">
                                            Volver al listado
                                        </a>
                                    @endauth
                                    <a href="{{ route('attention.tracking') }}" class="btn btn-outline-primary">
                                       Consultar otro radicado
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Estadísticas --}}
                        <div class="card mb-3">
                            <div class="card-header p-3 bg-white border-bottom">
                                <h5 class="mb-0 fw-bold">Estadísticas</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Acciones registradas:</span>
                                        <span class="fw-bold">{{ $attention->actions->count() }}</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Días transcurridos:</span>
                                        <span class="fw-bold">{{ $attention->created_at->diffInDays(now()) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Adjuntos:</span>
                                        <span class="fw-bold">{{ $attention->getMedia('attachments')->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Calificación (si existe) --}}
                        @if($attention->satisfaction_rating)
                            <div class="card mb-3">
                                <div class="card-header p-3 bg-white border-bottom">
                                    <h5 class="mb-0 fw-bold">Calificación</h5>
                                </div>
                                <div class="card-body text-center">
                                    <div class="fs-1 text-warning mb-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $attention->satisfaction_rating)
                                                <i class="fas fa-star"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <p class="mb-0 text-muted">{{ $attention->satisfaction_rating }} de 5 estrellas</p>
                                </div>
                            </div>
                        @endif

                    </div>
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
</style>
@endpush
