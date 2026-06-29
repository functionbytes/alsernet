@extends('layouts.theme')

@section('title', 'Log de emails — Configuración')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Configuración'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form method="POST" action="{{ route('settings.helpdeskemaillog.update') }}">
        @csrf
        @method('PATCH')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">Configuración del log de emails</h5>
                <p class="small mb-0 text-muted">Controla cómo se almacenan, purgan y muestran los registros de auditoría de emails</p>
            </div>

            {{-- Almacenamiento del cuerpo --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Almacenamiento del contenido</h6>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="store_body" id="store_body"
                               value="1" {{ $storeBody ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="store_body">
                            Guardar cuerpo HTML/texto del email
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Si está desactivado, solo se almacenan los metadatos (asunto, destinatarios, estado).
                        Recomendado desactivar si hay preocupaciones de privacidad o espacio en disco.
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <label for="max_body_bytes" class="form-label fw-semibold">
                            Tamaño máximo del cuerpo (KB)
                        </label>
                        <input type="number" class="form-control @error('max_body_bytes') is-invalid @enderror"
                               id="max_body_bytes" name="max_body_bytes"
                               value="{{ old('max_body_bytes', $maxBodyKb) }}"
                               min="1" max="10240">
                        @error('max_body_bytes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Si el cuerpo supera este límite, se trunca. Máximo 10.240 KB.</small>
                    </div>
                </div>
            </div>

            {{-- Retención y purga --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Retención y purga automática</h6>

                <div class="row g-4">
                    <div class="col-md-4">
                        <label for="retention_days" class="form-label fw-semibold">
                            Días de retención
                        </label>
                        <input type="number" class="form-control @error('retention_days') is-invalid @enderror"
                               id="retention_days" name="retention_days"
                               value="{{ old('retention_days', $retentionDays) }}"
                               min="0" max="3650">
                        @error('retention_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Registros más antiguos se eliminan durante la purga diaria.
                            <strong>0</strong> desactiva la purga.
                        </small>
                    </div>

                    <div class="col-md-4">
                        <label for="stale_queued_hours" class="form-label fw-semibold">
                            Horas para marcar cola obsoleta
                        </label>
                        <input type="number" class="form-control @error('stale_queued_hours') is-invalid @enderror"
                               id="stale_queued_hours" name="stale_queued_hours"
                               value="{{ old('stale_queued_hours', $staleQueuedHours) }}"
                               min="0" max="8760">
                        @error('stale_queued_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Registros en estado <em>queued</em> que nunca se confirmaron se marcan como
                            <em>failed</em> tras estas horas. <strong>0</strong> desactiva esta comprobación.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Visualización --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Visualización</h6>

                <div class="col-md-4">
                    <label for="per_page" class="form-label fw-semibold">
                        Registros por página (por defecto)
                    </label>
                    <select class="form-select @error('per_page') is-invalid @enderror"
                            id="per_page" name="per_page">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}" {{ (int) old('per_page', $perPage) === $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    @error('per_page')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div class="card-footer p-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar configuración
                </button>
            </div>

        </div>
    </form>
@endsection
