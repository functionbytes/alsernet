@extends('layouts.theme')

@section('page_title', 'Versión v' . $version->version_number . ' — ' . $page->title)

@section('content')

    @include('core::components.card', ['title' => 'Versiones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="row">

            {{-- Contenido principal --}}
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">
                                    <span class="badge bg-primary-subtle text-primary me-1">v{{ $version->version_number }}</span>
                                    {{ $version->title }}
                                    @if($loop->first ?? false)
                                        <span class="badge bg-success-subtle text-success ms-1">Actual</span>
                                    @endif
                                </h5>
                                <p class="small mb-0 text-muted">
                                    {{ $version->created_at->format('d/m/Y H:i') }} · {{ $version->created_at->diffForHumans() }}
                                    @if($version->user)
                                        · {{ $version->user->full_name ?? $version->user->name }}
                                    @else
                                        · Sistema
                                    @endif
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                <form method="POST"
                                      action="{{ route('pages.versions.restore', [$page->id, $version->id]) }}"
                                      class="d-inline"
                                      id="restoreForm">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" id="restoreBtn">
                                        Restaurar versión
                                    </button>
                                </form>
                                <a href="{{ route('pages.versions.index', $page->id) }}" class="btn btn-secondary">
                                    Volver
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($version->content)
                            <div class="p-3 bg-light rounded">
                                {!! clean_html($version->content) !!}
                            </div>
                        @else
                            <p class="text-muted mb-0"><em>Sin contenido</em></p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar derecho --}}
            <div class="col-lg-4">

                {{-- Información --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Información</h5>
                        <p class="small mb-0 text-muted">Datos de la versión</p>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-muted fw-semibold">Versión</dt>
                            <dd class="col-7">
                                <span class="badge bg-primary-subtle text-primary">v{{ $version->version_number }}</span>
                            </dd>

                            <dt class="col-5 text-muted fw-semibold">Estado</dt>
                            <dd class="col-7">
                                @if($version->status === 'published')
                                    <span class="badge bg-success-subtle text-success">Publicado</span>
                                @elseif($version->status === 'draft')
                                    <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                @endif
                            </dd>

                            <dt class="col-5 text-muted fw-semibold">Creado</dt>
                            <dd class="col-7">
                                {{ $version->created_at->format('d/m/Y H:i') }}
                                <p class="d-block text-muted">{{ $version->created_at->diffForHumans() }}</p>
                            </dd>

                            <dt class="col-5 text-muted fw-semibold">Autor</dt>
                            <dd class="col-7">
                                @if($version->user)
                                    {{ $version->user->full_name ?? $version->user->name }}
                                @else
                                    <p class="text-muted">Sistema</p>
                                @endif
                            </dd>

                            <dt class="col-5 text-muted fw-semibold">Plantilla</dt>
                            <dd class="col-7">{{ $version->template ?? 'default' }}</dd>

                            <dt class="col-5 text-muted fw-semibold">Slug</dt>
                            <dd class="col-7"><p >{{ $version->slug }}</p></dd>

                            <dt class="col-5 text-muted fw-semibold mb-0">Tamaño</dt>
                            <dd class="col-7 mb-0">{{ number_format($version->getContentSize() / 1024, 2) }} KB</dd>
                        </dl>
                    </div>
                </div>

                {{-- Descripción --}}
                @if($version->description)
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Descripcion</h5>
                        <p class="small mb-0 text-muted">Descripción breve de la página</p>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 small">{{ $version->description }}</p>
                    </div>
                </div>
                @endif

                {{-- SEO --}}
                @if($version->seo_title || $version->seo_description || $version->seo_keywords)
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">SEO</h5>
                        <p class="small mb-0 text-muted">Metadatos para buscadores</p>
                    </div>
                    <div class="card-body">
                        @if($version->seo_title)
                        <div class="mb-3">
                            <p class="small fw-semibold text-muted mb-1">Título SEO</p>
                            <p class="mb-0 small">{{ $version->seo_title }}</p>
                        </div>
                        @endif

                        @if($version->seo_description)
                        <div class="mb-3">
                            <p class="small fw-semibold text-muted mb-1">Descripcion SEO</p>
                            <p class="mb-0 small">{{ $version->seo_description }}</p>
                        </div>
                        @endif

                        @if($version->seo_keywords)
                        <div class="mb-0">
                            <p class="small fw-semibold text-muted mb-1">Palabras clave</p>
                            <p class="mb-0 small">{{ $version->seo_keywords }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>
        </div>

    </div>

    {{-- Modal confirmar restaurar --}}
    <div class="modal fade" id="modal-restore-version" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Restaurar versión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Restaurar v{{ $version->version_number }}? La versión actual se guardará automáticamente.
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" id="btn-confirm-restore" class="btn btn-primary w-100 mb-2">Restaurar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#restoreBtn').on('click', function (e) {
        e.preventDefault();
        $('#modal-restore-version').modal('show');
    });

    $('#btn-confirm-restore').on('click', function () {
        $('#modal-restore-version').modal('hide');
        $('#restoreForm').submit();
    });

    @if(session('success'))
    toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
    toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
