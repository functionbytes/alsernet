@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')
<div class="widget-content">
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Configuración HTTP</h5>
                    <p class="small mb-0 text-muted">Configura los parámetros HTTP para la sincronización con ERP</p>
                </div>
            </div>
        </div>

        <form id="httpConfigForm">
            @csrf

                <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="httpTimeout">Timeout (segundos)</label>
                                <input type="number" class="form-control" id="httpTimeout" name="http_timeout" min="1" max="3600" value="30">
                                <small class="form-text text-muted">Tiempo máximo de espera para las solicitudes HTTP</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="httpRetries">Reintentos</label>
                                <input type="number" class="form-control" id="httpRetries" name="http_retries" min="0" max="10" value="3">
                                <small class="form-text text-muted">Número de reintentos en caso de error</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="httpRetryDelay">Retraso entre reintentos (ms)</label>
                                <input type="number" class="form-control" id="httpRetryDelay" name="http_retry_delay" min="100" max="60000" value="1000">
                                <small class="form-text text-muted">Milisegundos de espera entre reintentos</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="httpVerifySsl">Verificar SSL</label>
                                <select class="form-select" id="httpVerifySsl" name="http_verify_ssl">
                                    <option value="1">Sí</option>
                                    <option value="0">No</option>
                                </select>
                                <small class="form-text text-muted">Verificar certificado SSL en solicitudes HTTPS</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="httpHeaders">Headers personalizados (JSON)</label>
                                <textarea class="form-control font-monospace" id="httpHeaders" name="http_headers" rows="4" placeholder='{ "Authorization": "Bearer token", "X-Custom-Header": "value" }'></textarea>
                                <small class="form-text text-muted">Headers HTTP adicionales a incluir en cada solicitud</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="httpUserAgent">User Agent</label>
                                <input type="text" class="form-control" id="httpUserAgent" name="http_user_agent" placeholder="Mozilla/5.0 (Compatible; SyncBot/1.0)">
                                <small class="form-text text-muted">User Agent personalizado para las solicitudes HTTP</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Configuración de proxy (opcional)</label>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="httpProxyUrl">URL del proxy</label>
                                <input type="text" class="form-control" id="httpProxyUrl" name="http_proxy_url" placeholder="http://proxy.example.com:8080">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="httpProxyAuth">Credenciales del proxy (usuario:contraseña)</label>
                                <input type="password" class="form-control" id="httpProxyAuth" name="http_proxy_auth" placeholder="usuario:contraseña">
                            </div>
                        </div>

                </div>


            <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar configuración </button>
                    <button type="reset" class="btn btn-secondary w-100">Limpiar</button>
            </div>

            </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // Cargar configuración guardada
    function loadConfiguration() {
        // Aquí iría la lógica para cargar la configuración desde la API
        // Por ahora se muestran los valores por defecto
    }

    // Guardar configuración
    $('#httpConfigForm').on('submit', function (e) {
        e.preventDefault();

        // Validar JSON headers si está rellenado
        const headersInput = $('#httpHeaders').val().trim();
        if (headersInput) {
            try {
                JSON.parse(headersInput);
            } catch {
                toastr.error('Headers: JSON inválido');
                return;
            }
        }

        const data = {
            http_timeout: parseInt($('#httpTimeout').val()),
            http_retries: parseInt($('#httpRetries').val()),
            http_retry_delay: parseInt($('#httpRetryDelay').val()),
            http_verify_ssl: parseInt($('#httpVerifySsl').val()),
            http_headers: headersInput ? JSON.parse(headersInput) : null,
            http_user_agent: $('#httpUserAgent').val(),
            http_proxy_url: $('#httpProxyUrl').val(),
            http_proxy_auth: $('#httpProxyAuth').val(),
        };

        // Guardar en localStorage o enviar a la API
        localStorage.setItem('supplier_http_config', JSON.stringify(data));
        toastr.success('Configuración HTTP guardada correctamente');
    });

    // Inicializar
    $(document).ready(function () {
        loadConfiguration();
    });
}());
</script>
@endpush
