@extends('layouts.theme')

@section('page_title', 'IndexNow')

@section('content')
    @include('core::components.alerts')

    <div class="row g-4">
        {{-- Estado de configuración --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1">Estado</h5>
                    <p class="small mb-3 text-muted">IndexNow avisa a Bing, Yandex y Seznam al instante cuando publicas o actualizas contenido.</p>

                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Activado</span>
                            @if($status['enabled'])
                                <span class="badge bg-success-subtle text-success">Sí</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Auto-submit</span>
                            <span class="fw-bold">{{ $status['auto_submit'] ? 'Sí' : 'No' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Host</span>
                            <code class="small">{{ $status['host'] ?: '—' }}</code>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Batch size</span>
                            <span class="fw-bold">{{ $status['batch_size'] }}</span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <h6 class="fw-bold mb-2">Key</h6>
                    @if($status['key'])
                        <code class="small d-block text-break mb-2">{{ $status['key'] }}</code>
                        <p class="text-muted small mb-1">Archivo de verificación:</p>
                        @if($status['key_location'])
                            <a href="{{ $status['key_location'] }}" target="_blank" rel="noopener" class="small text-break">
                                {{ $status['key_location'] }}
                            </a>
                        @endif
                    @else
                        <div class="alert alert-warning small mb-0">
                            Falta <code>SEO_INDEXNOW_KEY</code> en <code>.env</code>. Genera una cadena alfanumérica de 8-128 caracteres y añádela.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2 border-bottom pb-2">Configuración (.env)</h6>
                    <pre class="small bg-light p-2 rounded mb-0" style="font-size: 0.72rem;">SEO_INDEXNOW_ENABLED=true
SEO_INDEXNOW_KEY=tu_key_aqui
SEO_INDEXNOW_AUTO_SUBMIT=true
SEO_INDEXNOW_BATCH_SIZE=100</pre>
                </div>
            </div>
        </div>

        {{-- Submit manual --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">Enviar URLs manualmente</h5>
                    <p class="small mb-0 text-muted">Una URL por línea. Deben compartir el host configurado: <code>{{ $status['host'] ?: '—' }}</code>.</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('settings.seo.indexnow.submit') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="urls-input" class="form-label fw-semibold">URLs</label>
                            <textarea id="urls-input" name="urls" rows="10"
                                      class="form-control font-monospace @error('urls') is-invalid @enderror"
                                      placeholder="https://{{ $status['host'] ?: 'tu-sitio.com' }}/mi-pagina{{ "\n" }}https://{{ $status['host'] ?: 'tu-sitio.com' }}/otra-pagina"
                                      @if(! $status['enabled']) disabled @endif>{{ old('urls') }}</textarea>
                            @error('urls')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100" @if(! $status['enabled']) disabled @endif>
                            Enviar a IndexNow
                        </button>

                        @if(! $status['enabled'])
                            <div class="alert alert-warning small mt-3 mb-0">
                                IndexNow está desactivado. Configura <code>SEO_INDEXNOW_ENABLED=true</code> y una <code>SEO_INDEXNOW_KEY</code> válida en <code>.env</code>.
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2 border-bottom pb-2">¿Qué es IndexNow?</h6>
                    <p class="text-muted small mb-2">
                        Protocolo abierto de Bing, Yandex y Seznam que permite notificar actualizaciones de contenido
                        sin esperar al rastreo periódico. Reduce el tiempo hasta la indexación de días a segundos.
                    </p>
                    <p class="text-muted small mb-0">
                        Spec: <a href="https://www.indexnow.org/documentation" target="_blank" rel="noopener">indexnow.org/documentation</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
