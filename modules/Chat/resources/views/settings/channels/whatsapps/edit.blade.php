@extends('layouts.theme')

@section('content')

    <div class="row">

        {{-- Formulario Principal --}}
        <div class="col-lg-8">
            <div class="card">

                <form action="{{ route('settings.chat.channels.whatsapps.update', $whatsapp) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header">
                        <h5 class="mb-1">Editar canal de WhatsApp</h5>
                        <p class="text-muted mb-0" style="font-size:.875rem">Actualiza los parámetros de conexión con la API de WhatsApp. Las credenciales están cifradas en la base de datos.</p>
                    </div>

                    <div class="card-body">
                        <div class="row">

                            {{-- Nombre del canal --}}
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre del canal <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="inbox_name"
                                           class="form-control @error('inbox_name') is-invalid @enderror"
                                           value="{{ old('inbox_name', $whatsapp->inbox?->name) }}"
                                           placeholder="Ej: Soporte WhatsApp"
                                           required>
                                    @error('inbox_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- Número de teléfono --}}
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Número de teléfono</label>
                                    <input type="text" class="form-control bg-light" value="{{ $whatsapp->phone_number }}" disabled>
                                    <small class="text-muted">No se puede cambiar</small>
                                </div>
                            </div>

                            {{-- Proveedor --}}
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Proveedor</label>
                                    <input type="text" class="form-control bg-light"
                                           value="{{ match($whatsapp->provider) {
                                               'whatsapp_cloud' => 'WhatsApp Cloud API (Meta)',
                                               '360dialog'      => '360Dialog',
                                               'evolution_api'  => 'Evolution API',
                                               default          => $whatsapp->provider
                                           } }}"
                                           disabled>
                                    <small class="text-muted">No se puede cambiar</small>
                                </div>
                            </div>

                        </div>

                        <hr class="my-3">

                        @if($whatsapp->is360Dialog())

                            <div class="row">
                                <h6 class="fw-semibold mb-3">Configuración de 360Dialog</h6>

                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">API Key</label>
                                        <input type="password"
                                               name="api_key"
                                               class="form-control @error('api_key') is-invalid @enderror"
                                               placeholder="••••••••">
                                        <small class="text-muted">Dejar vacío para mantener la clave actual</small>
                                        @error('api_key')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Client ID <span class="text-muted">(opcional)</span></label>
                                        <input type="text"
                                               name="client_id"
                                               class="form-control @error('client_id') is-invalid @enderror"
                                               value="{{ old('client_id', $whatsapp->getClientId()) }}">
                                        @error('client_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        @elseif($whatsapp->isEvolutionApi())

                            <div class="row">
                                <h6 class="fw-semibold mb-3">Configuración de Evolution API</h6>

                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Nombre de instancia</label>
                                        <input type="text" class="form-control bg-light" value="{{ $whatsapp->getInstanceName() }}" disabled>
                                        <small class="text-muted">No se puede cambiar</small>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">URL de API</label>
                                        <input type="text" class="form-control bg-light" value="{{ $whatsapp->getApiUrl() }}" disabled>
                                    </div>
                                </div>
                            </div>

                        @else

                            <div class="row">
                                <h6 class="fw-semibold mb-3">Configuración de WhatsApp Cloud API (Meta)</h6>

                                {{-- Phone Number ID --}}
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Phone Number ID</label>
                                        <input type="text"
                                               name="phone_number_id"
                                               class="form-control @error('phone_number_id') is-invalid @enderror"
                                               value="{{ old('phone_number_id', $whatsapp->getPhoneNumberId()) }}"
                                               placeholder="Ej: 123456789012345">
                                        <small class="text-muted">Desde WhatsApp Business API → Configuración de la API</small>
                                        @error('phone_number_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Business Account ID --}}
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Business Account ID</label>
                                        <input type="text"
                                               name="business_account_id"
                                               class="form-control @error('business_account_id') is-invalid @enderror"
                                               value="{{ old('business_account_id', $whatsapp->getBusinessAccountId()) }}"
                                               placeholder="Ej: 987654321098765">
                                        <small class="text-muted">Desde Meta Business Manager</small>
                                        @error('business_account_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Access Token --}}
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Token de acceso</label>
                                        <input type="password"
                                               name="access_token"
                                               class="form-control @error('access_token') is-invalid @enderror"
                                               placeholder="••••••••">
                                        <small class="text-muted">Dejar vacío para mantener el token actual. Usa un <strong>long-lived token</strong> para evitar expiraciones.</small>
                                        @error('access_token')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                            </div>

                            {{-- Webhook info --}}
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="mb-2">URL del webhook</h6>
                                            <p class="text-muted small mb-2">
                                                Configura esta URL en el Meta App Dashboard → WhatsApp → Configuración → Webhook.
                                            </p>
                                            <div class="d-flex align-items-center gap-2">
                                                <code id="webhook-url" class="flex-grow-1 p-2 bg-white border rounded d-block" style="font-size:.8rem;word-break:break-all">
                                                    {{ url('/api/webhooks/whatsapp') }}
                                                </code>
                                                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" onclick="copyWebhook()">
                                                    Copiar
                                                </button>
                                            </div>
                                            <div class="alert alert-info mt-2 mb-0 py-2 px-3">
                                                <small>
                                                    <strong>Verify Token:</strong>
                                                    <code>{{ config('channels.whatsapp.verify_token') }}</code>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @endif

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2 w-100">
                            Guardar
                        </button>
                        <a href="{{ route('settings.chat.channels.whatsapps.show', $whatsapp) }}"
                           class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                            Volver
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            {{-- Estado de conexión --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Estado de conexión</h6>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-success-subtle text-success px-3 py-2">
                            {{ ucfirst($whatsapp->provider_config['status'] ?? 'Conectado') }}
                        </span>
                    </div>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2">Número: <strong>{{ $whatsapp->phone_number }}</strong></li>
                        <li class="mb-2">Proveedor: <strong>WhatsApp Cloud API</strong></li>
                        <li class="mb-2">Versión API: <strong>{{ config('channels.whatsapp.api_version', 'v22.0') }}</strong></li>
                    </ul>
                </div>
            </div>

            {{-- Información importante --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Información importante</h6>
                    <ul class="small text-muted mb-3 ps-3">
                        <li class="mb-2">El <strong>Phone Number ID</strong> y el <strong>Business Account ID</strong> los encuentras en el Meta App Dashboard</li>
                        <li class="mb-2">Usa un <strong>token permanente</strong> (System User) para evitar que expire cada 24h</li>
                        <li class="mb-2">El webhook debe estar configurado en Meta para recibir mensajes</li>
                        <li class="mb-2">Los tokens están <strong>cifrados</strong> en la base de datos</li>
                    </ul>
                    <div class="alert alert-info mb-0 py-2 px-3">
                        <small>
                            Después de guardar, prueba la conexión desde la página de detalle del canal.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Formato de valores --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Formato de valores</h6>
                    <div class="bg-light p-3 rounded">
                        <code class="d-block mb-2"><strong>Phone Number ID:</strong> 989418417591045</code>
                        <code class="d-block mb-2"><strong>Business Account ID:</strong> 3448633528633799</code>
                        <code class="d-block"><strong>Access Token:</strong> EAAJ...</code>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});

function copyWebhook() {
    const text = document.getElementById('webhook-url').innerText.trim();
    navigator.clipboard.writeText(text).then(() => toastr.success('URL copiada'));
}
</script>
@endpush
