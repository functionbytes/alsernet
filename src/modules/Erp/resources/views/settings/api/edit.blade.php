@extends('layouts.theme')

@section('content')

    <div class="row">
        <!-- Formulario Principal -->
        <div class="col-lg-8">
            <div class="card">

                <form id="formErpApi" method="POST" action="{{ route('settings.erp.api.update') }}">

                    {{ csrf_field() }}
                    @method('POST')

                    <div class="card-body">
                        <div class="row">
                            <h5 class="mb-2">Configuración API del ERP</h5>
                            <p class="card-subtitle mb-3">
                                Este espacio está diseñado para que configures las URLs de los servicios HTTP del ERP. Estos datos serán utilizados para integrar funcionalidades mediante API REST, sincronización y otros protocolos.
                            </p>
                        </div>

                        <div class="row">

                            <!-- API REST URL -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">API REST</label>
                                    <input
                                        type="url"
                                        class="form-control @error('erp_api_url') is-invalid @enderror"
                                        id="erp_api_url"
                                        name="erp_api_url"
                                        value="{{ old('erp_api_url', $settings['erp_api_url'] ?? '') }}"
                                        placeholder="http://192.168.1.3:58002/api"
                                        required>
                                    @error('erp_api_url')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Synchronization URL -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Sincronización</label>
                                    <input
                                        type="url"
                                        class="form-control @error('erp_sync_url') is-invalid @enderror"
                                        id="erp_sync_url"
                                        name="erp_sync_url"
                                        value="{{ old('erp_sync_url', $settings['erp_sync_url'] ?? '') }}"
                                        placeholder="http://223.1.1.18:9000/integracion"
                                        required>
                                    @error('erp_sync_url')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- XML-RPC URL -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">XML-RPC (Opcional)</label>
                                    <input
                                        type="url"
                                        class="form-control @error('erp_xmlrpc_url') is-invalid @enderror"
                                        id="erp_xmlrpc_url"
                                        name="erp_xmlrpc_url"
                                        value="{{ old('erp_xmlrpc_url', $settings['erp_xmlrpc_url'] ?? '') }}"
                                        placeholder="http://192.168.1.6:8081">
                                    @error('erp_xmlrpc_url')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- SMS URL -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">SMS (Opcional)</label>
                                    <input
                                        type="url"
                                        class="form-control @error('erp_sms_url') is-invalid @enderror"
                                        id="erp_sms_url"
                                        name="erp_sms_url"
                                        value="{{ old('erp_sms_url', $settings['erp_sms_url'] ?? '') }}"
                                        placeholder="http://213.134.40.126:8080">
                                    @error('erp_sms_url')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Timeout Configuration -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Timeout de Conexión (segundos)</label>
                                    <input
                                        type="number"
                                        class="form-control @error('erp_connect_timeout') is-invalid @enderror"
                                        id="erp_connect_timeout"
                                        name="erp_connect_timeout"
                                        value="{{ old('erp_connect_timeout', $settings['erp_connect_timeout'] ?? 30) }}"
                                        min="1"
                                        max="300"
                                        placeholder="30">
                                    @error('erp_connect_timeout')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="card-footer">
                            <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                                Guardar
                            </button>
                            <a href="{{ route('settings.erp.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                                Volver
                            </a>
                    </div>

                </form>
            </div>
        </div>

        <!-- Sidebar de Ayuda -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fa fa-circle-info me-2"></i> Información importante</h6>

                    <div class="mb-3">
                        <small class="text-muted d-block mb-2">
                            <strong>¿Qué configuras aquí?</strong>
                        </small>
                        <ul class="small text-muted mb-0 ps-3">
                            <li class="mb-2">URLs de los servicios HTTP del ERP</li>
                            <li class="mb-2">API REST y Sincronización son obligatorios</li>
                            <li class="mb-2">XML-RPC y SMS son opcionales</li>
                            <li class="mb-2">El timeout por defecto es 30 segundos</li>
                        </ul>
                    </div>

                    <div class="alert alert-info mb-0 py-2 px-3">
                        <small class="mb-0">
                            <i class="fa fa-lightbulb me-1"></i>
                            Asegúrate de que todas las URLs sean válidas y accesibles desde tu servidor.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Estado de Servicios -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Estado de servicios</h6>
                        <button id="checkServicesBtn" class="btn btn-sm btn-primary">
                            <i class="fas fa-sync-alt"></i> Verificar
                        </button>
                    </div>

                    <div id="servicesStatus" class="row g-2">
                        <div class="col-12">
                            <small class="text-muted">Cargando estado de servicios...</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
$(function() {
    const $checkBtn = $('#checkServicesBtn');
    const $statusContainer = $('#servicesStatus');

    // Cargar estado de servicios al abrir la página
    checkServices();

    $checkBtn.on('click', function() {
        checkServices();
    });

    function checkServices() {
        $checkBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verificando...');
        $statusContainer.html('<div class="col-md-6"><small class="text-muted">Cargando estado de servicios...</small></div>');

        $.ajax({
            url: "{{ route('settings.erp.api.check-services') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                let html = '';
                const services = response.services || {};

                for (const [name, isOnline] of Object.entries(services)) {
                    const badgeClass = isOnline ? 'bg-success' : 'bg-danger';
                    const icon = isOnline ? 'fa-check-circle' : 'fa-times-circle';
                    const status = isOnline ? 'Online' : 'Offline';

                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body py-2 px-3">
                                    <small class="text-muted d-block">${name}</small>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas ${icon} text-${isOnline ? 'success' : 'danger'}"></i>
                                        <span class="badge ${badgeClass}">${status}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                $statusContainer.html(html);

                if (response.success) {
                    toastr.success(response.message, 'Verificación Exitosa');
                } else {
                    toastr.warning(response.message, 'Algunos Servicios No Disponibles');
                }
            },
            error: function(xhr) {
                $statusContainer.html('<div class="col-md-6"><div class="alert alert-danger small">Error al verificar servicios</div></div>');
                toastr.error('Error al verificar servicios', 'Error');
            },
            complete: function() {
                $checkBtn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Verificar');
            }
        });
    }
});
</script>
@endpush

@endsection
