@extends('layouts.theme')

@section('title', 'Seguimiento y analítica')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Seguimiento y analítica'])
    @include('core::components.alerts')

    @php
        $pixelEnabled = old('facebook_pixel_enabled', $settings['facebook_pixel_enabled'] ?? '') == '1';
    @endphp

    <form action="{{ route('settings.ecommerce.tracking.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="mb-3">
            <h5 class="fw-bold mb-1">Administrar seguimiento</h5>
            <div class="text-muted small">Gestionar seguimiento: UTM, Facebook, Google Tag Manager...</div>
        </div>

        <div class="card mb-4">
            <div class="card-body">

                {{-- Facebook Pixel --}}
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="facebook_pixel_enabled" value="1"
                            id="facebook_pixel_enabled"
                            {{ $pixelEnabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="facebook_pixel_enabled">Habilitar píxel de Facebook (Metapixel)</label>
                    </div>
                </div>

                <div id="facebook-pixel-block" style="{{ $pixelEnabled ? '' : 'display:none' }}">
                    <div class="mb-3">
                        <label for="facebook_pixel_id" class="form-label fw-semibold">ID de píxel de Facebook</label>
                        <input type="text" name="facebook_pixel_id" id="facebook_pixel_id"
                            class="form-control @error('facebook_pixel_id') is-invalid @enderror"
                            placeholder="123456789012345"
                            value="{{ old('facebook_pixel_id', $settings['facebook_pixel_id'] ?? '') }}">
                        @error('facebook_pixel_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1">Vaya a <a href="https://developers.facebook.com/docs/meta-pixel" target="_blank">https://developers.facebook.com/docs/meta-pixel</a> para crear Facebook Pixel.</div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Google Tag Manager --}}
                <div class="mb-0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="google_tag_manager_enabled" value="1"
                            id="google_tag_manager_enabled"
                            {{ old('google_tag_manager_enabled', $settings['google_tag_manager_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="google_tag_manager_enabled">Habilite los eventos de seguimiento de Google Tag Manager</label>
                    </div>
                    <div class="form-text mt-1">Debe agregar Google Tag Manager para rastrear su sitio web, vaya a Administrador → Configuración → Seguimiento del sitio web.</div>
                </div>

            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
        </div>

    </form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#facebook_pixel_enabled').on('change', function () {
        $('#facebook-pixel-block').slideToggle(150);
    });
});
</script>
@endpush
