@extends('layouts.theme')

@section('title', 'Nueva integracion de Slack')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva integracion de Slack'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.slack-integrations.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva integracion de Slack</h5>
                        <small class="text-muted">Conecta un canal de Slack para recibir alertas automaticas</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.slack-integrations._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar integracion
                        </button>
                        <a href="{{ route('settings.helpdesk.slack-integrations.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Como obtener la URL del webhook</h6>
                    <ol class="small text-muted ps-3 mb-0">
                        <li class="mb-2">Ve a <strong>api.slack.com/apps</strong> y selecciona tu app (o crea una nueva)</li>
                        <li class="mb-2">En el menu lateral, busca <strong>Incoming Webhooks</strong></li>
                        <li class="mb-2">Activa los webhooks entrantes y haz clic en <strong>Add New Webhook to Workspace</strong></li>
                        <li class="mb-2">Selecciona el canal destino y autoriza</li>
                        <li>Copia la URL generada y pegala aqui</li>
                    </ol>
                    <hr>
                    <h6 class="mb-2">Seguridad</h6>
                    <p class="small text-muted mb-0">
                        La URL del webhook se guarda cifrada en la base de datos. Nunca se mostrara en texto plano despues de guardada.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
