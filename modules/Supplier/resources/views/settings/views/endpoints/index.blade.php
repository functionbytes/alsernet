@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Endpoints / Integraciones ERP'])

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

    <form action="{{ route('settings.suppliers.endpoints.update') }}"
          method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-body">

                <div class="alert alert-info py-2 px-3 mb-4" role="alert">
                    <i class="fas fa-info-circle me-1"></i>
                    Configura las URLs de los servicios externos que el módulo de proveedores utiliza para sincronizar con el ERP.
                    Deja vacío para deshabilitar un endpoint.
                </div>

                {{-- ── URL interna del ERP (api/erp) ───────────────────── --}}
                <div class="mb-4 pb-4 border-bottom">
                    <h6 class="mb-1 fw-bold text-dark">URL interna del ERP</h6>
                    <p class="text-muted small mb-3">
                        URL base que el servidor utiliza internamente para llamar a <code>/api/erp/*</code>.
                        Por defecto apunta al contenedor nginx de Docker. Cámbiala si el dominio de sincronización es diferente (p.ej. en producción).
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="erp_internal_url">URL base</label>
                        <input type="url"
                               class="form-control @error('erp_internal_url') is-invalid @enderror"
                               id="erp_internal_url"
                               name="erp_internal_url"
                               placeholder="http://nginx"
                               value="{{ old('erp_internal_url', $endpoints['erp_internal_url']) }}">
                        @error('erp_internal_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">
                            Ejemplos: <code>http://nginx</code> &nbsp;·&nbsp; <code>http://localhost:8080</code> &nbsp;·&nbsp; <code>https://midominio.com</code>
                        </small>
                    </div>
                </div>

                {{-- ── ERP: Publicar modelo ──────────────────────────────── --}}
                <div class="mb-2">
                    <h6 class="mb-1 fw-bold text-dark">Publicar modelo</h6>
                    <p class="text-muted small mb-3">
                        Se llama para publicar o actualizar la descripción de un modelo en el ERP.
                        Envía <code>idmodelo</code>, <code>nombre</code>, <code>descripcion</code>, <code>publicar</code> y <code>marca</code> (si disponible)
                        como form data (<code>application/x-www-form-urlencoded</code>) via <span class="badge bg-secondary">POST</span>.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="erp_modelo_url">URL del endpoint</label>
                        <div class="input-group">
                            <input type="url"
                                   class="form-control @error('erp_modelo_url') is-invalid @enderror"
                                   id="erp_modelo_url"
                                   name="erp_modelo_url"
                                   placeholder="http://servidor:8080/api/ruta/"
                                   value="{{ old('erp_modelo_url', $endpoints['erp_modelo_url']) }}">
                            <button type="button"
                                    class="btn btn-secondary"
                                    id="btn-toggle-test"
                                    title="Probar conexión"
                                    onclick="toggleTestForm()">
                                <i class="fas fa-plug me-1"></i>
                            </button>
                            @error('erp_modelo_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted d-block mt-1">
                            Ejemplo: <code>http://interges:8080/api-gestion/modelo/</code>
                        </small>
                    </div>

                    {{-- Formulario de prueba inline --}}
                    <div class="d-none" id="test-form-modelo">
                        <div class="card card-body bg-light border mb-3">
                            <p class="text-muted small mb-3">
                                Introduce los datos de prueba para verificar la conexión con el endpoint.
                                Todos los campos son enviados al ERP tal como se muestran.
                            </p>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold" for="test_idmodelo">
                                        idmodelo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control bg-white"
                                           id="test_idmodelo"
                                           placeholder="Ej: 100007627">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold" for="test_nombre">nombre</label>
                                    <input type="text"
                                           class="form-control bg-white"
                                           id="test_nombre"
                                           placeholder="Ej: SILLA WESTERN MINI">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold" for="test_marca">marca</label>
                                    <input type="text"
                                           class="form-control bg-white"
                                           id="test_marca"
                                           placeholder="Ej: 141">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold" for="test_publicar">publicar</label>
                                    <select class="form-select bg-white" id="test_publicar">
                                        <option value="1" selected>1 — Sí</option>
                                        <option value="0">0 — No</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="test_descripcion">descripcion</label>
                                    <textarea class="form-control bg-white"
                                              id="test_descripcion"
                                              rows="3"
                                              placeholder="Descripción larga del producto..."></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button"
                                        class="btn btn-primary w-100"
                                        onclick="testModeloEndpoint()">
                                    Enviar prueba
                                </button>
                            </div>
                            <div id="test-result-modelo" class="mt-2"></div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.suppliers.sync.config.http') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
<script>
function toggleTestForm() {
    var panel = document.getElementById('test-form-modelo');
    var btn   = document.getElementById('btn-toggle-test');
    var hidden = panel.classList.contains('d-none');

    panel.classList.toggle('d-none', !hidden);
    btn.classList.toggle('btn-secondary', !hidden);
    btn.classList.toggle('btn-primary', hidden);
}

function testModeloEndpoint() {
    var url      = document.getElementById('erp_modelo_url').value.trim();
    var idmodelo = document.getElementById('test_idmodelo').value.trim();
    var nombre   = document.getElementById('test_nombre').value.trim();
    var marca    = document.getElementById('test_marca').value.trim();
    var desc     = document.getElementById('test_descripcion').value.trim();
    var publicar = document.getElementById('test_publicar').value;
    var $result  = document.getElementById('test-result-modelo');

    if (!url) {
        $result.innerHTML = '<div class="alert alert-warning py-2 px-3 mt-2">Ingresa la URL del endpoint antes de probar.</div>';
        return;
    }

    if (!idmodelo) {
        $result.innerHTML = '<div class="alert alert-warning py-2 px-3 mt-2">El campo <strong>idmodelo</strong> es obligatorio para la prueba.</div>';
        return;
    }

    $result.innerHTML = '<div class="alert alert-secondary py-2 px-3 mt-2"><i class="fas fa-spinner fa-spin me-1"></i> Enviando prueba...</div>';

    var payload = {
        url:         url,
        idmodelo:    idmodelo,
        descripcion: desc,
        publicar:    parseInt(publicar, 10),
    };
    if (nombre) payload.nombre = nombre;
    if (marca)  payload.marca  = marca;

    fetch('{{ route("settings.suppliers.endpoints.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload),
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var sentStr = data.sent ? '<br><small class="d-block mt-1">Enviado: <code>' + JSON.stringify(data.sent) + '</code></small>' : '';
        if (data.success) {
            $result.innerHTML = '<div class="alert alert-success py-2 px-3 mt-2"><i class="fas fa-check-circle me-1"></i> Conexión exitosa (HTTP ' + data.status + ')' + sentStr + '</div>';
        } else {
            var statusTxt = data.status ? ' (HTTP ' + data.status + ')' : '';
            $result.innerHTML = '<div class="alert alert-danger py-2 px-3 mt-2"><i class="fas fa-times-circle me-1"></i> ' + (data.message || 'Error al conectar') + statusTxt + sentStr + '</div>';
        }
    })
    .catch(function(err) {
        $result.innerHTML = '<div class="alert alert-danger py-2 px-3 mt-2"><i class="fas fa-times-circle me-1"></i> Error de red: ' + err.message + '</div>';
    });
}
</script>
@endpush
