@extends('layouts.theme')

@section('title', 'Detalle de webhook log')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Detalle de webhook'])
@endsection

@section('content')
    <div class="widget-content">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Webhook #{{ $webhookLog->id }}</h5>
                        <p class="small mb-0 text-muted">{{ $webhookLog->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <a href="{{ route('ecommerce.webhook-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Evento</dt>
                    <dd class="col-sm-9"><code>{{ $webhookLog->event }}</code></dd>

                    <dt class="col-sm-3">URL destino</dt>
                    <dd class="col-sm-9 text-break">{{ $webhookLog->url }}</dd>

                    <dt class="col-sm-3">Estado</dt>
                    <dd class="col-sm-9">
                        @if($webhookLog->success)
                            <span class="badge bg-success">Enviado correctamente</span>
                        @else
                            <span class="badge bg-danger">Fallido</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Status HTTP</dt>
                    <dd class="col-sm-9">{{ $webhookLog->response_status ?? '—' }}</dd>

                    <dt class="col-sm-3">Intentos</dt>
                    <dd class="col-sm-9">{{ $webhookLog->attempts }}</dd>

                    @if($webhookLog->error)
                        <dt class="col-sm-3">Error</dt>
                        <dd class="col-sm-9">
                            <div class="alert alert-danger small mb-0">{{ $webhookLog->error }}</div>
                        </dd>
                    @endif
                </dl>

                <h6 class="mt-4 mb-2 fw-bold">Payload enviado</h6>
                <pre class="bg-light p-3 rounded small overflow-auto" style="max-height: 400px;">{{ json_encode($webhookLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                @if($webhookLog->response_body)
                    <h6 class="mt-4 mb-2 fw-bold">Respuesta del servidor</h6>
                    <pre class="bg-light p-3 rounded small overflow-auto" style="max-height: 400px;">{{ $webhookLog->response_body }}</pre>
                @endif
            </div>
        </div>
    </div>
@endsection
