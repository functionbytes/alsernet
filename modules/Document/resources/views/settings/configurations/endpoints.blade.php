@extends('layouts.theme')

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/document/css/endpoints.css') }}?v={{ @filemtime(public_path('modules/document/css/endpoints.css')) }}">
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Endpoints / Integraciones'])
@endsection

@section('content')
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
                        <span class="badge bg-primary">Aprobado</span>.
                        Envía <code>identificadororigen=&lt;order_id&gt;</code> como form data (<code>application/x-www-form-urlencoded</code>).
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="erp_documentacion_ok_url">URL del endpoint</label>
                        <input type="url"
                               class="form-control @error('erp_documentacion_ok_url') is-invalid @enderror"
                               id="erp_documentacion_ok_url"
                               name="erp_documentacion_ok_url"
                               placeholder="http://servidor:8080/api/ruta/"
                               value="{{ old('erp_documentacion_ok_url', $endpoints['erp_documentacion_ok_url']) }}">
                        @error('erp_documentacion_ok_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">
                            Ejemplo: <code>http://interges:8080/api-gestion/pedido-cliente-documentacion-ok/</code>
                        </small>
                    </div>

                    {{-- Zona de prueba. Va en un bloque aparte porque NADA de lo que
                         hay aquí se guarda: son valores de usar y tirar para lanzar una
                         llamada de prueba contra la URL de arriba. Antes iban sueltos
                         debajo del campo y no había forma de distinguir lo que se
                         persiste de lo que no. --}}
                    <div class="endpoint-test">
                        <div class="endpoint-test__head">
                            <span class="fw-semibold">Probar este endpoint</span>
                            <small class="text-muted">Estos valores no se guardan</small>
                        </div>

                        <div class="mb-3">
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

                        <button type="button"
                                class="btn btn-secondary"
                                onclick="testEndpoint('erp_documentacion_ok_url', 'test-order-id-documentacion-ok', 'test-result-documentacion-ok')">
                            Probar conexión
                        </button>

                        <div id="test-result-documentacion-ok" class="mt-3"></div>
                    </div>
                </div>

                <hr class="my-4">

                {{-- ── ERP: Modelo (nombre/descripción) ─────────────────────────────── --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">Modelo (nombre / descripción)</h6>
                    <p class="text-muted small mb-3">
                        Se llama al <strong>aprobar contenido</strong> en el módulo Proveedores, para actualizar el
                        nombre y la descripción del modelo en el ERP.
                        Envía <code>idmodelo</code>, <code>nombre</code>, <code>descripcion</code> y <code>publicar</code> como form data.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="erp_modelo_url">URL del endpoint</label>
                        <input type="url"
                               class="form-control @error('erp_modelo_url') is-invalid @enderror"
                               id="erp_modelo_url"
                               name="erp_modelo_url"
                               placeholder="http://servidor:8080/api/ruta/"
                               value="{{ old('erp_modelo_url', $endpoints['erp_modelo_url']) }}">
                        @error('erp_modelo_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">
                            Ejemplo: <code>http://interges:8080/api-gestion/modelo/</code>
                        </small>
                    </div>

                    <div class="endpoint-test">
                        <div class="endpoint-test__head">
                            <span class="fw-semibold">Probar este endpoint</span>
                            <small class="text-muted">Estos valores no se guardan</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="test-idmodelo">
                                ID de modelo de prueba <span class="text-muted">(opcional)</span>
                            </label>
                            <input type="text" class="form-control" id="test-idmodelo" placeholder="0">
                            <small class="text-muted d-block mt-1">
                                Al probar se enviará <code>idmodelo=&lt;valor&gt;&amp;nombre=&amp;descripcion=&amp;publicar=0</code> (por defecto <code>"0"</code>, sin tocar nombre/descripción).
                            </small>
                        </div>

                        <button type="button" class="btn btn-secondary" onclick="testModeloEndpoint()">
                            Probar conexión
                        </button>

                        <div id="test-result-modelo" class="mt-3"></div>
                    </div>
                </div>

                <hr class="my-4">

                {{-- ── ERP: Asignar característica ─────────────────────────────── --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">Asignar característica</h6>
                    <p class="text-muted small mb-3">
                        Se llama al <strong>añadir una característica</strong> a un modelo o variante (modal de
                        Características, módulo Proveedores). Envía <code>id_caracteristica</code>+<code>idmodelo</code>
                        (a nivel modelo) o <code>id_caracteristica</code>+<code>id_valor</code>+<code>idarticulo</code>
                        (a nivel variante) como form data.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="erp_caracteristica_url">URL del endpoint</label>
                        <input type="url"
                               class="form-control @error('erp_caracteristica_url') is-invalid @enderror"
                               id="erp_caracteristica_url"
                               name="erp_caracteristica_url"
                               placeholder="http://servidor:8080/api/ruta/"
                               value="{{ old('erp_caracteristica_url', $endpoints['erp_caracteristica_url']) }}">
                        @error('erp_caracteristica_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">
                            Ejemplo: <code>http://interges:8080/api-gestion/asignar-caracteristica/</code>
                        </small>
                    </div>

                    {{-- Este endpoint tiene DOS casos (modelo y variante) con campos
                         distintos, así que los botones se agrupan por caso en vez de
                         quedar sueltos al final: cada uno queda junto a los campos que
                         usa, y se ve cuál rellenar para cada prueba. --}}
                    <div class="endpoint-test">
                        <div class="endpoint-test__head">
                            <span class="fw-semibold">Probar este endpoint</span>
                            <small class="text-muted">Estos valores no se guardan</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="test-id-caracteristica">
                                ID de característica de prueba <span class="text-muted">(opcional)</span>
                            </label>
                            <input type="text" class="form-control" id="test-id-caracteristica" placeholder="0">
                            <small class="text-muted d-block mt-1">Común a los dos casos.</small>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <div class="endpoint-test__case h-100">
                                    <span class="endpoint-test__case-title">Caso Modelo</span>
                                    <div class="mb-3">
                                        <label class="form-label" for="test-idmodelo-caracteristica">ID de modelo</label>
                                        <input type="text" class="form-control" id="test-idmodelo-caracteristica" placeholder="0">
                                    </div>
                                    <button type="button" class="btn btn-secondary w-100" onclick="testCaracteristicaEndpoint('modelo')">
                                        Probar caso Modelo
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="endpoint-test__case h-100">
                                    <span class="endpoint-test__case-title">Caso Variante</span>
                                    <div class="mb-3">
                                        <label class="form-label" for="test-idarticulo-caracteristica">ID de artículo</label>
                                        <input type="text" class="form-control" id="test-idarticulo-caracteristica" placeholder="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="test-idvalor-caracteristica">ID de valor</label>
                                        <input type="text" class="form-control" id="test-idvalor-caracteristica" placeholder="0">
                                    </div>
                                    <button type="button" class="btn btn-secondary w-100" onclick="testCaracteristicaEndpoint('articulo')">
                                        Probar caso Variante
                                    </button>
                                </div>
                            </div>
                        </div>

                        <small class="text-muted d-block mt-3">
                            Se envían siempre las 4 claves (vacía la que no aplica en cada caso) — el servidor real
                            necesita las 4 presentes, aunque estén en blanco, o no persiste el dato.
                        </small>

                        <div id="test-result-caracteristica" class="mt-3"></div>
                    </div>
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
        // Núcleo compartido: hace POST a la ruta de test genérica con la URL configurada
        // en `inputId` y el `payload` ya construido por el caller (varía por endpoint).
        function runEndpointTest(inputId, payload, resultContainerId) {
            var url = document.getElementById(inputId).value.trim();
            var $result = document.getElementById(resultContainerId);

            if (!url) {
                $result.innerHTML = '<div class="alert alert-warning py-2 px-3 mt-2">Ingresa una URL antes de probar.</div>';
                return;
            }

            $result.innerHTML = '<div class="alert alert-secondary py-2 px-3 mt-2"><i class="fas fa-spinner fa-spin me-1"></i> Probando con <code>' + JSON.stringify(payload) + '</code>...</div>';

            fetch('{{ route("settings.documents.configurations.endpoints.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ url: url, payload: payload }),
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

        function testEndpoint(inputId, orderInputId, resultContainerId) {
            var orderInput = document.getElementById(orderInputId);
            var orderId = orderInput ? orderInput.value.trim() : '';
            runEndpointTest(inputId, { identificadororigen: orderId || '0' }, resultContainerId);
        }

        function testModeloEndpoint() {
            var idmodelo = document.getElementById('test-idmodelo').value.trim() || '0';
            // Sin nombre/descripcion: no arriesga sobrescribir datos reales del modelo de prueba.
            runEndpointTest('erp_modelo_url', { idmodelo: idmodelo, nombre: '', descripcion: '', publicar: '0' }, 'test-result-modelo');
        }

        function testCaracteristicaEndpoint(tipo) {
            var idCaracteristica = document.getElementById('test-id-caracteristica').value.trim() || '0';
            // El servidor real necesita las 4 claves siempre presentes (vacía la que no aplica),
            // aunque estén en blanco — sin esto responde 200 pero no persiste (ver memoria 14-ago-2026).
            var payload = tipo === 'articulo'
                ? {
                    id_caracteristica: idCaracteristica,
                    id_valor: document.getElementById('test-idvalor-caracteristica').value.trim() || '0',
                    idmodelo: '',
                    idarticulo: document.getElementById('test-idarticulo-caracteristica').value.trim() || '0',
                }
                : {
                    id_caracteristica: idCaracteristica,
                    id_valor: '',
                    idmodelo: document.getElementById('test-idmodelo-caracteristica').value.trim() || '0',
                    idarticulo: '',
                };
            runEndpointTest('erp_caracteristica_url', payload, 'test-result-caracteristica');
        }
    </script>
@endpush
