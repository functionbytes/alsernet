@extends('layouts.theme')

@section('page_title', 'Configuracion de páginas')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuracion de páginas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="{{ route('settings.pages.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-header p-3 bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">Permalink</h5>
                                    <p class="small mb-0 text-muted">Configuracion del prefijo de URL para páginas públicas</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="mb-3">
                                <label for="permalink_prefix" class="form-label fw-semibold">
                                    Prefijo de URL para páginas
                                </label>
                                <input type="text"
                                       class="form-control @error('permalink_prefix') is-invalid @enderror"
                                       id="permalink_prefix"
                                       name="permalink_prefix"
                                       value="{{ old('permalink_prefix', setting('permalink-modules-page-models-page', '')) }}"
                                       placeholder="Dejar vacío para la raíz">
                                <div class="form-text">
                                    Ejemplo: si configuras <code>paginas</code>, las URLs serán:
                                    <code>{{ url('/') }}/paginas/mi-pagina</code>
                                </div>
                                @error('permalink_prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-info py-2 mb-0">
                                <small>
                                    <strong>URL resultante:</strong>
                                    <span id="url-preview">
                                        @php $currentPrefix = setting('permalink-modules-page-models-page', ''); @endphp
                                        {{ url('/') }}/{{ $currentPrefix ? $currentPrefix . '/' : '' }}mi-pagina
                                    </span>
                                </small>
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary w-100">Guardar</button>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header p-3 bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">Página de aterrizaje (Homepage)</h5>
                                    <p class="small mb-0 text-muted">Selecciona la página que se mostrará en la ruta raíz <code>/</code></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="homepage_page_id" class="form-label fw-semibold">
                                    Página de inicio
                                </label>
                                <select name="homepage_page_id"
                                        id="homepage_page_id"
                                        class="form-select @error('homepage_page_id') is-invalid @enderror">
                                    <option value="">— Usar página con template 'homepage' —</option>
                                    @foreach ($publishedPages as $page)
                                        <option value="{{ $page->id }}"
                                            {{ (int) old('homepage_page_id', setting('homepage-page-id')) === $page->id ? 'selected' : '' }}>
                                            {{ $page->title }}
                                            @if ($page->slug) ({{ $page->slug }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    Cuando dejes en blanco, usará la página publicada con <code>template = 'homepage'</code>.
                                </div>
                                @error('homepage_page_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary w-100">Guardar</button>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header p-3 bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">Idiomas soportados</h5>
                                    <p class="small mb-0 text-muted">Idiomas activos disponibles para traducir páginas</p>
                                </div>

                            </div>
                        </div>
                        <div class="card-body">
                            @if ($activeLocales->isEmpty())
                                <div class="alert alert-warning mb-0 py-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    No hay idiomas activos. <a href="{{ route('locales.index') }}">Activa al menos uno</a>.
                                </div>
                            @else
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($activeLocales as $locale)
                                        <span class="badge bg-primary-subtle text-primary d-flex align-items-center gap-1 py-2 px-3">
                                            {{ $locale->native_name }}
                                            <code class="text-primary ms-1" style="font-size:.7rem">{{ $locale->code }}</code>
                                            @if ($locale->is_default)
                                                <span class="badge bg-primary ms-1" style="font-size:.6rem">predeterminado</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                                <p class="form-text mt-2 mb-0">
                                    Los idiomas se gestionan desde
                                    <a href="{{ route('locales.index') }}">Configuración › Localización › Idiomas</a>.
                                </p>
                            @endif
                        </div>


                        @if (Route::has('locales.index'))

                            <div class="card-header p-3 bg-white border-bottom">
                                <a href="{{ route('locales.index') }}" class="btn btn-sm btn-info w-100">
                                    <i class="fas fa-globe me-1"></i> Gestionar idiomas
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="card mt-3">
                        <div class="card-header p-3 bg-white border-bottom">
                            <div>
                                <h5 class="mb-1 fw-bold">Páginas de error</h5>
                                <p class="small mb-0 text-muted">Asigna una página publicada para cada código de error HTTP</p>
                            </div>
                        </div>
                        <div class="card-body">
                            @php
                                $errorGroups = [
                                    '4xx - Errores del cliente' => [
                                        '400' => ['label' => '400 - Solicitud incorrecta'],
                                        '401' => ['label' => '401 - No autenticado'],
                                        '403' => ['label' => '403 - Acceso denegado'],
                                        '404' => ['label' => '404 - Página no encontrada'],
                                        '405' => ['label' => '405 - Método no permitido'],
                                        '408' => ['label' => '408 - Tiempo de espera agotado'],
                                        '410' => ['label' => '410 - Recurso eliminado'],
                                        '419' => ['label' => '419 - Sesión expirada'],
                                        '422' => ['label' => '422 - Datos inválidos'],
                                        '429' => ['label' => '429 - Demasiadas solicitudes'],
                                    ],
                                    '5xx - Errores del servidor' => [
                                        '500' => ['label' => '500 - Error interno'],
                                        '502' => ['label' => '502 - Puerta de enlace fallida'],
                                        '503' => ['label' => '503 - Servicio no disponible'],
                                        '504' => ['label' => '504 - Tiempo de espera gateway'],
                                    ],
                                ];
                            @endphp

                            @foreach ($errorGroups as $groupLabel => $errorDefs)
                                <h3 class="small fw-semibold text-muted text-uppercase mb-5 mt-3">{{ $groupLabel }}</h3>

                                @foreach ($errorDefs as $code => $def)
                                    <div class="mb-3">
                                        <label for="error_page_{{ $code }}" class="form-label fw-semibold">
                                            {{ $def['label'] }}
                                        </label>
                                        <select name="error_page_{{ $code }}"
                                                id="error_page_{{ $code }}"
                                                class="form-select @error('error_page_'.$code) is-invalid @enderror">
                                            <option value="">— Usar contenido predeterminado —</option>
                                            @foreach ($publishedPages as $page)
                                                <option value="{{ $page->id }}"
                                                    {{ old("error_page_{$code}", setting("error-page-{$code}")) == $page->id ? 'selected' : '' }}>
                                                    {{ $page->title }}
                                                    @if ($page->slug) ({{ $page->slug }}) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('error_page_'.$code)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endforeach
                                <hr>
                            @endforeach
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary w-100">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Cómo funciona</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <p class="small mb-1"><strong>Sin prefijo:</strong></p>
                                <p class="text-muted mb-0">Las páginas se sirven directamente desde la raíz, por ejemplo <code>/mi-pagina</code>.</p>
                            </div>
                            <div>
                                <p class="small mb-1"><strong>Con prefijo:</strong></p>
                                <p class="text-muted mb-0">Se agrega un segmento antes del slug, por ejemplo <code>/paginas/mi-pagina</code>.</p>
                            </div>
                            <div class="alert alert-warning py-2 mb-0">
                                <small>Cambiar el prefijo afecta todas las URLs existentes. Si tienes páginas indexadas, configura redirecciones antes de modificarlo.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Páginas de error</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <p class="small mb-1"><strong>¿Cómo funciona?</strong></p>
                                <p class="text-muted mb-0">Cuando ocurre un error HTTP, el sistema muestra el contenido de la página seleccionada. Puedes editarla desde el panel de páginas.</p>
                            </div>
                            <div class="alert alert-info py-2 mb-0">
                                <small>Si no seleccionas ninguna página, se mostrará el diseño predeterminado del sistema.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Idiomas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <p class="small mb-1"><strong>Traducciones:</strong></p>
                                <p class="text-muted mb-0">Cada idioma habilitado añade una pestaña de traducción al crear o editar páginas.</p>
                            </div>
                            <div>
                                <p class="small mb-1"><strong>Público:</strong></p>
                                <p class="text-muted mb-0">Los visitantes verán el contenido en el idioma detectado de su navegador si hay traducción disponible.</p>
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
(function () {
    const baseUrl = '{{ url('/') }}';
    const input = document.getElementById('permalink_prefix');
    const preview = document.getElementById('url-preview');

    input.addEventListener('input', function () {
        const prefix = this.value.trim().replace(/^\/+|\/+$/g, '');
        preview.textContent = prefix
            ? `${baseUrl}/${prefix}/mi-pagina`
            : `${baseUrl}/mi-pagina`;
    });

})();
</script>
@endpush
