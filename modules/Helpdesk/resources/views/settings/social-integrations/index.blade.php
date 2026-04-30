@extends('layouts.theme')

@section('title', 'Integraciones sociales - Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Integraciones sociales'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Header Actions Card -->
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Integraciones sociales</h5>
                        <p class="small mb-0 text-muted">Configura los canales de WhatsApp, Facebook Messenger e Instagram para recibir mensajes en Helpdesk</p>
                    </div>
                    <div>
                        <a href="{{ route('manager.helpdesk') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">

            {{-- WhatsApp Business API --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                    <i class="fab fa-whatsapp fa-lg text-success"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold">WhatsApp Business API</h5>
                                    <p class="small text-muted mb-0">Recibe y responde mensajes de WhatsApp desde Helpdesk</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if($whatsappEnabled)
                                    <span class="badge bg-success">Habilitado</span>
                                @else
                                    <span class="badge bg-secondary">Deshabilitado</span>
                                @endif
                                <button type="button" id="test-whatsapp" class="btn btn-sm btn-outline-success" onclick="testConnection('whatsapp')">
                                    <i class="fas fa-plug me-1"></i> Probar conexión
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-semibold mb-2"><i class="fas fa-terminal me-1 text-muted"></i> Variables de entorno requeridas</h6>
                                <p class="small text-muted mb-2">Agrega estas variables en tu archivo <code>.env</code>:</p>
                                <pre class="bg-light rounded p-3 small mb-0"><code>WHATSAPP_ENABLED=true
WHATSAPP_API_URL=https://graph.facebook.com/v19.0
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_VERIFY_TOKEN=your_verify_token
WHATSAPP_APP_SECRET=your_app_secret</code></pre>
                            </div>
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-semibold mb-2"><i class="fas fa-link me-1 text-muted"></i> URL del webhook</h6>
                                <p class="small text-muted mb-2">Configura este webhook en <strong>Meta Business Suite → WhatsApp → Configuración de webhook</strong>:</p>
                                <div class="input-group input-group-sm mb-3">
                                    <input type="text" class="form-control font-monospace" value="{{ $webhookBaseUrl }}/whatsapp" readonly id="whatsapp-webhook-url">
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('whatsapp-webhook-url')" title="Copiar">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <h6 class="fw-semibold mb-2"><i class="fas fa-bell me-1 text-muted"></i> Suscripciones de webhook recomendadas</h6>
                                <ul class="small text-muted mb-0">
                                    <li><code>messages</code></li>
                                    <li><code>message_deliveries</code></li>
                                    <li><code>message_reads</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Facebook Messenger --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                    <i class="fab fa-facebook-messenger fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold">Facebook Messenger</h5>
                                    <p class="small text-muted mb-0">Recibe y responde mensajes de Facebook Pages desde Helpdesk</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if($facebookEnabled)
                                    <span class="badge bg-success">Habilitado</span>
                                @else
                                    <span class="badge bg-secondary">Deshabilitado</span>
                                @endif
                                <button type="button" id="test-facebook" class="btn btn-sm btn-outline-primary" onclick="testConnection('facebook')">
                                    <i class="fas fa-plug me-1"></i> Probar conexión
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-semibold mb-2"><i class="fas fa-terminal me-1 text-muted"></i> Variables de entorno requeridas</h6>
                                <p class="small text-muted mb-2">Agrega estas variables en tu archivo <code>.env</code>:</p>
                                <pre class="bg-light rounded p-3 small mb-0"><code>FACEBOOK_ENABLED=true
FACEBOOK_PAGE_ACCESS_TOKEN=your_page_access_token
FACEBOOK_APP_SECRET=your_app_secret
FACEBOOK_VERIFY_TOKEN=your_verify_token</code></pre>

                                <h6 class="fw-semibold mb-2 mt-3"><i class="fas fa-key me-1 text-muted"></i> Permisos requeridos</h6>
                                <ul class="small text-muted mb-0">
                                    <li><code>pages_messaging</code></li>
                                    <li><code>pages_manage_metadata</code></li>
                                </ul>
                            </div>
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-semibold mb-2"><i class="fas fa-link me-1 text-muted"></i> URL del webhook</h6>
                                <p class="small text-muted mb-2">Configura este webhook en <strong>Meta for Developers → Messenger → Configuración de webhook</strong>:</p>
                                <div class="input-group input-group-sm mb-3">
                                    <input type="text" class="form-control font-monospace" value="{{ $webhookBaseUrl }}/facebook" readonly id="facebook-webhook-url">
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('facebook-webhook-url')" title="Copiar">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <h6 class="fw-semibold mb-2"><i class="fas fa-bell me-1 text-muted"></i> Suscripciones de webhook recomendadas</h6>
                                <ul class="small text-muted mb-0">
                                    <li><code>messages</code></li>
                                    <li><code>messaging_postbacks</code></li>
                                    <li><code>message_deliveries</code></li>
                                    <li><code>message_reads</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Instagram --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(225,48,108,.1)">
                                    <i class="fab fa-instagram fa-lg" style="color:#e1306c"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold">Instagram</h5>
                                    <p class="small text-muted mb-0">Recibe y responde mensajes directos de Instagram desde Helpdesk</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if($instagramEnabled)
                                    <span class="badge bg-success">Habilitado</span>
                                @else
                                    <span class="badge bg-secondary">Deshabilitado</span>
                                @endif
                                <button type="button" id="test-instagram" class="btn btn-sm btn-outline-danger" onclick="testConnection('instagram')">
                                    <i class="fas fa-plug me-1"></i> Probar conexión
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-semibold mb-2"><i class="fas fa-terminal me-1 text-muted"></i> Variables de entorno requeridas</h6>
                                <p class="small text-muted mb-2">Agrega estas variables en tu archivo <code>.env</code>:</p>
                                <pre class="bg-light rounded p-3 small mb-0"><code>INSTAGRAM_ENABLED=true
INSTAGRAM_ACCESS_TOKEN=your_access_token
INSTAGRAM_APP_SECRET=your_app_secret
INSTAGRAM_VERIFY_TOKEN=your_verify_token</code></pre>

                                <h6 class="fw-semibold mb-2 mt-3"><i class="fas fa-key me-1 text-muted"></i> Permisos requeridos (desde ene. 2025)</h6>
                                <ul class="small text-muted mb-0">
                                    <li><code>instagram_business_basic</code></li>
                                    <li><code>instagram_business_manage_messages</code></li>
                                </ul>

                                <div class="alert alert-warning d-flex align-items-start gap-2 mt-3 mb-0 py-2">
                                    <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></i>
                                    <small>Limite de mensajes directos: <strong>200 DMs/hora</strong> por cuenta de Instagram Business.</small>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-semibold mb-2"><i class="fas fa-link me-1 text-muted"></i> URL del webhook</h6>
                                <p class="small text-muted mb-2">Configura este webhook en <strong>Meta for Developers → Instagram → Configuración de webhook</strong>:</p>
                                <div class="input-group input-group-sm mb-3">
                                    <input type="text" class="form-control font-monospace" value="{{ $webhookBaseUrl }}/instagram" readonly id="instagram-webhook-url">
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('instagram-webhook-url')" title="Copiar">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <h6 class="fw-semibold mb-2"><i class="fas fa-bell me-1 text-muted"></i> Suscripciones de webhook recomendadas</h6>
                                <ul class="small text-muted mb-0">
                                    <li><code>messages</code></li>
                                    <li><code>messaging_postbacks</code></li>
                                    <li><code>message_reactions</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
    const socialTestRoutes = {
        whatsapp: '{{ route('settings.helpdesk.social-integrations.test.whatsapp') }}',
        facebook: '{{ route('settings.helpdesk.social-integrations.test.facebook') }}',
        instagram: '{{ route('settings.helpdesk.social-integrations.test.instagram') }}',
    };

    function testConnection(platform) {
        const btn = document.getElementById('test-' + platform);
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Probando...';

        fetch(socialTestRoutes[platform], {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
            } else {
                toastr.error(data.message);
            }
        })
        .catch(() => toastr.error('Error de conexión'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    function copyToClipboard(inputId) {
        const input = document.getElementById(inputId);
        navigator.clipboard.writeText(input.value).then(() => {
            toastr.success('URL copiada al portapapeles');
        }).catch(() => {
            input.select();
            document.execCommand('copy');
            toastr.success('URL copiada al portapapeles');
        });
    }
</script>
@endpush
