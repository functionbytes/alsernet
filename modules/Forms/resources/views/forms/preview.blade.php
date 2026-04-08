@extends('layouts.theme')

@section('title', 'Preview: ' . $form->name)

@push('css')
<style>
    #previewFrame {
        transition: max-width .3s ease;
        display: block;
        width: 100%;
        min-height: 600px;
        border: none;
    }
    #previewFrame.preview-mobile-view {
        max-width: 390px;
        margin: 0 auto;
    }
    .btn-group .btn.active {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
        color: #fff;
    }
</style>
@endpush

@section('content')

    @include('core::components.card', ['title' => 'Vista previa del formulario'])

    @include('core::components.alerts')

    <div class="row g-3">

        {{-- Preview principal --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold">Vista previa</h6>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Vista de dispositivo">
                            <button type="button" class="btn btn-outline-primary active" id="btnDesktop">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnMobile">
                                <i class="fas fa-mobile-screen"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <iframe
                        id="previewFrame"
                        src="{{ route('forms.public.preview.public', [$form, $previewToken]) }}"
                        title="Preview: {{ $form->name }}">
                    </iframe>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>Vista previa con el template activo. El formulario no enviará datos reales.
                    </small>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-lg-4">

            {{-- Detalle del formulario --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom bg-warning-subtle">
                    <h6 class="mb-0 fw-bold">Detalle del formulario</h6>
                    <small class="text-muted">Información del formulario</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">NOMBRE</h6>
                            <p class="mb-0 fw-bold">{{ $form->name }}</p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">SLUG</h6>
                            <p class="mb-0"><code>{{ $form->slug }}</code></p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">ESTADO</h6>
                            <p class="mb-0">
                                @if ($form->is_active)
                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">CAMPOS</h6>
                            <p class="mb-0">{{ $form->fields->count() }}</p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">SUBMISSIONS</h6>
                            <p class="mb-0">
                                <a href="{{ route('settings.forms.submissions.index', $form) }}">
                                    {{ $form->submissions()->count() }}
                                </a>
                            </p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">CATEGORÍA</h6>
                            <p class="mb-0">{{ $form->category->name ?? 'Sin categoría' }}</p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">CREADO</h6>
                            <p class="mb-0 small">{{ $form->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if (isset($previewToken))
                            <div class="col-12">
                                <h6 class="text-muted fw-semibold small mb-1">URL PÚBLICA DE PREVIEW</h6>
                                <p class="mb-0 d-flex align-items-center gap-2">
                                    <code class="small text-break">{{ route('forms.public.preview.public', [$form, $previewToken]) }}</code>
                                    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                        onclick="navigator.clipboard.writeText('{{ route('forms.public.preview.public', [$form, $previewToken]) }}'); toastr.success('URL copiada')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Acciones rápidas --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Acciones rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('settings.forms.edit', $form) }}" class="btn btn-primary">
                            Editar formulario
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary">
                            Ver respuestas
                        </a>
                        <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary">
                            Volver
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    $('#btnDesktop').on('click', function () {
        $('#btnDesktop').addClass('active');
        $('#btnMobile').removeClass('active');
        $('#previewFrame').removeClass('preview-mobile-view');
    });

    $('#btnMobile').on('click', function () {
        $('#btnMobile').addClass('active');
        $('#btnDesktop').removeClass('active');
        $('#previewFrame').addClass('preview-mobile-view');
    });

    $('#previewFrame').on('load', function () {
        var frameHeight = this.contentWindow.document.body.scrollHeight;
        $(this).height(frameHeight + 40);
    });
});
</script>
@endpush
