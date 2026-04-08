@extends('layouts.theme')

@section('title', 'Verificación de motores de búsqueda')

@section('content')

    @include('core::components.card', ['title' => 'Verificación de motores de búsqueda'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <form method="POST" action="{{ route('setting.seo.verification.update') }}" id="verificationForm">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-body">

                            <h6 class="fw-bold text-dark mb-1">Códigos de verificación</h6>
                            <p class="text-muted mb-3">Ingresa los códigos de verificación proporcionados por cada motor de búsqueda. Se insertarán automáticamente como meta tags en el <code>&lt;head&gt;</code> de tu sitio.</p>

                            {{-- Google Search Console --}}
                            <div class="mb-4">
                                <label for="seo_google_verification" class="form-label fw-semibold">
                                    Google Search Console
                                </label>
                                <input type="text" class="form-control font-monospace" id="seo_google_verification"
                                       name="seo_google_verification"
                                       value="{{ $settings['seo_google_verification'] }}"
                                       placeholder="abc123def456ghi789">
                                <small class="text-muted d-block mt-1">
                                    Meta tag: <code>&lt;meta name="google-site-verification" content="..."&gt;</code>
                                </small>
                            </div>

                            {{-- Bing Webmaster Tools --}}
                            <div class="mb-4">
                                <label for="seo_bing_verification" class="form-label fw-semibold">
                                    Bing Webmaster Tools
                                </label>
                                <input type="text" class="form-control font-monospace" id="seo_bing_verification"
                                       name="seo_bing_verification"
                                       value="{{ $settings['seo_bing_verification'] }}"
                                       placeholder="ABCDEF1234567890ABCDEF1234567890">
                                <small class="text-muted d-block mt-1">
                                    Meta tag: <code>&lt;meta name="msvalidate.01" content="..."&gt;</code>
                                </small>
                            </div>

                            {{-- Pinterest --}}
                            <div class="mb-4">
                                <label for="seo_pinterest_verification" class="form-label fw-semibold">
                                    Pinterest
                                </label>
                                <input type="text" class="form-control font-monospace" id="seo_pinterest_verification"
                                       name="seo_pinterest_verification"
                                       value="{{ $settings['seo_pinterest_verification'] }}"
                                       placeholder="abcdef1234567890">
                                <small class="text-muted d-block mt-1">
                                    Meta tag: <code>&lt;meta name="p:domain_verify" content="..."&gt;</code>
                                </small>
                            </div>

                            {{-- Baidu --}}
                            <div class="mb-4">
                                <label for="seo_baidu_verification" class="form-label fw-semibold">
                                    Baidu Webmaster Tools
                                </label>
                                <input type="text" class="form-control font-monospace" id="seo_baidu_verification"
                                       name="seo_baidu_verification"
                                       value="{{ $settings['seo_baidu_verification'] }}"
                                       placeholder="code-abcdefgh">
                                <small class="text-muted d-block mt-1">
                                    Meta tag: <code>&lt;meta name="baidu-site-verification" content="..."&gt;</code>
                                </small>
                            </div>

                            {{-- Yandex --}}
                            <div class="mb-0">
                                <label for="seo_yandex_verification" class="form-label fw-semibold">
                                    Yandex Webmaster
                                </label>
                                <input type="text" class="form-control font-monospace" id="seo_yandex_verification"
                                       name="seo_yandex_verification"
                                       value="{{ $settings['seo_yandex_verification'] }}"
                                       placeholder="1234567890abcdef">
                                <small class="text-muted d-block mt-1">
                                    Meta tag: <code>&lt;meta name="yandex-verification" content="..."&gt;</code>
                                </small>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>
                    </div>

                </form>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Estado actual</h6>
                        <p class="text-muted mb-3">Motores de búsqueda verificados en tu sitio.</p>

                        <div class="d-flex flex-column gap-2">
                            @php
                                $engines = [
                                    ['key' => 'seo_google_verification', 'name' => 'Google'],
                                    ['key' => 'seo_bing_verification', 'name' => 'Bing'],
                                    ['key' => 'seo_pinterest_verification', 'name' => 'Pinterest'],
                                    ['key' => 'seo_baidu_verification', 'name' => 'Baidu'],
                                    ['key' => 'seo_yandex_verification', 'name' => 'Yandex'],
                                ];
                            @endphp
                            @foreach($engines as $engine)
                                <div class="d-flex align-items-center justify-content-between">
                                    <span>{{ $engine['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cómo obtener los códigos</h6>
                    </div>
                    <div class="card-body">

                        <h6 class="fw-semibold mb-2">
                            Google Search Console
                        </h6>
                        <ol class="text-muted ps-3 mb-3">
                            <li class="mb-1">Ve a <a href="https://search.google.com/search-console" target="_blank" class="fw-semibold">Google Search Console</a></li>
                            <li class="mb-1">Agrega tu propiedad (URL del sitio)</li>
                            <li class="mb-1">Selecciona verificación por <strong>Etiqueta HTML</strong></li>
                            <li>Copia solo el valor del atributo <code>content</code></li>
                        </ol>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">
                            Bing Webmaster Tools
                        </h6>
                        <ol class="text-muted ps-3 mb-3">
                            <li class="mb-1">Ve a <a href="https://www.bing.com/webmasters" target="_blank" class="fw-semibold">Bing Webmaster Tools</a></li>
                            <li class="mb-1">Agrega tu sitio web</li>
                            <li>Selecciona <strong>Etiqueta meta</strong> y copia el valor de <code>content</code></li>
                        </ol>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">
                            Pinterest
                        </h6>
                        <ol class="text-muted ps-3 mb-0">
                            <li class="mb-1">Ve a <a href="https://www.pinterest.com/settings/claim" target="_blank" class="fw-semibold">Pinterest Settings</a></li>
                            <li class="mb-1">En "Reclamar", selecciona tu sitio web</li>
                            <li>Copia el código de verificación de la etiqueta HTML</li>
                        </ol>

                    </div>
                </div>
            </div>

        </div>

    </div>

    @push('scripts')
    <script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const form = document.getElementById('verificationForm');

        form?.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch(this.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
                if (data.status) {
                    toastr.success(data.message, 'Guardado exitoso');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(data.message, 'Error al guardar');
                }
            })
            .catch(() => toastr.error('Error al guardar configuración', 'Error'));
        });
    })();
    </script>
    @endpush

@endsection
