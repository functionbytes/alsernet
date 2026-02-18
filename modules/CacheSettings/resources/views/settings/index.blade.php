@extends('layouts.theme')

@section('title', 'Configuracion de cache')

@section('content')
    @include('core::components.card', ['title' => 'Configuracion de cache'])

    @include('core::components.alerts')

    <form method="POST" action="{{ route('settings.cache.update') }}">
        @csrf
        @method('PATCH')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">Configuracion de cache</h5>
                <p class="small mb-0 text-muted">Controla que elementos del sistema se almacenan en cache para mejorar el rendimiento</p>
            </div>

            {{-- Cache de menus --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Cache de menus</h6>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="admin_menu_enabled"
                               id="admin_menu_enabled" value="1"
                               {{ $get('admin_menu_enabled') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="admin_menu_enabled">
                            Habilitar cache del menu de administracion
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Almacena en cache el menu del panel de administracion. Recomendado en produccion.
                    </small>
                </div>

                <div class="mb-0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="frontend_menu_enabled"
                               id="frontend_menu_enabled" value="1"
                               {{ $get('frontend_menu_enabled') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="frontend_menu_enabled">
                            Habilitar cache del menu del sitio web
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Almacena en cache los menus de navegacion del frontend.
                    </small>
                </div>
            </div>

            {{-- Cache de avatares --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Cache de avatares</h6>

                <div class="mb-0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="user_avatars_enabled"
                               id="user_avatars_enabled" value="1"
                               {{ $get('user_avatars_enabled') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="user_avatars_enabled">
                            Habilitar cache de avatares de usuarios
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Almacena en cache las imagenes de perfil de los usuarios para reducir peticiones al servidor.
                    </small>
                </div>
            </div>

            {{-- Cache de sitemap --}}
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Cache de sitemap</h6>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sitemap_enabled"
                               id="sitemap_enabled" value="1"
                               {{ $get('sitemap_enabled') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="sitemap_enabled">
                            Habilitar cache del sitemap
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Almacena en cache el sitemap XML para evitar regenerarlo en cada peticion.
                    </small>
                </div>

                <div class="col-md-4">
                    <label for="sitemap_ttl" class="form-label">Tiempo de vida del cache (minutos)</label>
                    <input type="number" class="form-control" id="sitemap_ttl" name="sitemap_ttl"
                           min="1" max="10080"
                           value="{{ old('sitemap_ttl', $get('sitemap_ttl', 1440)) }}">
                    <small class="text-muted d-block mt-1">
                        Tiempo en minutos antes de regenerar el sitemap. Minimo: 1. Maximo: 10080 (7 dias). Por defecto: 1440 (24 horas).
                    </small>
                </div>
            </div>

            {{-- Footer --}}
            <div class="card-footer p-4 border-top d-flex justify-content-end gap-2">
                <a href="javascript:history.back()" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar cambios
                </button>
            </div>
        </div>
    </form>
@endsection
