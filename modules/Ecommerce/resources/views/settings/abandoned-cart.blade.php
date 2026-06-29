@extends('layouts.theme')

@section('title', 'Carrito abandonado')

@section('content')

    <form action="{{ route('settings.ecommerce.abandoned-cart.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-header border-bottom p-3">
                <h5 class="mb-0 fw-bold">Recuperación de carritos abandonados</h5>
                <small class="text-muted">Recupera ventas perdidas enviando correos automáticos a clientes que abandonaron sus carritos</small>
            </div>
            <div class="card-body">
                @include('core::components.alerts')

                {{-- Habilitar --}}
                <div class="mb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="abandoned_cart_enabled" value="1"
                            id="abandoned_cart_enabled"
                            {{ old('abandoned_cart_enabled', $settings['abandoned_cart_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="abandoned_cart_enabled">Habilitar correos electrónicos de carritos abandonados</label>
                    </div>
                    <div class="form-text mt-1">Activar correos automáticos de recuperación de carritos abandonados. Cuando está habilitado, el sistema detectará automáticamente los carritos abandonados y enviará correos de recuperación.</div>
                </div>

                <div id="abandoned-cart-config-block" style="{{ old('abandoned_cart_enabled', $settings['abandoned_cart_enabled'] ?? '') == '1' ? '' : 'display:none' }}">

                    <hr class="my-3">

                    {{-- Plantilla de correo --}}
                    <div class="mb-3">
                        <label for="abandoned_cart_email_template" class="form-label fw-semibold">Plantilla de correo</label>
                        <select name="abandoned_cart_email_template" id="abandoned_cart_email_template"
                            class="form-select @error('abandoned_cart_email_template') is-invalid @enderror">
                            <option value="abandoned_cart" {{ old('abandoned_cart_email_template', $settings['abandoned_cart_email_template'] ?? 'abandoned_cart') == 'abandoned_cart' ? 'selected' : '' }}>Plantilla moderna (recomendada)</option>
                            <option value="order_recover" {{ old('abandoned_cart_email_template', $settings['abandoned_cart_email_template'] ?? 'abandoned_cart') == 'order_recover' ? 'selected' : '' }}>Plantilla clásica</option>
                        </select>
                        @error('abandoned_cart_email_template')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">Elige el diseño y estilo para tus correos de carrito abandonado. La plantilla moderna incluye incentivos y mejores elementos de conversión.</div>
                    </div>

                    {{-- Asunto del correo --}}
                    <div class="mb-3">
                        <label for="abandoned_cart_email_subject" class="form-label fw-semibold">Línea de asunto del correo</label>
                        <input type="text" name="abandoned_cart_email_subject" id="abandoned_cart_email_subject"
                            class="form-control @error('abandoned_cart_email_subject') is-invalid @enderror"
                            value="{{ old('abandoned_cart_email_subject', $settings['abandoned_cart_email_subject'] ?? 'Completa tu compra - ¡Tu carrito te está esperando!') }}">
                        @error('abandoned_cart_email_subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">La línea de asunto que aparece en las bandejas de entrada de los clientes. Usa lenguaje atractivo para fomentar la apertura.</div>
                    </div>

                    {{-- Retraso inicial --}}
                    <div class="mb-3">
                        <label for="abandoned_cart_delay_hours" class="form-label fw-semibold">Retraso inicial (horas)</label>
                        <input type="number" name="abandoned_cart_delay_hours" id="abandoned_cart_delay_hours" min="1"
                            class="form-control @error('abandoned_cart_delay_hours') is-invalid @enderror"
                            value="{{ old('abandoned_cart_delay_hours', $settings['abandoned_cart_delay_hours'] ?? '1') }}">
                        @error('abandoned_cart_delay_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">Esperar esta cantidad de horas después del abandono del carrito antes de enviar el primer correo de recuperación. Recomendado: 1-2 horas.</div>
                    </div>

                    {{-- Edad máxima del carrito --}}
                    <div class="mb-3">
                        <label for="abandoned_cart_max_hours" class="form-label fw-semibold">Edad máxima del carrito (horas)</label>
                        <input type="number" name="abandoned_cart_max_hours" id="abandoned_cart_max_hours" min="1"
                            class="form-control @error('abandoned_cart_max_hours') is-invalid @enderror"
                            value="{{ old('abandoned_cart_max_hours', $settings['abandoned_cart_max_hours'] ?? '168') }}">
                        @error('abandoned_cart_max_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">No enviar correos para carritos más antiguos que esto. Después de este tiempo, los carritos se consideran demasiado antiguos para recuperar. Por defecto: 168 horas (7 días).</div>
                    </div>

                    {{-- Límite de correos por lote --}}
                    <div class="mb-3">
                        <label for="abandoned_cart_email_limit" class="form-label fw-semibold">Límite de correos por lote</label>
                        <input type="number" name="abandoned_cart_email_limit" id="abandoned_cart_email_limit" min="1"
                            class="form-control @error('abandoned_cart_email_limit') is-invalid @enderror"
                            value="{{ old('abandoned_cart_email_limit', $settings['abandoned_cart_email_limit'] ?? '50') }}">
                        @error('abandoned_cart_email_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">Número máximo de correos a enviar en cada ejecución automatizada. Ayuda a controlar la carga del servidor y los límites de envío de correos.</div>
                    </div>

                    {{-- Máx. correos por carrito --}}
                    <div class="mb-3">
                        <label for="abandoned_cart_max_emails" class="form-label fw-semibold">Máx. correos por carrito</label>
                        <input type="number" name="abandoned_cart_max_emails" id="abandoned_cart_max_emails" min="1"
                            class="form-control @error('abandoned_cart_max_emails') is-invalid @enderror"
                            value="{{ old('abandoned_cart_max_emails', $settings['abandoned_cart_max_emails'] ?? '3') }}">
                        @error('abandoned_cart_max_emails')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">Número máximo de correos de seguimiento a enviar por un solo carrito abandonado. Previene el spam y la fatiga del correo.</div>
                    </div>

                    {{-- Intervalo de correos --}}
                    <div class="mb-3">
                        <label for="abandoned_cart_email_interval_hours" class="form-label fw-semibold">Intervalo de correos (horas)</label>
                        <input type="number" name="abandoned_cart_email_interval_hours" id="abandoned_cart_email_interval_hours" min="1"
                            class="form-control @error('abandoned_cart_email_interval_hours') is-invalid @enderror"
                            value="{{ old('abandoned_cart_email_interval_hours', $settings['abandoned_cart_email_interval_hours'] ?? '24') }}">
                        @error('abandoned_cart_email_interval_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">Tiempo de espera entre el envío de correos de seguimiento para el mismo carrito. Recomendado: 24-72 horas.</div>
                    </div>

                    {{-- Incluir oferta de envío gratis --}}
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="abandoned_cart_offer_free_shipping" value="1"
                                id="abandoned_cart_offer_free_shipping"
                                {{ old('abandoned_cart_offer_free_shipping', $settings['abandoned_cart_offer_free_shipping'] ?? '') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="abandoned_cart_offer_free_shipping">Incluir oferta de envío gratis</label>
                        </div>
                        <div class="form-text mt-1">Mostrar un incentivo de envío gratis en la plantilla de correo para fomentar la finalización. Esta es solo una opción de visualización.</div>
                    </div>

                    {{-- Excluir categorías --}}
                    <div class="mb-3">
                        <label for="abandoned_cart_exclude_categories" class="form-label fw-semibold">Excluir categorías de productos</label>
                        <textarea name="abandoned_cart_exclude_categories" id="abandoned_cart_exclude_categories" rows="3"
                            class="form-control @error('abandoned_cart_exclude_categories') is-invalid @enderror"
                            placeholder="electronics,books,digital-downloads">{{ old('abandoned_cart_exclude_categories', $settings['abandoned_cart_exclude_categories'] ?? '') }}</textarea>
                        @error('abandoned_cart_exclude_categories')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">Lista separada por comas de slugs de categorías a excluir de los correos de carrito abandonado (ej., descargas-digitales, tarjetas-regalo).</div>
                    </div>

                    {{-- Dirección de correo de prueba --}}
                    <div class="mb-1">
                        <label for="abandoned_cart_test_email" class="form-label fw-semibold">Dirección de correo de prueba</label>
                        <input type="text" name="abandoned_cart_test_email" id="abandoned_cart_test_email"
                            class="form-control mb-2"
                            placeholder="your-email@example.com"
                            value="{{ old('abandoned_cart_test_email') }}">
                        <button type="button" class="btn btn-danger btn-sm mb-2" id="send-test-email-btn">
                            <i class="fas fa-paper-plane me-1"></i> Enviar email de prueba
                        </button>
                        <div class="form-text">Se enviará un email de prueba usando la plantilla seleccionada arriba a la dirección especificada.</div>
                    </div>

                </div>{{-- #abandoned-cart-config-block --}}

                <hr class="my-3">

                {{-- Destruir carrito al cerrar sesión --}}
                <div class="mb-0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="cart_destroy_on_logout" value="1"
                            id="cart_destroy_on_logout"
                            {{ old('cart_destroy_on_logout', $settings['cart_destroy_on_logout'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="cart_destroy_on_logout">Destruir carrito al cerrar sesión</label>
                    </div>
                    <div class="form-text mt-1">El carrito se destruirá cuando el cliente cierre la sesión.</div>
                </div>

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar ajustes
                </button>
            </div>
        </div>

    </form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#abandoned_cart_enabled').on('change', function () {
        $('#abandoned-cart-config-block').slideToggle(150);
    });
});
</script>
@endpush
