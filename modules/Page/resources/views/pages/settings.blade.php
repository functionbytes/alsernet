@extends('layouts.theme')

@section('page_title', 'Configuracion de páginas')

@section('content')

    @include('core::components.card', ['title' => 'Configuracion de páginas'])

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
                            <button type="submit" class="btn btn-primary">Guardar</button>
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
