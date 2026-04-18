@extends('attention::layouts.public')

@section('title', 'Seguimiento peticiones')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">

            {{-- Formulario de busqueda --}}
            <div class="card mb-4 border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                             style="width: 70px; height: 70px; background: linear-gradient(135deg, #90bb13 0%, #13C672 100%); box-shadow: 0 4px 20px rgba(144, 187, 19, 0.3);">
                            <i class="fas fa-magnifying-glass fa-2x text-white"></i>
                        </div>
                        <h4 class="fw-bold mb-2" style="color: #1a2030;">Consulte el estado de su peticiones</h4>
                        <p class="text-muted mb-0">Ingrese su numero de radicado para ver el estado actual</p>
                    </div>

                    <form action="{{ route('peticiones.tracking') }}" method="GET" class="mx-auto" style="max-width: 550px;">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="form-control form-control-lg search-input" name="radicado"
                                   value="{{ $radicado ?? request('radicado') }}"
                                   placeholder="Ej: peticiones-2026-000001" required>
                            <button class="btn btn-primary btn-lg search-button" type="submit">
                                Consultar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if(isset($radicado))
                @if(isset($attention) && $attention)
                    {{-- Informacion del radicado --}}
                    <div class="card mb-4">
                        <div class="card-header p-3" style="background: linear-gradient(135deg, #2a3042, #1a2030);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 text-white fw-bold">Radicado: {{ $attention->radicado }}</h5>
                                    <p class="mb-0 small text-white-50">
                                        {{ $attention->type->name ?? '' }}
                                        @if($attention->category)
                                            — {{ $attention->category->name }}
                                        @endif
                                    </p>
                                </div>
                                <span class="badge bg-white fs-6" style="color: {{ $attention->status->color() }};">
                                    {{ $attention->status->label() }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <p class="text-muted mb-1 small">Asunto</p>
                                    <p class="fw-semibold mb-0">{{ $attention->subject }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p class="text-muted mb-1 small">Fecha de radicacion</p>
                                    <p class="fw-semibold mb-0">{{ $attention->created_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                                @if($attention->sede)
                                    <div class="col-md-6 mb-3">
                                        <p class="text-muted mb-1 small">Sede</p>
                                        <p class="fw-semibold mb-0">{{ $attention->sede->name }}</p>
                                    </div>
                                @endif
                                @if($attention->department)
                                    <div class="col-md-6 mb-3">
                                        <p class="text-muted mb-1 small">Departamento asignado</p>
                                        <p class="fw-semibold mb-0">{{ $attention->department->name }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Timeline de estados --}}
                    <div class="card mb-4">
                        <div class="card-header bg-white p-3 border-bottom">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-timeline text-primary me-2"></i>Historial de estados
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="tracking-timeline">
                                {{-- Recibido --}}
                                @php
                                    $isReceived = true;
                                    $isInProcess = in_array($attention->status->value, ['in_process', 'resolved', 'closed']);
                                    $isResolved = in_array($attention->status->value, ['resolved', 'closed']);
                                    $isClosed = $attention->status->value === 'closed';
                                @endphp

                                <div class="timeline-step {{ $isReceived ? 'completed' : '' }}">
                                    <div class="timeline-icon">
                                        <i class="fas fa-inbox"></i>
                                    </div>
                                    <div class="timeline-body">
                                        <h6 class="mb-1">Recibido</h6>
                                        <p class="text-muted mb-0">
                                            Su peticiones ha sido radicado exitosamente
                                            <br>{{ $attention->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="timeline-step {{ $isInProcess ? 'completed' : ($attention->status->value === 'received' ? 'pending' : '') }}
                                     {{ $attention->status->value === 'in_process' ? 'active' : '' }}">
                                    <div class="timeline-icon">
                                        <i class="fas fa-gears"></i>
                                    </div>
                                    <div class="timeline-body">
                                        <h6 class="mb-1">En proceso</h6>
                                        <p class="text-muted mb-0">
                                            @if($isInProcess)
                                                Su solicitud esta siendo atendida
                                                @if($attention->department)
                                                    por {{ $attention->department->name }}
                                                @endif
                                            @else
                                                Pendiente de asignacion
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="timeline-step {{ $isResolved ? 'completed' : 'pending' }}
                                     {{ $attention->status->value === 'resolved' ? 'active' : '' }}">
                                    <div class="timeline-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="timeline-body">
                                        <h6 class="mb-1">Resuelto</h6>
                                        <p class="text-muted mb-0">
                                            @if($isResolved)
                                                Su peticiones ha sido resuelto
                                                @if($attention->resolved_at)
                                                    <br>{{ $attention->resolved_at->format('d/m/Y H:i') }}
                                                @endif
                                            @else
                                                Pendiente de resolucion
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="timeline-step {{ $isClosed ? 'completed' : 'pending' }}">
                                    <div class="timeline-icon">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <div class="timeline-body">
                                        <h6 class="mb-1">Cerrado</h6>
                                        <p class="text-muted mb-0">
                                            @if($isClosed)
                                                El caso ha sido cerrado
                                                @if($attention->closed_at)
                                                    <br>{{ $attention->closed_at->format('d/m/Y H:i') }}
                                                @endif
                                            @else
                                                Pendiente de cierre
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Respuesta oficial --}}
                    @if($attention->resolution)
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white p-3">
                                <h5 class="mb-0 text-white fw-bold">
                                    <i class="fas fa-reply me-2"></i>Respuesta oficial
                                </h5>
                            </div>
                            <div class="card-body">
                                @if($attention->response_type)
                                    <p class="mb-2">
                                        <strong>Tipo de respuesta:</strong>
                                        <span class="badge bg-light text-dark">{{ $attention->response_type->label() }}</span>
                                    </p>
                                @endif
                                <div class="bg-light rounded p-3">
                                    {!! nl2br(e($attention->resolution)) !!}
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Radicado no encontrado --}}
                    <div class="card border-0 shadow">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                     style="width: 100px; height: 100px; background: linear-gradient(135deg, #FEC90F15 0%, #FEC90F30 100%);">
                                    <i class="fas fa-circle-exclamation text-warning" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-3" style="color: #1a2030;">Radicado no encontrado</h4>
                            <p class="text-muted mb-4">
                                No se encontro ninguna solicitud con el radicado <strong class="text-dark">{{ $radicado }}</strong>.
                                <br>Verifique el numero e intentelo nuevamente.
                            </p>
                            <div class="alert alert-light border text-start">
                                <p class="mb-2"><strong>Verifique que:</strong></p>
                                <ul class="mb-0 text-muted">
                                    <li>El numero de radicado este escrito correctamente</li>
                                    <li>Incluya los guiones (Ej: peticiones-2026-000001)</li>
                                    <li>El radicado haya sido generado en nuestro sistema</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Enlace al formulario --}}
            <div class="text-center mt-4">
                <a href="{{ route('peticiones.form') }}" class="btn btn-outline-primary">
                    <i class="fas fa-file-pen me-2"></i>Radicar nueva solicitud
                </a>
            </div>

        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* Search Input Wrapper */
        .search-input-wrapper {
            position: relative;
            display: flex;
            gap: 0.5rem;
        }

        .search-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #90bb13;
            font-size: 1.125rem;
            z-index: 10;
        }

        .search-input {
            padding-left: 3rem;
            border-radius: 0.75rem;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
            flex: 1;
        }

        .search-input:focus {
            border-color: #90bb13;
            box-shadow: 0 0 0 0.25rem rgba(144, 187, 19, 0.15);
            padding-left: 3rem;
        }

        .search-button {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.75rem 2rem;
            white-space: nowrap;
        }

        @media (max-width: 576px) {
            .search-input-wrapper {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
            }
        }

        /* Timeline Styles */
        .tracking-timeline {
            position: relative;
            padding-left: 60px;
        }

        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 5px;
            bottom: 5px;
            width: 3px;
            background: #e0e0e0;
            border-radius: 3px;
        }

        .timeline-step {
            position: relative;
            padding-bottom: 30px;
            opacity: 0.4;
        }

        .timeline-step:last-child {
            padding-bottom: 0;
        }

        .timeline-step.completed,
        .timeline-step.active {
            opacity: 1;
        }

        .timeline-icon {
            position: absolute;
            left: -48px;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 16px;
            z-index: 1;
            border: 3px solid #fff;
        }

        .timeline-step.completed .timeline-icon {
            background: #13C672;
            color: #fff;
        }

        .timeline-step.active .timeline-icon {
            background: #FEC90F;
            color: #fff;
            animation: pulse-timeline 2s infinite;
        }

        .timeline-step.pending .timeline-icon {
            background: #e9ecef;
            color: #adb5bd;
        }

        .timeline-body {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 3px solid #e0e0e0;
        }

        .timeline-step.completed .timeline-body {
            border-left-color: #13C672;
        }

        .timeline-step.active .timeline-body {
            border-left-color: #FEC90F;
            background: #fffde6;
        }

        @keyframes pulse-timeline {
            0% { box-shadow: 0 0 0 0 rgba(254, 201, 15, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(254, 201, 15, 0); }
            100% { box-shadow: 0 0 0 0 rgba(254, 201, 15, 0); }
        }
    </style>
@endpush
