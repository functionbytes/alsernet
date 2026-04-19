@extends('layouts.theme')

@section('title', 'Configuracion de cache')

@section('content')
    @include('core::components.card', ['title' => 'Configuracion de cache'])

    @include('core::components.alerts')

    <form id="form-cache-settings" method="POST" action="{{ route('settings.cache.update') }}">
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
            <div class="card-body border-bottom p-4">
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

                <div class="col-md-12">
                    <label for="sitemap_ttl" class="form-label">Tiempo de vida del cache (minutos)</label>
                    <input type="number" class="form-control" id="sitemap_ttl" name="sitemap_ttl"
                           min="1" max="10080"
                           value="{{ old('sitemap_ttl', $get('sitemap_ttl', 1440)) }}">
                    <small class="text-muted d-block mt-1">
                        Tiempo en minutos antes de regenerar el sitemap. Minimo: 1. Maximo: 10080 (7 dias). Por defecto: 1440 (24 horas).
                    </small>
                </div>
            </div>

            @if(\Modules\System\Helpers\ModuleStatusHelper::isModuleEnabled('Page'))
                {{-- Cache de páginas --}}
                <div class="card-body border-bottom p-4">
                    <h6 class="fw-bold mb-3">Cache de páginas</h6>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="pages_enabled"
                                   id="pages_enabled" value="1"
                                   {{ $get('pages_enabled') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="pages_enabled">
                                Habilitar cache de páginas
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Almacena en cache cada página en Redis para servir contenido más rápidamente.
                        </small>
                    </div>

                    <div class="col-md-12">
                        <label for="pages_ttl" class="form-label">Tiempo de vida del cache (minutos)</label>
                        <input type="number" class="form-control" id="pages_ttl" name="pages_ttl"
                               min="1" max="10080"
                               value="{{ old('pages_ttl', $get('pages_ttl', 1440)) }}">
                        <small class="text-muted d-block mt-1">
                            Tiempo en minutos antes de regenerar el cache de páginas. Minimo: 1. Maximo: 10080 (7 dias). Por defecto: 1440 (24 horas).
                        </small>
                    </div>
                </div>

            @endif

            {{-- Footer --}}
            <div class="card-footer">
                <button type="button" class="btn btn-primary w-100 mb-1" data-bs-toggle="modal" data-bs-target="#modal-save-cache">
                    Guardar cambios
                </button>
                <a href="{{ route('settings.cache.monitor') }}" class="btn btn-secondary w-100">Monitor de cache</a>
            </div>
        </div>
    </form>

    <div class="modal fade" id="modal-save-cache" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar cambios de cache</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Confirmas que quieres guardar la configuracion de cache?</p>
                    <p class="text-muted mb-0">Cambiar la configuracion puede afectar el rendimiento del sistema temporalmente mientras se regenera el cache.</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" form="form-cache-settings" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush
@endsection
