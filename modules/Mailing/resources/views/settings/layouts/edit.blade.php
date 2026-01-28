@extends('layouts.theme')

@section('content')
<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formLayout" action="{{ route('settings.mailing.templates.layouts.update', $layout->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')

                {{-- Header --}}
                <div class="card-header border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">
                                <i class="fas fa-edit me-2 text-primary"></i>Editar layout de email
                            </h5>
                            <p class="mb-0 text-muted small">Modifique la configuración del layout.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light-info" data-bs-toggle="modal" data-bs-target="#previewModal">
                                <i class="fas fa-eye me-1"></i>Vista previa
                            </button>
                            <a href="{{ route('settings.mailing.templates.layouts.index') }}" class="btn btn-light-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Alerts --}}
                <div class="card-body">
                    @include('core::components.alerts')
                </div>

                {{-- Form Content --}}
                <div class="card-body p-4">
                    @include('mailing::settings.layouts._form', ['layout' => $layout])
                </div>

                {{-- Footer with Actions --}}
                <div class="card-footer bg-light border-top p-4">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <a href="{{ route('settings.mailing.templates.layouts.index') }}" class="btn btn-light-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <form action="{{ route('settings.mailing.templates.layouts.destroy', $layout->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea eliminar este layout?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-light-danger">
                                    <i class="fas fa-trash me-1"></i>Eliminar
                                </button>
                            </form>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Guardar cambios
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye me-2 text-info"></i>Vista previa: {{ $layout->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 600px; overflow-y: auto; background-color: {{ $layout->background_color ?? '#f0f0f0' }};">
                <div style="max-width: {{ $layout->max_width ?? 600 }}px; margin: 0 auto;">
                    {!! str_replace('{{content}}', '<div style="padding: 20px; background-color: white; border: 2px dashed #ccc; text-align: center; color: #999;">Contenido principal aquí</div>', $layout->html) !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const form = document.getElementById('formLayout');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
</script>
@endpush
