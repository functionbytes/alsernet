@extends('layouts.theme')

@section('title', 'Conectar página de Facebook')

@section('content')

    @include('core::components.card', ['title' => 'Conectar Facebook'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <div class="d-flex align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Conectar página de Facebook</h5>
                                <p class="small mb-0 text-muted">Autoriza el acceso a tu página de Facebook para recibir y enviar mensajes</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body text-center py-5">
                        <div class="round-64 rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center mb-4">
                            <i class="fab fa-facebook fs-1 text-primary"></i>
                        </div>
                        <h4 class="mb-3">Conecta tu página de Facebook</h4>
                        <p class="text-muted mb-4">
                            Haz clic en el botón de abajo para autorizar esta aplicación y acceder a tus páginas de Facebook.
                            Serás redirigido a Facebook para seleccionar qué página deseas conectar.
                        </p>

                        <a href="{{ $oauthUrl }}" class="btn btn-primary btn-lg mb-3">
                            <i class="fab fa-facebook me-2"></i> Conectar con Facebook
                        </a>

                        <div class="mt-4">
                            <a href="{{ route('settings.chat.channels.facebook-pages.index') }}" class="btn btn-secondary">
                                Volver al listado
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header p-3 bg-info-subtle border-bottom border-light">
                        <h6 class="mb-0 fw-semibold"><i class="fa fa-circle-info me-2"></i>Requisitos</h6>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0 ps-3">
                            <li class="mb-2">Debes ser administrador de la página de Facebook</li>
                            <li class="mb-2">La página debe estar publicada (no en modo borrador)</li>
                            <li class="mb-2">Necesitarás otorgar el permiso "pages_messaging"</li>
                            <li>La conexión es segura mediante OAuth 2.0</li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header p-3 bg-success-subtle border-bottom border-light">
                        <h6 class="mb-0 fw-semibold"><i class="fa fa-shield me-2"></i>Privacidad</h6>
                    </div>
                    <div class="card-body">
                        <p class="small mb-0">
                            Solo solicitamos los permisos mínimos necesarios para recibir y enviar mensajes.
                            Tu información personal nunca se almacena ni se comparte.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
