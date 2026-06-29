@extends('layouts.theme')

@section('content')

    <div class="row">
        <!-- Formulario principal -->
        <div class="col-lg-8">
            <div class="card">

                <form method="POST" action="{{ route('settings.erp.api-security.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <h5 class="mb-2">Seguridad de la API ERP</h5>
                            <p class="card-subtitle mb-3">
                                Controla cómo se autentican las peticiones a <code>/api/erp/*</code>
                                (customer, products, suppliers, families, ...). Por defecto la API queda
                                abierta y se asume que un firewall externo restringe el acceso.
                            </p>
                        </div>

                        <div class="row">
                            <!-- Toggle: enabled -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label d-block">Modo de protección</label>
                                    <div class="form-check form-switch">
                                        <input
                                            type="hidden"
                                            name="erp_api_auth_enabled"
                                            value="no">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="erp_api_auth_enabled"
                                            name="erp_api_auth_enabled"
                                            value="yes"
                                            @checked(old('erp_api_auth_enabled', $enabled ? 'yes' : 'no') === 'yes')>
                                        <label class="form-check-label" for="erp_api_auth_enabled">
                                            Exigir autenticación
                                        </label>
                                    </div>
                                    <small class="text-muted">
                                        Desactivado: la API queda abierta a quien tenga conectividad
                                        de red al servidor (firewall protege). Activado: se aplica el
                                        guard seleccionado más abajo.
                                    </small>
                                </div>
                            </div>

                            <!-- Guard -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label" for="erp_api_auth_guard">Guard</label>
                                    <select
                                        class="form-select @error('erp_api_auth_guard') is-invalid @enderror"
                                        id="erp_api_auth_guard"
                                        name="erp_api_auth_guard">
                                        @php $g = old('erp_api_auth_guard', $guard); @endphp
                                        <option value="sanctum"   @selected($g === 'sanctum')>Sanctum (Bearer)</option>
                                        <option value="erp_token" @selected($g === 'erp_token')>ErpEndpointToken (header X-Erp-Token)</option>
                                        <option value="both"      @selected($g === 'both')>Ambos (cascada)</option>
                                    </select>
                                    @error('erp_api_auth_guard')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <strong>Sanctum</strong>: usuarios con token Bearer (incluye el
                                        token emitido por <code>erp:issue-bridge-token</code>).<br>
                                        <strong>ErpEndpointToken</strong>: header <code>X-Erp-Token</code>
                                        contra los tokens del modelo (más granular, por endpoint).<br>
                                        <strong>Ambos</strong>: prueba Sanctum primero, fallback a ErpEndpointToken.
                                    </small>
                                </div>
                            </div>

                            <!-- Throttle -->
                            <div class="col-12 col-md-3">
                                <div class="mb-3">
                                    <label class="control-label col-form-label" for="erp_api_throttle">
                                        Throttle <code>/api/erp/*</code>
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control @error('erp_api_throttle') is-invalid @enderror"
                                        id="erp_api_throttle"
                                        name="erp_api_throttle"
                                        value="{{ old('erp_api_throttle', $throttle) }}"
                                        placeholder="60,1"
                                        required>
                                    @error('erp_api_throttle')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    <small class="text-muted">Formato <code>peticiones,minutos</code></small>
                                </div>
                            </div>

                            <!-- Throttle público -->
                            <div class="col-12 col-md-3">
                                <div class="mb-3">
                                    <label class="control-label col-form-label" for="erp_public_token_throttle">
                                        Throttle público token
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control @error('erp_public_token_throttle') is-invalid @enderror"
                                        id="erp_public_token_throttle"
                                        name="erp_public_token_throttle"
                                        value="{{ old('erp_public_token_throttle', $publicTokenThrottle) }}"
                                        placeholder="60,1"
                                        required>
                                    @error('erp_public_token_throttle')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    <small class="text-muted">Para <code>/api/erp/public/{token}/{slug}</code></small>
                                </div>
                            </div>
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success mb-0 mt-2">
                                <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                            </div>
                        @endif
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

        <!-- Sidebar de ayuda -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fa fa-shield-halved me-2"></i> ¿Qué se protege?</h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2"><code>GET /api/erp/customer/{id}/*</code> — datos personales, LOPD, deudas, pedidos</li>
                        <li class="mb-2"><code>GET /api/erp/products/*</code> — catálogo y filtros</li>
                        <li class="mb-2"><code>GET /api/erp/suppliers/*</code> — proveedores y referencias</li>
                        <li class="mb-2"><code>GET /api/erp/families|subfamilies|groups</code> — jerarquía</li>
                    </ul>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fa fa-circle-info me-2"></i> Cuándo activar</h6>
                    <p class="small text-muted mb-2">
                        Si la instancia es accesible desde internet o desde una red en la que no
                        confías al 100 %, activa la autenticación. Si está detrás de un firewall que
                        sólo permite hosts conocidos, puedes dejarlo desactivado.
                    </p>
                    <div class="alert alert-warning mb-0 py-2 px-3">
                        <small class="mb-0">
                            <i class="fa fa-triangle-exclamation me-1"></i>
                            Los cambios de throttle requieren ejecutar
                            <code>php artisan route:cache</code> para tomar efecto.
                        </small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fa fa-key me-2"></i> Emitir token Sanctum</h6>
                    <p class="small text-muted mb-2">
                        Para integrar otra app (ej. el panel Alsernet) con Sanctum:
                    </p>
<pre class="small mb-0" style="background:#f5f5f5;padding:.5rem;border-radius:4px;">php artisan erp:issue-bridge-token \
  --user=system@bridge \
  --label=alsernet-helpdesk</pre>
                </div>
            </div>
        </div>
    </div>

@endsection
