@extends('layouts.theme')

@section('page_title', 'Google Search Console')

@section('content')
    @include('core::components.alerts')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">Google Search Console</h5>
                    <p class="small mb-0 text-muted">Importa clicks, impresiones y posiciones directamente del API de GSC. Una vez conectado, se ejecuta automáticamente o bajo demanda.</p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">OAuth configurado</h6>
                                    @if($status['configured'])
                                        <span class="badge bg-success-subtle text-success fs-6">Sí</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger fs-6">No</span>
                                        <p class="small text-muted mt-2 mb-0">Falta client_id / client_secret. Configúralos en <a href="{{ route('settings.seo.settings.edit') }}">Configuración SEO</a>.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Cuenta conectada</h6>
                                    @if($status['connected'])
                                        <span class="badge bg-success-subtle text-success fs-6">Sí</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary fs-6">No</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL de la propiedad en GSC</label>
                        <code class="d-block">{{ $status['property_url'] ?: '—' }}</code>
                        <small class="text-muted">Se edita en la configuración (clave <code>seo.gsc.property_url</code>).</small>
                    </div>

                    @if(! $status['connected'])
                        <a href="{{ route('settings.seo.gsc.connect') }}" class="btn btn-primary w-100 mb-2" @if(! $status['configured']) aria-disabled="true" @endif>
                            Conectar con Google Search Console
                        </a>
                    @else
                        <form method="POST" action="{{ route('settings.seo.gsc.import') }}" class="mb-2">
                            @csrf
                            <div class="d-flex gap-2">
                                <input type="number" name="days" min="1" max="90" value="28" class="form-control" style="max-width:120px;">
                                <button type="submit" class="btn btn-primary flex-fill">Importar últimos N días</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('settings.seo.gsc.disconnect') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">Desconectar</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2 border-bottom pb-2">Cómo configurar</h6>
                    <ol class="small text-muted mb-0 ps-3">
                        <li class="mb-2"><a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a> → crear OAuth Client ID (Web application).</li>
                        <li class="mb-2">Agregar redirect URI: <code class="small text-break">{{ route('settings.seo.gsc.callback') }}</code></li>
                        <li class="mb-2">Copiar client_id y client_secret a <a href="{{ route('settings.seo.settings.edit') }}">Configuración SEO</a>.</li>
                        <li class="mb-2">Activar la "Search Console API" para ese proyecto Google Cloud.</li>
                        <li>Pulsar "Conectar" arriba y autorizar.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
