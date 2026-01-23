@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="fas fa-edit me-2"></i>Editar configuración de Reverb
            </h6>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading">Error al guardar</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('manager.backups.reverb.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="host" class="form-label">Host de broadcasting</label>
                            <input type="text" class="form-control @error('host') is-invalid @enderror"
                                id="host" name="host" value="{{ old('host', $settings['host']) }}"
                                placeholder="localhost o ws.example.com" required>
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="port" class="form-label">Puerto</label>
                            <input type="number" class="form-control @error('port') is-invalid @enderror"
                                id="port" name="port" value="{{ old('port', $settings['port']) }}"
                                min="1" max="65535" required>
                            @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="scheme" class="form-label">Esquema</label>
                            <select class="form-select @error('scheme') is-invalid @enderror"
                                id="scheme" name="scheme" required>
                                <option value="http" {{ old('scheme', $settings['scheme']) === 'http' ? 'selected' : '' }}>
                                    HTTP (sin encriptación)
                                </option>
                                <option value="https" {{ old('scheme', $settings['scheme']) === 'https' ? 'selected' : '' }}>
                                    HTTPS (encriptado)
                                </option>
                                <option value="ws" {{ old('scheme', $settings['scheme']) === 'ws' ? 'selected' : '' }}>
                                    WS (WebSocket)
                                </option>
                                <option value="wss" {{ old('scheme', $settings['scheme']) === 'wss' ? 'selected' : '' }}>
                                    WSS (WebSocket Secure)
                                </option>
                            </select>
                            @error('scheme')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="server_host" class="form-label">Host del servidor</label>
                                <input type="text" class="form-control @error('server_host') is-invalid @enderror"
                                    id="server_host" name="server_host" value="{{ old('server_host', $settings['server_host']) }}"
                                    placeholder="0.0.0.0" required>
                                <small class="text-muted">Dirección en la que escucha el servidor Reverb</small>
                                @error('server_host')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="server_port" class="form-label">Puerto del servidor</label>
                                <input type="number" class="form-control @error('server_port') is-invalid @enderror"
                                    id="server_port" name="server_port" value="{{ old('server_port', $settings['server_port']) }}"
                                    min="1" max="65535" required>
                                <small class="text-muted">Puerto donde escucha el servidor</small>
                                @error('server_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading">Información importante</h6>
                            <ul class="mb-0">
                                <li>El <strong>host de broadcasting</strong> es donde los clientes se conectarán</li>
                                <li>El <strong>host del servidor</strong> es donde escucha el proceso Reverb (generalmente 0.0.0.0)</li>
                                <li>Usa <strong>WSS</strong> para conexiones seguras en producción</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar cambios
                            </button>
                            <a href="{{ route('manager.backups.reverb.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title mb-3">Valores actuales</h6>

                    <div class="mb-3">
                        <label class="text-muted">Host</label>
                        <code>{{ $settings['host'] }}</code>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted">Puerto</label>
                        <code>{{ $settings['port'] }}</code>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted">Esquema</label>
                        <code>{{ $settings['scheme'] }}</code>
                    </div>

                    <hr>

                    <h6 class="text-muted">Variables de entorno</h6>
                    <small class="text-muted">
                        Configura estas variables en tu archivo <code>.env</code>
                    </small>

                    <pre class="mt-3 bg-white p-2 border rounded"><code>REVERB_HOST={{ $settings['host'] }}
REVERB_PORT={{ $settings['port'] }}
REVERB_SCHEME={{ $settings['scheme'] }}
REVERB_SERVER_HOST={{ $settings['server_host'] }}
REVERB_SERVER_PORT={{ $settings['server_port'] }}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
