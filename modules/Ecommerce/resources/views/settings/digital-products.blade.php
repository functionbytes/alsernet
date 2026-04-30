@extends('layouts.theme')

@section('title', 'Productos digitales')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Productos digitales'])
@endsection

@section('content')
    @include('core::components.alerts')

    @php $enabled = old('digital_products_enabled', $settings['digital_products_enabled'] ?? '') == '1'; @endphp

    <form action="{{ route('settings.ecommerce.digital-products.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-header p-3 border-bottom">
                <h6 class="mb-0 fw-bold">Productos digitales</h6>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="digital_products_enabled" value="1" id="digital_products_enabled"
                        {{ $enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="digital_products_enabled">Habilitar productos digitales</label>
                </div>
                <div class="form-text mb-0">Permite crear productos descargables o con licencia de activación.</div>

                <div id="digital-extra-options" class="mt-3 ps-2 border-start border-2" style="{{ $enabled ? '' : 'display:none' }}">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="digital_products_guest_checkout" value="1" id="digital_products_guest_checkout"
                            {{ old('digital_products_guest_checkout', $settings['digital_products_guest_checkout'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="digital_products_guest_checkout">Permitir el pago de invitados para productos digitales</label>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="digital_products_disable_physical" value="1" id="digital_products_disable_physical"
                            {{ old('digital_products_disable_physical', $settings['digital_products_disable_physical'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="digital_products_disable_physical">Desactivar producto físico</label>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100">Guardar cambios</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#digital_products_enabled').on('change', function () {
        $('#digital-extra-options').slideToggle(150);
    });
});
</script>
@endpush
