@extends('layouts.theme')

@section('title', 'Editar reporte programado')

@section('page_header')
    @include('core::components.card', [
    'title' => 'Editar reporte programado',
    'back'  => route('settings.analytics.schedules.index'),
    ])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row">

        {{-- Formulario --}}
        <div class="col-lg-8">
            <div class="card w-100">
                <form method="POST" action="{{ route('settings.analytics.schedules.update', $schedule) }}">
                    @csrf
                    @method('PUT')

                    <div class="card-header">
                        <h5 class="mb-2">Editar reporte programado</h5>
                        <p class="card-subtitle mt-1">
                            Modifica la configuración del reporte programado.
                        </p>
                    </div>

                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Ej: Reporte semanal web"
                                   value="{{ old('name', $schedule->name) }}" required maxlength="100">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Frecuencia <span class="text-danger">*</span>
                                </label>
                                <select name="frequency" id="frequency" class="form-select select2 @error('frequency') is-invalid @enderror" required>
                                    <option value="daily"   {{ old('frequency', $schedule->frequency) === 'daily'   ? 'selected' : '' }}>Diario</option>
                                    <option value="weekly"  {{ old('frequency', $schedule->frequency) === 'weekly'  ? 'selected' : '' }}>Semanal</option>
                                    <option value="monthly" {{ old('frequency', $schedule->frequency) === 'monthly' ? 'selected' : '' }}>Mensual</option>
                                </select>
                                @error('frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Formato <span class="text-danger">*</span>
                                </label>
                                <select name="format" id="format" class="form-select select2 @error('format') is-invalid @enderror" required>
                                    <option value="pdf"   {{ old('format', $schedule->format) === 'pdf'   ? 'selected' : '' }}>PDF</option>
                                    <option value="excel" {{ old('format', $schedule->format) === 'excel' ? 'selected' : '' }}>Excel</option>
                                    <option value="csv"   {{ old('format', $schedule->format) === 'csv'   ? 'selected' : '' }}>CSV</option>
                                </select>
                                @error('format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Email de destino <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="admin@ejemplo.com"
                                   value="{{ old('email', $schedule->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">El reporte se enviará a esta dirección según la frecuencia configurada</small>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('settings.analytics.schedules.index') }}" class="btn btn-secondary w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">

            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Frecuencias disponibles</h6>
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><strong>Diario:</strong> Se envía cada día al ejecutarse el cron de analítica</li>
                        <li class="mb-2"><strong>Semanal:</strong> Se envía una vez por semana</li>
                        <li><strong>Mensual:</strong> Se envía el primer día de cada mes</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Formatos de exportación</h6>
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><strong>PDF:</strong> Ideal para compartir y archivar</li>
                        <li class="mb-2"><strong>Excel:</strong> Para análisis y manipulación de datos</li>
                        <li><strong>CSV:</strong> Compatible con cualquier herramienta de datos</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('css')
<link href="{{ asset('core/select2/css/select2.min.css') }}" rel="stylesheet" />
@endpush

@push('scripts')
<script src="{{ asset('core/select2/js/select2.min.js') }}"></script>
<script>
$(document).ready(function () {
    $('#frequency').select2({ width: '100%' });
    $('#format').select2({ width: '100%' });
});
</script>
@endpush
