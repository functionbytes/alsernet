@extends('layouts.theme')

@section('title', 'Editar webhook')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar webhook'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.webhooks.update', $webhook) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar webhook</h5>
                        <small class="text-muted">{{ $webhook->name }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.webhooks._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.webhooks.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Estadisticas</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Envios exitosos</span>
                        <span class="fw-semibold text-success">{{ number_format($webhook->success_count) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Envios fallidos</span>
                        <span class="fw-semibold text-dark">{{ number_format($webhook->failure_count) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Ultimo envio</span>
                        <span class="small">{{ $webhook->last_triggered_at?->diffForHumans() ?? 'Nunca' }}</span>
                    </div>
                    @if($webhook->last_error)
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Ultimo error</h6>
                </div>
                <div class="card-body">
                    <p class="small text-dark mb-0">{{ $webhook->last_error }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
