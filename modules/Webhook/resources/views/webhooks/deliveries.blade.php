@extends('layouts.theme')

@section('page_title', 'Entregas de Webhooks')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Entregas de webhooks',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Webhooks', 'url' => route('webhooks.index')],
            ['label' => 'Entregas', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Entregas de webhooks</h5>
                        <p class="small mb-0 text-muted">Monitorea el estado y rendimiento de las entregas de webhooks</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('webhooks.index') }}" class="btn btn-secondary">
                            Ver integraciones
                        </a>
                        <a href="{{ route('webhooks.events') }}" class="btn btn-outline-primary">
                            Ver eventos
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert bg-light border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Funcionalidad en construcción</h6>
                            <p class="mb-0">
                                Esta sección se completará una vez que la tabla <code>webhook_deliveries</code> esté lista.
                                Aquí podrás monitorear todas las entregas de webhooks, incluyendo intentos exitosos, fallidos y reintentos automáticos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('webhooks.deliveries') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por evento o integración..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="status" class="form-label fw-semibold">Estado</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Exitoso</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="integration" class="form-label fw-semibold">Integración</label>
                            <select id="integration" name="integration" class="form-select">
                                <option value="">Todas las integraciones</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if (request('search') || request('status') || request('integration'))
                                <a href="{{ route('webhooks.deliveries') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Empty State --}}
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay entregas disponibles</h5>
                    <p class="text-muted mb-4">
                        Los registros de entregas aparecerán aquí cuando se procesen webhooks.
                        Incluirá información sobre estado de entrega, tiempos de respuesta, reintentos y errores.
                    </p>
                    <a href="{{ route('webhooks.index') }}" class="btn btn-primary">
                        Ver integraciones activas
                    </a>
                </div>
            </div>

        </div>

    </div>

@endsection
