@extends('layouts.theme')

@section('title', 'GDPR: ' . $form->name)

@section('content')

    @include('core::components.card', ['title' => 'GDPR'])

    <div class="widget-content searchable-container list">

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                            <small class="text-muted">{{ $form->slug }}</small>
                        </div>
                        @if ($form->is_active)
                            <span class="badge bg-light-success text-success">Activo</span>
                        @else
                            <span class="badge bg-light-danger text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-desktop me-1"></i> Preview
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-inbox me-1"></i> Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-9">
                <form action="{{ route('settings.forms.settings.gdpr.update', $form) }}" method="POST" id="gdprForm">
                    @csrf
                    @method('PATCH')

                    <div class="card">

                        <div class="card-header p-4 border-bottom">
                            <h5 class="mb-1 fw-bold">GDPR y privacidad</h5>
                            <p class="small mb-0 text-muted">Configuración de retención de datos y cumplimiento de privacidad</p>
                        </div>

                        {{-- Retención de datos --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Retención de datos</h6>
                            <p class="text-muted mb-3">Define cuánto tiempo se conservan las respuestas del formulario.</p>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="retention_days" class="form-label">Días de retención de datos</label>
                                    <div class="input-group">
                                        <input type="number" id="retention_days" name="retention_days" class="form-control"
                                               value="{{ old('retention_days', $form->retention_days) }}"
                                               min="1" max="3650"
                                               placeholder="Sin límite">
                                        <span class="input-group-text">días</span>
                                    </div>
                                    <div class="form-text">
                                        Dejar vacío para no eliminar submissions automáticamente.
                                        Los submissions más antiguos se pueden anonimizar manualmente.
                                    </div>
                                    @error('retention_days')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Anonimización manual --}}
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Anonimización manual</h6>
                            <p class="text-muted mb-3">
                                Anonimiza permanentemente los submissions anteriores al período de retención configurado.
                                Los datos de los usuarios (nombre, email, IP) serán reemplazados por valores anónimos.
                            </p>

                            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                                <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></i>
                                <div>
                                    <strong>Acción irreversible.</strong>
                                    Una vez anonimizados, los datos de los usuarios no pueden recuperarse.
                                    Asegúrate de tener un período de retención configurado antes de continuar.
                                </div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="anonymize_old"
                                       name="anonymize_old" value="1">
                                <label class="form-check-label" for="anonymize_old">
                                    Anonimizar submissions anteriores al período de retención al guardar
                                </label>
                            </div>
                        </div>

                        <div class="card-footer p-4">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar cambios
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            <div class="col-lg-3">
                @include('forms::settings.partials.tabs', ['active' => 'gdpr'])

                <div class="card mt-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Sobre el GDPR</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted ps-3 mb-0 small">
                            <li class="mb-1">Configura un período de retención para eliminar datos antiguos automáticamente.</li>
                            <li class="mb-1">La anonimización reemplaza datos personales con valores genéricos.</li>
                            <li>Agrega un campo de consentimiento en los campos del formulario.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    $('#gdprForm').on('submit', function (e) {
        if ($('#anonymize_old').is(':checked') && !confirm('¿Estás seguro de que deseas anonimizar los submissions? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
</script>
@endpush
