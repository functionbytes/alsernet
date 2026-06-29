@extends('layouts.theme')

@section('title', 'Editar suscripcion de webhook')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar suscripcion de webhook'])
@endsection

@section('content')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('reviews.webhook-subscriptions.update', $webhook) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar suscripcion de webhook</h5>
                        <small class="text-muted">Modifique el endpoint o los eventos suscritos.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('reviews::webhooks._form', ['webhook' => $webhook])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('reviews.webhook-subscriptions.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es un webhook?</h6>
                    <p class="card-text text-muted">
                        Un webhook envía una notificación HTTP POST a tu URL cuando ocurre un evento, permitiendo integrar con Zapier, Make u otras herramientas de automatizacion.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Eventos disponibles</h6>
                    <ul class="list-unstyled text-muted mb-0">
                        <li class="mb-1"><code>review.created</code> — Nueva reseña detectada</li>
                        <li class="mb-1"><code>reply.published</code> — Respuesta publicada</li>
                        <li><code>review.anomaly</code> — Anomalia en volumen</li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Fallos registrados</h6>
                    <p class="card-text text-muted mb-0">
                        Esta suscripcion registra <strong>{{ $webhook->failure_count }}</strong> fallo(s) de entrega.
                        @if($webhook->last_triggered_at)
                            Último envío: {{ $webhook->last_triggered_at->diffForHumans() }}.
                        @endif
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
