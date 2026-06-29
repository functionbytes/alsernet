@extends('layouts.theme')

@section('title', 'Seguimiento y analítica')

@section('content')

    @php
        $pixelEnabled = old('facebook_pixel_enabled', $settings['facebook_pixel_enabled'] ?? '') == '1';
    @endphp

    <form action="{{ route('settings.ecommerce.tracking.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-header border-bottom p-3">
                <h5 class="mb-0 fw-bold">Seguimiento y analítica</h5>
                <small class="text-muted">Gestiona seguimiento: UTM, Facebook Pixel, Google Tag Manager…</small>
            </div>
            <div class="card-body">
                @include('core::components.alerts')

                {{-- Facebook Pixel --}}
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="facebook_pixel_enabled" value="1"
                            id="facebook_pixel_enabled"
                            {{ $pixelEnabled ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="facebook_pixel_enabled">Píxel de Facebook (Meta Pixel)</label>
                    </div>
                </div>

                <div id="facebook-pixel-block" style="{{ $pixelEnabled ? '' : 'display:none' }}">
                    <div class="mb-3 ps-3 ">
                        <label for="facebook_pixel_id" class="form-label">ID del píxel</label>
                        <input type="text" name="facebook_pixel_id" id="facebook_pixel_id"
                            class="form-control @error('facebook_pixel_id') is-invalid @enderror"
                            placeholder="123456789012345"
                            value="{{ old('facebook_pixel_id', $settings['facebook_pixel_id'] ?? '') }}">
                        @error('facebook_pixel_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-1 text-muted">Crea tu Meta Pixel en <a href="https://developers.facebook.com/docs/meta-pixel" target="_blank">developers.facebook.com</a>.</div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Google Tag Manager --}}
                <div class="mb-0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="google_tag_manager_enabled" value="1"
                            id="google_tag_manager_enabled"
                            {{ old('google_tag_manager_enabled', $settings['google_tag_manager_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="google_tag_manager_enabled">Eventos de Google Tag Manager</label>
                    </div>
                    <div class="form-text mt-1 text-muted">Configura GTM en Administrador → Configuración → Seguimiento del sitio web.</div>
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
    $('#facebook_pixel_enabled').on('change', function () {
        $('#facebook-pixel-block').slideToggle(150);
    });
});
</script>
@endpush
