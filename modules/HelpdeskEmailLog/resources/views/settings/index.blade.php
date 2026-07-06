@extends('layouts.theme')

@section('title', 'Log de emails — Configuración')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Configuración'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog.css') }}">
@endpush

@section('content')
    @include('core::components.alerts')

    <div class="emaillog-settings">
        <form method="POST" action="{{ route('settings.helpdeskemaillog.update') }}">
            @csrf
            @method('PATCH')

            <div class="evx-card">

                {{-- Cabecera --}}
                <div class="evx-block-head">
                    <div>
                        <span class="t">Configuración del log de emails</span>
                        <span class="s">Controla cómo se almacenan, purgan y muestran los registros de auditoría de emails</span>
                    </div>
                </div>

                {{-- Almacenamiento del contenido --}}
                <div class="evx-section-block">
                    <h6 class="evx-section-title">Almacenamiento del contenido</h6>
                    <p class="evx-section-desc">Define si se guarda el cuerpo del email y su tamaño máximo.</p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="store_body" class="evx-form-label">Guardar cuerpo HTML/texto del email</label>
                            <select class="evx-select @error('store_body') is-invalid @enderror" id="store_body" name="store_body">
                                <option value="1" {{ (string) old('store_body', $storeBody ? '1' : '0') === '1' ? 'selected' : '' }}>Sí, guardar contenido</option>
                                <option value="0" {{ (string) old('store_body', $storeBody ? '1' : '0') === '0' ? 'selected' : '' }}>No, solo metadatos</option>
                            </select>
                            @error('store_body')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">Si se desactiva, solo se almacenan metadatos (asunto, destinatarios, estado). Recomendado desactivar por privacidad o espacio en disco.</span>
                        </div>

                        <div class="evx-form-field">
                            <label for="max_body_bytes" class="evx-form-label">Tamaño máximo del cuerpo (KB)</label>
                            <input type="number" class="evx-input @error('max_body_bytes') is-invalid @enderror"
                                   id="max_body_bytes" name="max_body_bytes"
                                   value="{{ old('max_body_bytes', $maxBodyKb) }}" min="1" max="10240">
                            @error('max_body_bytes')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">Si el cuerpo supera este límite, se trunca. Máximo 10.240 KB.</span>
                        </div>
                    </div>
                </div>

                {{-- Retención y purga --}}
                <div class="evx-section-block">
                    <h6 class="evx-section-title">Retención y purga automática</h6>
                    <p class="evx-section-desc">Antigüedad máxima de los registros y tratamiento de los envíos que quedan en cola.</p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="retention_days" class="evx-form-label">Días de retención</label>
                            <input type="number" class="evx-input @error('retention_days') is-invalid @enderror"
                                   id="retention_days" name="retention_days"
                                   value="{{ old('retention_days', $retentionDays) }}" min="0" max="3650">
                            @error('retention_days')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">Los registros más antiguos se eliminan durante la purga diaria. <strong>0</strong> desactiva la purga.</span>
                        </div>

                        <div class="evx-form-field">
                            <label for="stale_queued_hours" class="evx-form-label">Horas para marcar cola obsoleta</label>
                            <input type="number" class="evx-input @error('stale_queued_hours') is-invalid @enderror"
                                   id="stale_queued_hours" name="stale_queued_hours"
                                   value="{{ old('stale_queued_hours', $staleQueuedHours) }}" min="0" max="8760">
                            @error('stale_queued_hours')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">Registros en estado <em>queued</em> que nunca se confirmaron se marcan como <em>failed</em> tras estas horas. <strong>0</strong> lo desactiva.</span>
                        </div>
                    </div>
                </div>

                {{-- Visualización --}}
                <div class="evx-section-block">
                    <h6 class="evx-section-title">Visualización</h6>
                    <p class="evx-section-desc">Preferencias por defecto del listado de emails.</p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="per_page" class="evx-form-label">Registros por página (por defecto)</label>
                            <select class="evx-select @error('per_page') is-invalid @enderror" id="per_page" name="per_page">
                                @foreach($perPageOptions as $option)
                                    <option value="{{ $option }}" {{ (int) old('per_page', $perPage) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('per_page')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="evx-foot">
                    <button type="submit" class="evx-btn evx-btn-primary evx-btn-inline">
                        Guardar configuración
                    </button>
                </div>

            </div>
        </form>
    </div>
@endsection
