@extends('layouts.theme')

@section('content')
<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formLayout" action="{{ route('settings.mailing.templates.layouts.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf

                {{-- Header --}}
                <div class="card-header border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">
                                <i class="fas fa-layer-group me-2 text-primary"></i>Crear layout de email
                            </h5>
                            <p class="mb-0 text-muted small">Configure un nuevo layout reutilizable para sus plantillas de email.</p>
                        </div>
                        <div>
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
                    @include('mailing::settings.layouts._form', ['layout' => null])
                </div>

                {{-- Footer with Actions --}}
                <div class="card-footer bg-light border-top p-4">
                    <div class="d-flex justify-content-between gap-2">
                        <a href="{{ route('settings.mailing.templates.layouts.index') }}" class="btn btn-light-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-light-warning">
                                <i class="fas fa-redo me-1"></i>Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Crear layout
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
