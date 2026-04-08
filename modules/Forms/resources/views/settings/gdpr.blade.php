@extends('layouts.theme')

@section('title', 'GDPR: ' . $form->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="d-flex align-items-center gap-2 mb-4">
                <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                    <span class="text-muted">{{ $form->slug }}</span>
                </div>
            </div>

            @include('forms::settings.partials.tabs', ['active' => 'gdpr'])

            <div class="row">
                <div class="col-lg-8">
                    <form action="{{ route('settings.forms.settings.gdpr.update', $form) }}" method="POST" id="gdprForm">
                        @csrf
                        @method('PATCH')

                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Retención de datos</h6>
                                <div class="row g-3">

                                    <div class="col-md-6">
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
                        </div>

                        {{-- Anonimización --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Anonimización manual</h6>
                                <p class="text-muted mb-3">
                                    Esta acción anonimizará permanentemente todos los submissions anteriores al período de retención configurado.
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
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="anonymize_old"
                                           name="anonymize_old" value="1">
                                    <label class="form-check-label" for="anonymize_old">
                                        Anonimizar submissions anteriores al período de retención al guardar
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar cambios
                            </button>
                        </div>

                    </form>
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
