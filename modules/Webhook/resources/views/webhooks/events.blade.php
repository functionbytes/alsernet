@extends('layouts.theme')

@section('page_title', 'Eventos de Webhooks')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Eventos de webhooks',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Webhooks', 'url' => route('webhooks.index')],
            ['label' => 'Eventos', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Eventos de webhooks</h5>
                        <p class="small mb-0 text-muted">Explora los tipos de eventos disponibles para suscripciones</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('webhooks.backups.integrations.index') }}" class="btn btn-secondary">
                            Ver integraciones
                        </a>
                        <a href="{{ route('webhooks.deliveries') }}" class="btn btn-outline-primary">
                            Ver entregas
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-warning border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Funcionalidad en construcción</h6>
                            <p class="mb-0">
                                Esta sección se completará una vez que la tabla <code>webhook_events</code> esté lista.
                                Aquí podrás consultar todos los tipos de eventos disponibles en el sistema, su descripción,
                                payload de ejemplo y qué integraciones están suscritas a cada evento.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('webhooks.events') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-10">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por nombre o categoría de evento..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if (request('search'))
                                <a href="{{ route('webhooks.events') }}" class="btn btn-outline-secondary">
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
                    <h5 class="fw-bold mb-2">No hay eventos disponibles</h5>
                    <p class="text-muted mb-4">
                        Los tipos de eventos aparecerán aquí cuando se registren en el sistema.
                        Podrás ver la lista completa de eventos disponibles para crear suscripciones,
                        junto con su documentación y ejemplos de payload.
                    </p>
                    <a href="{{ route("webhooks.backups.integrations.index") }}" class="btn btn-primary">
                        Configurar integraciones
                    </a>
                </div>
            </div>

        </div>

    </div>

@endsection
