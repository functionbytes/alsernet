@extends('layouts.theme')

@section('content')
<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formTemplateForm" action="{{ route('settings.mailing.templates.forms.update', $templateForm->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')

                {{-- Header --}}
                <div class="card-header border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">
                                <i class="fas fa-edit me-2 text-primary"></i>Editar formulario de plantilla
                            </h5>
                            <p class="mb-0 text-muted small">Modifique la configuración del formulario.</p>
                        </div>
                        <a href="{{ route('settings.mailing.templates.forms.index') }}" class="btn btn-light-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>

                {{-- Alerts --}}
                <div class="card-body">
                    @include('core::components.alerts')
                </div>

                {{-- Form Content --}}
                <div class="card-body p-4">
                    @include('mailing::settings.template-forms._form', ['templateForm' => $templateForm])
                </div>

                {{-- Footer with Actions --}}
                <div class="card-footer bg-light border-top p-4">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <a href="{{ route('settings.mailing.templates.forms.index') }}" class="btn btn-light-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <form action="{{ route('settings.mailing.templates.forms.destroy', $templateForm->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea eliminar este formulario?');">
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

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const form = document.getElementById('formTemplateForm');
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
