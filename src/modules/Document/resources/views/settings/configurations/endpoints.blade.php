@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Endpoints / Integraciones'])

    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> Por favor, corrige los siguientes errores:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('settings.documents.configurations.endpoints.update') }}"
          method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-body">

                <div class="alert alert-info py-2 px-3 mb-4" role="alert">
                    <i class="fas fa-info-circle me-1"></i>
                    Configura las URLs de los servicios externos que el módulo de documentos llama automáticamente.
                    Deja vacío para deshabilitar un endpoint.
                </div>

                {{-- ── ERP: Documentación OK ─────────────────────────────── --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">Documentación OK</h6>
                    <p class="text-muted small mb-3">
                        Se llama cuando un documento completa <strong>todas las etapas de validación</strong> y queda en estado
                        <span class="badge bg-success">Aprobado</span>.
                        Envía <code>identificadororigen=&lt;order_id&gt;</code> como form data (<code>application/x-www-form-urlencoded</code>).
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="erp_documentacion_ok_url">URL del endpoint</label>
                        <div class="input-group">
                            <input type="url"
                                   class="form-control @error('erp_documentacion_ok_url') is-invalid @enderror"
                                   id="erp_documentacion_ok_url"
                                   name="erp_documentacion_ok_url"
                                   placeholder="http://servidor:8080/api/ruta/"
                                   value="{{ old('erp_documentacion_ok_url', $endpoints['erp_documentacion_ok_url']) }}">
                            <button type="button"
                                    class="btn btn-secondary"
                                    title="Probar conexión"
                                    onclick="testEndpoint('erp_documentacion_ok_url', 'test-order-id-documentacion-ok', 'test-result-documentacion-ok')">
                                <i class="fas fa-plug"></i>
                            </button>
                            @error('erp_documentacion_ok_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted d-block mt-1">
                            Ejemplo: <code>http://interges:8080/api-gestion/pedido-cliente-documentacion-ok/</code>
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label" for="test-order-id-documentacion-ok">
                                ID de pedido de prueba <span class="text-muted">(opcional)</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="test-order-id-documentacion-ok"
                                   placeholder="0">
                            <small class="text-muted d-block mt-1">
                                Al probar se enviará <code>identificadororigen=&lt;valor&gt;</code> como form data (por defecto <code>"0"</code>).
                            </small>
                        </div>
                    </div>

                    <div id="test-result-documentacion-ok"></div>
                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.documents.configurations') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        function testEndpoint(inputId, orderInputId, resultContainerId) {
            var url = document.getElementById(inputId).value.trim();
            var orderInput = document.getElementById(orderInputId);
            var orderId = orderInput ? orderInput.value.trim() : '';
            var $result = document.getElementById(resultContainerId);

            if (!url) {
                $result.innerHTML = '<div class="alert alert-warning py-2 px-3 mt-2">Ingresa una URL antes de probar.</div>';
                return;
            }

            var payload = { identificadororigen: orderId || '0' };

            $result.innerHTML = '<div class="alert alert-secondary py-2 px-3 mt-2"><i class="fas fa-spinner fa-spin me-1"></i> Probando con <code>' + JSON.stringify(payload) + '</code>...</div>';

            fetch('{{ route("settings.documents.configurations.endpoints.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ url: url, order_id: orderId }),
            })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var sent = data.sent ? ' &middot; Enviado: <code>' + JSON.stringify(data.sent) + '</code>' : '';
                    if (data.success) {
                        $result.innerHTML = '<div class="alert alert-success py-2 px-3 mt-2"><i class="fas fa-check-circle me-1"></i> Conexión exitosa (HTTP ' + data.status + ')' + sent + '</div>';
                    } else {
                        var statusTxt = data.status ? ' (HTTP ' + data.status + ')' : '';
                        $result.innerHTML = '<div class="alert alert-danger py-2 px-3 mt-2"><i class="fas fa-times-circle me-1"></i> ' + (data.message || 'Error al conectar') + statusTxt + sent + '</div>';
                    }
                })
                .catch(function(err) {
                    $result.innerHTML = '<div class="alert alert-danger py-2 px-3 mt-2"><i class="fas fa-times-circle me-1"></i> Error de red: ' + err.message + '</div>';
                });
        }
    </script>
@endpush
