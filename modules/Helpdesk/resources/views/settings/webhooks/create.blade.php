@extends('layouts.theme')

@section('title', 'Nuevo webhook')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo webhook'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.webhooks.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo webhook</h5>
                        <small class="text-muted">Configura una URL externa para recibir notificaciones de eventos</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.webhooks._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar webhook
                        </button>
                        <a href="{{ route('settings.helpdesk.webhooks.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los webhooks</h6>
                    <p class="card-text text-muted small">
                        Los webhooks permiten que sistemas externos reciban notificaciones automaticas cuando ocurren eventos en Helpdesk, similar a como funciona Zapier o Zendesk.
                    </p>
                    <hr>
                    <h6 class="mb-2">Firma HMAC</h6>
                    <p class="card-text text-muted small">
                        Si configuras un secreto, cada peticion incluira la cabecera <code>X-Helpdesk-Signature</code> con un hash HMAC-SHA256 para validar la autenticidad.
                    </p>
                    <hr>
                    <h6 class="mb-2">Cabeceras personalizadas</h6>
                    <p class="card-text text-muted small">
                        Puedes enviar cabeceras adicionales en formato JSON. Ejemplo:
                    </p>
                    <pre class="bg-light p-2 rounded small">{"Authorization": "Bearer tu-token"}</pre>
                </div>
            </div>
        </div>
    </div>

@endsection
