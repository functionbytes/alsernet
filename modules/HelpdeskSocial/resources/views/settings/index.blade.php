@extends('layouts.theme')

@section('title', 'Configuración Social')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración Social'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Configuración Social</h1>

            <div class="alert alert-info">
                Estos valores se leen de <code>.env</code> / <code>config/helpdesksocial.php</code> al arrancar el
                servicio. Para cambiarlos, edita la configuración y reinicia los workers — no son editables desde
                aquí.
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Auto-respuesta</h5>
                    <dl class="row">
                        <dt class="col-sm-4">Activar auto-respuesta</dt>
                        <dd class="col-sm-8">{{ ($config['auto_reply']['enabled'] ?? true) ? 'Sí' : 'No' }}</dd>
                    </dl>

                    <h5 class="mb-3 mt-4">Clasificación de intenciones</h5>
                    <dl class="row">
                        <dt class="col-sm-4">Activar clasificación</dt>
                        <dd class="col-sm-8">{{ ($config['intent_classification']['enabled'] ?? true) ? 'Sí' : 'No' }}</dd>
                        <dt class="col-sm-4">Proveedor</dt>
                        <dd class="col-sm-8">{{ $config['intent_classification']['provider'] ?? '—' }}</dd>
                    </dl>

                    <h5 class="mb-3 mt-4">Comentarios</h5>
                    <dl class="row">
                        <dt class="col-sm-4">Sincronización de comentarios</dt>
                        <dd class="col-sm-8">{{ ($config['comments']['enabled'] ?? true) ? 'Sí' : 'No' }}</dd>
                    </dl>

                    <h5 class="mb-3 mt-4">Integraciones</h5>
                    <dl class="row">
                        <dt class="col-sm-4">Meta (Facebook + Instagram)</dt>
                        <dd class="col-sm-8">{{ ($config['integrations']['meta']['enabled'] ?? true) ? 'Sí' : 'No' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
