<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Procesando Pago') }} - {{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=">
    <link href="{{ themeAsset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ themeAsset('libs/fontawesome/fontawesome.css') }}" rel="stylesheet">
    <style>
        body { background: #fbfbfb; min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .payment-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .payment-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); overflow: hidden; max-width: 600px; width: 100%; }
        .payment-header { background: #90bb13; color: white; padding: 2rem; text-align: center; }
        .payment-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.9; }
        .payment-body { padding: 2rem; }
        .payment-summary { background: #f8f9fa; border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem; }
        .amount-display { font-size: 2rem; font-weight: bold; color: #2c3e50; }
        .wompi-form { text-align: center; padding: 2rem 0; min-height: 100px; }
        .back-button { background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; transition: all 0.3s ease; height: 40px; line-height: 40px; width: 100%; margin-top: 10px; }
        .back-button:hover { background: #5a6268; transform: translateY(-1px); }
        .fallback-button { background: #90bb13; color: white; border: none; border-radius: 4px; cursor: pointer; transition: all 0.3s ease; height: 40px; line-height: 40px; width: 100%; margin-top: 10px; }
        .fallback-button:hover { background: #7aa30f; transform: translateY(-1px); }
        .payment-instructions { background: rgba(144,187,19,0.1); border: 1px solid rgba(144,187,19,0.3); border-radius: 4px; padding: 1rem; color: #5a7a0e; }
        .security-info { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 2rem; color: #000; font-size: 0.9rem; }
        .security-badges { display: flex; align-items: center; gap: 0.5rem; }
        .loading-indicator { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; color: #000; }
        .loading-spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #90bb13; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .error-message { display: none; background: #f8d7da; color: #842029; padding: 1.5rem; border-radius: 10px; margin: 1rem 0; border-left: 4px solid #dc3545; }
        .widget-status { text-align: center; margin: 1rem 0; font-size: 0.9rem; color: #90bb13; display: none !important; }
        .waybox-button { background-color: #90bb13 !important; width: 100% !important; }
        .security-info i { color: #90bb13 !important; }
    </style>
</head>
<body>
<div class="payment-container">
    <div class="payment-card">
        <div class="payment-header">
            <div class="payment-icon"><i class="fas fa-credit-card"></i></div>
            <h2 class="mb-0">{{ __('Pago seguro con Wompi') }}</h2>
            <p class="mb-0 opacity-75">{{ __('Procesa tu pago de forma segura') }}</p>
        </div>
        <div class="payment-body">
            <div class="payment-summary">
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="mb-2">{{ __('Referencia de Pedido') }}</h6>
                        <p class="mb-0 text-muted">{{ $widgetData['reference'] }}</p>
                        <small class="text-muted">{{ __('Guarda esta referencia para futuras consultas') }}</small>
                    </div>
                    <div class="col-md-12 mt-4">
                        <h6 class="mb-2">{{ __('Total a Pagar') }}</h6>
                        <div class="amount-display">
                            @if($originalCurrency !== 'COP')
                                {{ number_format($originalAmount, 2) }} {{ $originalCurrency }}
                                <small class="text-muted d-block" style="font-size: 0.8rem;">
                                    ≈ {{ number_format($widgetData['amount_in_cents'] / 100, 0) }} COP
                                </small>
                            @else
                                ${{ number_format($widgetData['amount_in_cents'] / 100, 0) }} COP
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="payment-instructions">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>{{ __('Instrucciones de Pago') }}</strong>
                </div>
                <p class="mb-2">{{ __('Al hacer clic en "Pagar con Wompi" seras redirigido a la plataforma segura de pago.') }}</p>
                <small>{{ __('Podras pagar con tarjeta de credito, debito, PSE o Nequi.') }}</small>
            </div>

            <div id="loading-indicator" class="loading-indicator">
                <div class="loading-spinner"></div>
                <span>{{ __('Cargando formulario de pago...') }}</span>
            </div>

            <div id="widget-status" class="widget-status" style="display: none;"></div>

            <div id="error-message" class="error-message">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>{{ __('Error al cargar el widget de pago') }}</strong>
                </div>
                <p id="error-details" class="mb-3"></p>
                <div class="text-center">
                    <button onclick="location.reload()" class="fallback-button">
                        <i class="fas fa-redo me-2"></i>{{ __('Recargar Pagina') }}
                    </button>
                    <button onclick="redirectToDirectCheckout()" class="fallback-button">
                        <i class="fas fa-external-link-alt me-2"></i>{{ __('Pago Directo') }}
                    </button>
                </div>
            </div>

            <div id="wompi-form-container" class="wompi-form">
                <form id="formWompi">
                    <script
                        id="wompi-widget-script"
                        src="https://checkout.wompi.co/widget.js"
                        data-render="button"
                        data-public-key="{{ $widgetData['public_key'] }}"
                        data-currency="{{ $widgetData['currency'] }}"
                        data-amount-in-cents="{{ $widgetData['amount_in_cents'] }}"
                        data-reference="{{ $widgetData['reference'] }}"
                        data-redirect-url="{{ $widgetData['redirect_url'] }}"
                        @if(isset($widgetData['signature_integrity']))
                            data-signature:integrity="{{ $widgetData['signature_integrity'] }}"
                        @endif
                        @if(isset($widgetData['expiration_time']))
                            data-expiration-time="{{ $widgetData['expiration_time'] }}"
                        @endif
                        @if(isset($widgetData['customer_data']['email']))
                            data-customer-data:email="{{ $widgetData['customer_data']['email'] }}"
                        @endif
                        @if(isset($widgetData['customer_data']['full_name']) && $widgetData['customer_data']['full_name'])
                            data-customer-data:full-name="{{ $widgetData['customer_data']['full_name'] }}"
                        @endif
                        @if(isset($widgetData['customer_data']['phone_number']) && isset($widgetData['customer_data']['phone_number_prefix']))
                            data-customer-data:phone-number="{{ $widgetData['customer_data']['phone_number'] }}"
                            data-customer-data:phone-number-prefix="{{ $widgetData['customer_data']['phone_number_prefix'] }}"
                        @endif
                        @if(isset($widgetData['shipping_address']) && isset($widgetData['shipping_address']['address_line_1']))
                            data-shipping-address:address-line-1="{{ $widgetData['shipping_address']['address_line_1'] }}"
                            data-shipping-address:city="{{ $widgetData['shipping_address']['city'] }}"
                            data-shipping-address:region="{{ $widgetData['shipping_address']['region'] }}"
                            data-shipping-address:country="{{ $widgetData['shipping_address']['country'] }}"
                            @if(isset($widgetData['shipping_address']['phone_number']) && isset($widgetData['shipping_address']['phone_number_prefix']))
                                data-shipping-address:phone-number="{{ $widgetData['shipping_address']['phone_number'] }}"
                                data-shipping-address:phone-number-prefix="{{ $widgetData['shipping_address']['phone_number_prefix'] }}"
                            @endif
                        @endif>
                    </script>
                </form>
                <button onclick="window.history.back()" class="back-button">
                    <i class="fas fa-arrow-left me-2"></i>{{ __('Volver al Carrito') }}
                </button>
            </div>

            <div class="security-info">
                <div class="security-badges"><i class="fas fa-shield-alt text-success"></i><span>{{ __('Pago 100% Seguro') }}</span></div>
                <div class="security-badges"><i class="fas fa-lock text-primary"></i><span>{{ __('Conexion Encriptada') }}</span></div>
                <div class="security-badges"><i class="fas fa-certificate text-warning"></i><span>{{ __('Certificado SSL') }}</span></div>
            </div>

            @if(app()->environment('local'))
                <div class="mt-4 p-3" style="background: #f8f9fa; border-radius: 10px; font-size: 0.85rem;">
                    <strong>{{ __('Debug Info') }} (Solo en desarrollo):</strong><br>
                    <strong>Entorno:</strong> {{ $widgetData['is_sandbox'] ? 'Sandbox' : 'Produccion' }}<br>
                    <strong>Public Key:</strong> {{ substr($widgetData['public_key'], 0, 20) }}...<br>
                    <strong>Referencia:</strong> {{ $widgetData['reference'] }}<br>
                    <strong>Monto (centavos):</strong> {{ $widgetData['amount_in_cents'] }}<br>
                    <strong>Moneda:</strong> {{ $widgetData['currency'] }}<br>
                    <strong>Email:</strong> {{ $widgetData['customer_data']['email'] ?? 'N/A' }}<br>
                    <strong>URL Redirect:</strong> {{ $widgetData['redirect_url'] }}
                </div>
            @endif
        </div>
    </div>
</div>

<script src="{{ themeAsset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script>
    const widgetConfig = @json($widgetData);
    let widgetDetected = false;
    let widgetCheckInterval;

    function showLoading() {
        document.getElementById('loading-indicator').style.display = 'flex';
        document.getElementById('error-message').style.display = 'none';
        document.getElementById('wompi-form-container').style.display = 'none';
    }

    function showError(message, details = '') {
        document.getElementById('loading-indicator').style.display = 'none';
        document.getElementById('error-message').style.display = 'block';
        document.getElementById('wompi-form-container').style.display = 'none';
        document.getElementById('error-details').innerHTML = details || message;
    }

    function showWidget() {
        document.getElementById('loading-indicator').style.display = 'none';
        document.getElementById('error-message').style.display = 'none';
        document.getElementById('wompi-form-container').style.display = 'block';
    }

    function checkWidgetLoaded() {
        const indicators = [
            document.querySelector('button[data-wompi-id]'),
            document.querySelector('.wompi-button'),
            document.querySelector('[class*="wompi"]'),
            document.querySelector('iframe[src*="wompi"]'),
            document.querySelector('[data-wompi-id]'),
            document.querySelector('form button[type="submit"]'),
        ];

        const widgetFound = indicators.some(indicator => indicator !== null);

        if (widgetFound && !widgetDetected) {
            widgetDetected = true;
            showWidget();
            clearInterval(widgetCheckInterval);
            return true;
        }
        return false;
    }

    function startWidgetDetection() {
        let checkCount = 0;
        const maxChecks = 30;

        widgetCheckInterval = setInterval(() => {
            checkCount++;
            if (checkWidgetLoaded()) return;
            if (checkCount >= maxChecks) {
                clearInterval(widgetCheckInterval);
                showError('El widget de Wompi no se detecto correctamente', 'Puede haber un problema de conectividad.');
            }
        }, 500);
    }

    function redirectToDirectCheckout() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'https://checkout.wompi.co/p/';

        const formData = {
            'public-key': widgetConfig.public_key,
            'currency': widgetConfig.currency,
            'amount-in-cents': widgetConfig.amount_in_cents,
            'reference': widgetConfig.reference,
            'signature:integrity': widgetConfig.signature_integrity,
            'redirect-url': widgetConfig.redirect_url,
            'customer-data:email': widgetConfig.customer_data.email
        };

        if (widgetConfig.customer_data.full_name) {
            formData['customer-data:full-name'] = widgetConfig.customer_data.full_name;
        }
        if (widgetConfig.customer_data.phone_number && widgetConfig.customer_data.phone_number_prefix) {
            formData['customer-data:phone-number'] = widgetConfig.customer_data.phone_number;
            formData['customer-data:phone-number-prefix'] = widgetConfig.customer_data.phone_number_prefix;
        }

        for (const [key, value] of Object.entries(formData)) {
            if (value !== null && value !== undefined && value !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
        }

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        showLoading();
        if (!navigator.onLine) {
            showError('Sin conexion a internet', 'Verifica tu conexion y recarga la pagina.');
            return;
        }
        setTimeout(() => { startWidgetDetection(); }, 2000);
        setTimeout(() => {
            if (!widgetDetected && document.getElementById('loading-indicator').style.display !== 'none') {
                showError('El widget de pago tardo demasiado en cargar', 'Puedes recargar la pagina o usar el pago directo.');
            }
        }, 20000);
    });

    window.addEventListener('error', function(e) {
        if (e.message && (e.message.includes('wompi') || e.message.includes('checkout'))) {
            clearInterval(widgetCheckInterval);
            showError('Error en el script de Wompi', 'Hubo un problema tecnico. Puedes recargar la pagina o usar el pago directo.');
        }
    });

    window.addEventListener('online', function() {
        if (!widgetDetected) location.reload();
    });

    window.addEventListener('offline', function() {
        showError('Conexion perdida', 'Se perdio la conexion a internet. El widget se recargara automaticamente.');
    });
</script>
</body>
</html>
