@extends('layouts.theme')

@section('content')
<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formTemplateForm" action="{{ route('settings.mailing.templates.forms.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf

                {{-- Header --}}
                <div class="card-header border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">
                                <i class="fas fa-wpforms me-2 text-primary"></i>Crear formulario de plantilla
                            </h5>
                            <p class="mb-0 text-muted small">Configure un nuevo formulario para sus plantillas de email.</p>
                        </div>
                        <div>
                            <a href="{{ route('settings.mailing.templates.forms.index') }}" class="btn btn-light-secondary">
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
                    @include('mailing::settings.template-forms._form', ['templateForm' => null])
                </div>

                {{-- Footer with Actions --}}
                <div class="card-footer bg-light border-top p-4">
                    <div class="d-flex justify-content-between gap-2">
                        <a href="{{ route('settings.mailing.templates.forms.index') }}" class="btn btn-light-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-light-warning">
                                <i class="fas fa-redo me-1"></i>Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Crear formulario
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
