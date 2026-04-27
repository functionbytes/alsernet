@extends('layouts.theme')

@section('title', 'Métodos de pago')

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Métodos de pago</h5>
                    <p class="small mb-0 text-muted">Gateways de pago registrados. Los gateways de módulos se instalan desde
                        <a href="{{ route('settings.modules.index') }}">Módulos</a>.
                    </p>
                </div>
                <a href="{{ route('settings.modules.uploadForm') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Instalar gateway
                </a>
            </div>
        </div>

        <div class="card-body p-4">

            @if($gateways->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-credit-card fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">No hay métodos de pago registrados.</p>
                </div>
            @else
                <div class="row g-3">
                    @foreach($gateways as $gateway)
                        <div class="col-md-6 col-xl-4">
                            <div class="card border h-100 {{ $gateway['enabled'] ? '' : 'opacity-75' }}">
                                <div class="card-body p-4">

                                    {{-- Header: icono + nombre + badge estado --}}
                                    <div class="d-flex align-items-start justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-2 p-2 bg-light d-flex align-items-center justify-content-center"
                                                 style="width:44px;height:44px;">
                                                @if($gateway['key'] === 'wompi')
                                                    <i class="fas fa-credit-card text-primary fs-5"></i>
                                                @elseif($gateway['key'] === 'cod')
                                                    <i class="fas fa-motorcycle text-warning fs-5"></i>
                                                @elseif($gateway['key'] === 'bank_transfer')
                                                    <i class="fas fa-university text-success fs-5"></i>
                                                @else
                                                    <i class="fas fa-plug text-secondary fs-5"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $gateway['name'] }}</h6>
                                                <small class="text-muted font-monospace">{{ $gateway['key'] }}</small>
                                            </div>
                                        </div>
                                        <span class="badge {{ $gateway['enabled'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $gateway['enabled'] ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>

                                    {{-- Origen: built-in o módulo --}}
                                    <div class="mb-3">
                                        @if($gateway['is_module'])
                                            <div class="d-flex align-items-center gap-2 small">
                                                <span class="badge bg-primary-subtle text-primary">
                                                    <i class="fas fa-puzzle-piece me-1"></i> Módulo
                                                </span>
                                                <span class="text-muted">{{ $gateway['module_name'] }}</span>
                                                @if($gateway['version'])
                                                    <span class="text-muted">v{{ $gateway['version'] }}</span>
                                                @endif
                                                @if(! $gateway['module_enabled'])
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> Módulo deshabilitado
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge bg-light text-muted small">
                                                <i class="fas fa-cube me-1"></i> Built-in
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Descripción si viene del módulo --}}
                                    @if($gateway['description'])
                                        <p class="small text-muted mb-3">{{ $gateway['description'] }}</p>
                                    @endif

                                    {{-- Acciones --}}
                                    <div class="d-flex gap-2">
                                        @if($gateway['settings_route'])
                                            <a href="{{ route($gateway['settings_route']) }}"
                                               class="btn btn-sm btn-outline-secondary flex-fill">
                                                <i class="fas fa-cog me-1"></i> Configurar
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary flex-fill" disabled>
                                                Sin configuración
                                            </button>
                                        @endif

                                        @if($gateway['is_module'] && $gateway['module_alias'])
                                            <a href="{{ route('settings.modules.show', $gateway['module_alias']) }}"
                                               class="btn btn-sm btn-outline-light"
                                               title="Ver módulo">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

    {{-- Panel de documentación para desarrolladores --}}
    <div class="card mt-4">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">
                <i class="fas fa-code me-2"></i> Crear un gateway como módulo instalable
            </h6>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted mb-3">
                Cualquier módulo que declare <code>"type": "payment-gateway"</code> en su <code>module.json</code>
                se registra automáticamente al instalarlo. No se requiere tocar el código core.
            </p>

            <div class="row g-4">
                <div class="col-md-6">
                    <p class="small fw-semibold mb-1">1. Estructura del módulo</p>
                    <pre class="bg-light rounded p-3 small mb-0"><code>PaymentGateway-Stripe/
├── module.json
├── composer.json
├── app/
│   ├── Services/
│   │   └── StripeGateway.php
│   └── Providers/
│       └── ...ServiceProvider.php
├── config/
│   └── stripe.php
└── resources/views/
    └── settings/stripe.blade.php</code></pre>
                </div>

                <div class="col-md-6">
                    <p class="small fw-semibold mb-1">2. Campos requeridos en <code>module.json</code></p>
                    <pre class="bg-light rounded p-3 small mb-0"><code>{
  "name": "PaymentGateway-Stripe",
  "alias": "payment-gateway-stripe",
  "type": "payment-gateway",
  "gateway_channel": "stripe",
  "gateway_class": "Modules\\PaymentGatewayStripe\\Services\\StripeGateway",
  "version": "1.0.0",
  "providers": [
    "Modules\\PaymentGatewayStripe\\Providers\\...ServiceProvider"
  ]
}</code></pre>
                </div>

                <div class="col-12">
                    <p class="small fw-semibold mb-1">3. La clase del gateway debe implementar</p>
                    <code class="small">Modules\EcommercePayment\Contracts\PaymentGatewayContract</code>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        @foreach(['getChannel()', 'getName()', 'isEnabled()', 'makePayment()', 'handleCallback()', 'handleWebhook()', 'refund()', 'getSettingsView()', 'getSettingsRoute()'] as $method)
                            <span class="badge bg-light text-secondary font-monospace">{{ $method }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
