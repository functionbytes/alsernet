@extends('layouts.theme')

@section('content')

    <div class="row">

        {{-- Formulario Principal --}}
        <div class="col-lg-8">
            <div class="card">

                <form action="{{ route('settings.chat.channels.whatsapps.store') }}" method="POST" id="whatsapp-form">
                    @csrf

                    <div class="card-header">
                        <h5 class="mb-1">Crear canal de WhatsApp</h5>
                        <p class="text-muted mb-0" style="font-size:.875rem">Conecta un número de WhatsApp Business al chat para enviar y recibir mensajes directamente desde las conversaciones.</p>
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
                                           value="{{ old('inbox_name') }}"
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
                                    <label class="control-label col-form-label">Número de teléfono <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="phone_number"
                                           class="form-control @error('phone_number') is-invalid @enderror"
                                           value="{{ old('phone_number') }}"
                                           placeholder="+34612345678"
                                           required>
                                    <small class="text-muted">Incluye el código de país</small>
                                    @error('phone_number')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- Proveedor --}}
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Proveedor <span class="text-danger">*</span></label>
                                    <select name="provider" id="provider" class="form-select @error('provider') is-invalid @enderror" required>
                                        <option value="whatsapp_cloud" {{ old('provider', 'whatsapp_cloud') == 'whatsapp_cloud' ? 'selected' : '' }}>
                                            WhatsApp Cloud API (Meta) — Recomendado
                                        </option>
                                        <option value="360dialog" {{ old('provider') == '360dialog' ? 'selected' : '' }}>
                                            360Dialog
                                        </option>
                                        <option value="evolution_api" {{ old('provider') == 'evolution_api' ? 'selected' : '' }}>
                                            Evolution API (Baileys / WhatsApp Web)
                                        </option>
                                    </select>
                                    @error('provider')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <hr class="my-3">

                        {{-- ===== CLOUD API ===== --}}
                        <div id="cloud-fields">
                            <div class="row">
                                <h6 class="fw-semibold mb-3">Configuración de WhatsApp Cloud API (Meta)</h6>

                                {{-- Phone Number ID --}}
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Phone Number ID <span class="text-danger">*</span></label>
                                        <input type="text"
                                               name="phone_number_id"
                                               class="form-control @error('phone_number_id') is-invalid @enderror"
                                               value="{{ old('phone_number_id') }}"
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
                                        <label class="control-label col-form-label">Business Account ID <span class="text-danger">*</span></label>
                                        <input type="text"
                                               name="business_account_id"
                                               class="form-control @error('business_account_id') is-invalid @enderror"
                                               value="{{ old('business_account_id') }}"
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
                                        <label class="control-label col-form-label">Token de acceso <span class="text-danger">*</span></label>
                                        <input type="password"
                                               name="access_token"
                                               class="form-control @error('access_token') is-invalid @enderror"
                                               value="{{ old('access_token') }}"
                                               placeholder="EAAJ...">
                                        <small class="text-muted">Token con permisos <code>whatsapp_business_messaging</code>. Usa un <strong>long-lived token</strong> para evitar expiraciones.</small>
                                        @error('access_token')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Webhook info --}}
                                <div class="col-12 mt-1">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="mb-2">URL del webhook</h6>
                                            <p class="text-muted small mb-2">
                                                Después de crear el canal, configura esta URL en el Meta App Dashboard → WhatsApp → Configuración → Webhook.
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
                        </div>

                        {{-- ===== 360DIALOG ===== --}}
                        <div id="dialog360-fields" style="display:none">
                            <div class="row">
                                <h6 class="fw-semibold mb-3">Configuración de 360Dialog</h6>

                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">API Key <span class="text-danger">*</span></label>
                                        <input type="password"
                                               name="api_key"
                                               class="form-control @error('api_key') is-invalid @enderror"
                                               value="{{ old('api_key') }}"
                                               placeholder="••••••••">
                                        <small class="text-muted">Tu API Key desde el panel de 360Dialog</small>
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
                                               value="{{ old('client_id') }}"
                                               placeholder="cliente-123">
                                        <small class="text-muted">Client ID de 360Dialog si aplica</small>
                                        @error('client_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ===== EVOLUTION API ===== --}}
                        <div id="evolution-fields" style="display:none">
                            <div class="row">
                                <h6 class="fw-semibold mb-3">Configuración de Evolution API (Baileys)</h6>

                                <div class="col-12">
                                    <div class="card bg-warning-subtle mb-3">
                                        <div class="card-body py-2 px-3">
                                            <small>
                                                Evolution API usa WhatsApp Web (Baileys). Tras crear el canal deberás escanear un <strong>código QR</strong> para vincular tu número.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Instance Name --}}
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Nombre de instancia <span class="text-danger">*</span></label>
                                        <input type="text"
                                               name="instance_name"
                                               class="form-control @error('instance_name') is-invalid @enderror"
                                               value="{{ old('instance_name') }}"
                                               placeholder="mi-instancia-wa">
                                        <small class="text-muted">Nombre único en tu servidor Evolution API</small>
                                        @error('instance_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- API URL --}}
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">URL de API <span class="text-muted">(opcional)</span></label>
                                        <input type="url"
                                               name="api_url"
                                               class="form-control @error('api_url') is-invalid @enderror"
                                               value="{{ old('api_url', config('channels.whatsapp.providers.evolution_api.api_url')) }}"
                                               placeholder="http://localhost:8080">
                                        <small class="text-muted">Dejar vacío para usar la configuración por defecto</small>
                                        @error('api_url')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- API Key --}}
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">API Key <span class="text-muted">(opcional)</span></label>
                                        <input type="password"
                                               name="api_key"
                                               class="form-control @error('api_key') is-invalid @enderror"
                                               value="{{ old('api_key', config('channels.whatsapp.providers.evolution_api.api_key')) }}"
                                               placeholder="••••••••">
                                        <small class="text-muted">Dejar vacío para usar la configuración por defecto</small>
                                        @error('api_key')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2 w-100">
                            Crear canal
                        </button>
                        <a href="{{ route('settings.chat.channels.whatsapps.index') }}"
                           class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                            Volver
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            {{-- Comparativa de proveedores --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Comparativa de proveedores</h6>
                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-success-subtle text-success">Recomendado</span>
                            <strong class="small">Cloud API (Meta)</strong>
                        </div>
                        <ul class="small text-muted mb-0 ps-3">
                            <li>Oficial de Meta, alta estabilidad</li>
                            <li>Requiere cuenta Business verificada</li>
                            <li>Soporta plantillas de mensajes</li>
                        </ul>
                    </div>
                    <hr class="my-2">
                    <div class="mb-3">
                        <strong class="small">360Dialog</strong>
                        <ul class="small text-muted mb-0 ps-3 mt-1">
                            <li>Wrapper de la API oficial</li>
                            <li>Requiere suscripción mensual</li>
                            <li>Onboarding simplificado</li>
                        </ul>
                    </div>
                    <hr class="my-2">
                    <div>
                        <strong class="small">Evolution API</strong>
                        <ul class="small text-muted mb-0 ps-3 mt-1">
                            <li>Basado en WhatsApp Web</li>
                            <li>Requiere servidor propio</li>
                            <li>Conexión por QR code</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Información importante --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Información importante</h6>
                    <ul class="small text-muted mb-3 ps-3">
                        <li class="mb-2">El número de teléfono <strong>no se puede cambiar</strong> una vez creado el canal</li>
                        <li class="mb-2">Las credenciales se <strong>cifran</strong> en la base de datos</li>
                        <li class="mb-2">Para Cloud API necesitas configurar el webhook en Meta tras crear el canal</li>
                    </ul>
                    <div class="alert alert-info mb-0 py-2 px-3">
                        <small>
                            Puedes probar la conexión desde la página de detalle del canal una vez creado.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Formato de valores --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Formato de valores</h6>
                    <div class="bg-light p-3 rounded">
                        <code class="d-block mb-2"><strong>Teléfono:</strong> +34612345678</code>
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
    const $provider        = $('#provider');
    const $cloudFields     = $('#cloud-fields');
    const $dialog360Fields = $('#dialog360-fields');
    const $evolutionFields = $('#evolution-fields');

    $provider.on('change', function () {
        $cloudFields.hide();
        $dialog360Fields.hide();
        $evolutionFields.hide();

        const val = $(this).val();
        if (val === 'whatsapp_cloud')  $cloudFields.show();
        else if (val === '360dialog')  $dialog360Fields.show();
        else if (val === 'evolution_api') $evolutionFields.show();
    }).trigger('change');

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
