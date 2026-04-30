@extends('layouts.theme')

@section('title', 'Nueva suscripcion de webhook')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva suscripcion de webhook'])
@endsection

@section('content')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('reviews.webhook-subscriptions.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva suscripcion de webhook</h5>
                        <small class="text-muted">Configure el endpoint que recibirá notificaciones de eventos de reseñas.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('reviews::webhooks._form', ['webhook' => null])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Crear suscripcion</button>
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
                    <h6 class="card-title mb-3">Secreto HMAC</h6>
                    <p class="card-text text-muted mb-0">
                        Si configuras un secreto, cada petición incluirá el header <code>X-Webhook-Signature</code> con firma <code>HMAC-SHA256</code> para que puedas verificar la autenticidad.
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
            </div>
        </div>

    </div>

@endsection
