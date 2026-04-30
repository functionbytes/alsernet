@extends('helpdesk::settings.layout')

@php
    $title = 'Configuración de LiveChat';
    $description = 'Personaliza el widget de chat en vivo y sus comportamientos';
    $icon = 'fa-comments';
    $breadcrumb = 'LiveChat';
@endphp

@section('backups-content')
<form method="POST" action="{{ route('settings.helpdesk.livechat.update') }}">
    @csrf
    @method('PUT')

    <div class="form-section">
        <h5><i class="fas fa-palette"></i> Estilo del widget</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="primary_color">Color primario</label>
                <input type="color" class="form-control form-control-color w-100" id="primary_color" name="primary_color"
                    value="{{ old('primary_color', $backups['primary_color'] ?? '#b10100') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="secondary_color">Color secundario</label>
                <input type="color" class="form-control form-control-color w-100" id="secondary_color" name="secondary_color"
                    value="{{ old('secondary_color', $backups['secondary_color'] ?? '#ffffff') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="header_title">Título del chat</label>
                <input type="text" class="form-control" id="header_title" name="header_title"
                    value="{{ old('header_title', $backups['header_title'] ?? 'Chat de Soporte') }}" maxlength="100" required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-comment-dots"></i> Mensajes</h5>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="welcome_message">Mensaje de bienvenida</label>
                <input type="text" class="form-control" id="welcome_message" name="welcome_message"
                    value="{{ old('welcome_message', $backups['welcome_message'] ?? '') }}" maxlength="200" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="input_placeholder">Placeholder del campo de mensaje</label>
                <input type="text" class="form-control" id="input_placeholder" name="input_placeholder"
                    value="{{ old('input_placeholder', $backups['input_placeholder'] ?? '') }}" maxlength="100">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-map-marker-alt"></i> Posición del widget</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="position">Posición</label>
                <select class="form-select" id="position" name="position">
                    @foreach($positions ?? [] as $value => $label)
                        <option value="{{ $value }}" {{ ($backups['position'] ?? 'bottom-right') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="side_spacing">Espacio lateral (px)</label>
                <input type="number" class="form-control" id="side_spacing" name="side_spacing"
                    value="{{ old('side_spacing', $backups['side_spacing'] ?? 16) }}" min="0" max="100">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bottom_spacing">Espacio inferior (px)</label>
                <input type="number" class="form-control" id="bottom_spacing" name="bottom_spacing"
                    value="{{ old('bottom_spacing', $backups['bottom_spacing'] ?? 16) }}" min="0" max="100">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-toggle-on"></i> Opciones</h5>
        <div class="row g-3">
            @foreach(['show_avatars' => 'Mostrar avatares', 'show_help_center' => 'Mostrar centro de ayuda', 'typing_indicator' => 'Indicador de escritura', 'sound_notifications' => 'Notificaciones de sonido', 'hide_launcher' => 'Ocultar lanzador'] as $field => $label)
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1"
                        {{ ($backups[$field] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary btn-save">
            <i class="fas fa-save me-2"></i> Guardar configuración
        </button>
    </div>
</form>
@endsection
