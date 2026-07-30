@extends('helpdesk::settings.layout')

@php
    $title = 'Configuración de tickets';
    $description = 'Administra las opciones generales de tickets del helpdesk';
    $icon = 'fa-ticket-alt';
    $breadcrumb = 'Tickets';
@endphp

@section('backups-content')
<form method="POST" action="{{ route('manager.helpdesk.settings.tickets.general.update') }}">
    @csrf
    @method('PUT')

    <div class="form-section">
        <h5><i class="fas fa-hashtag"></i> Identificación</h5>
        <div class="row">
            <div class="col-md-4">
                <label class="form-label" for="customer_ticketid">Prefijo de ticket</label>
                <input type="text" class="form-control" id="customer_ticketid" name="customer_ticketid"
                    value="{{ old('customer_ticketid', $settings['customer_ticketid'] ?? 'SPT') }}" maxlength="4" required>
                <span class="form-help">Máximo 4 caracteres</span>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="ticket_character">Caracteres en descripción</label>
                <input type="number" class="form-control" id="ticket_character" name="ticket_character"
                    value="{{ old('ticket_character', $settings['ticket_character'] ?? 100) }}" min="10" max="500" required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-ban"></i> Restricciones de creación</h5>
        <div class="row">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="restrict_to_create_ticket"
                        name="restrict_to_create_ticket" value="1"
                        {{ ($settings['restrict_to_create_ticket'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="restrict_to_create_ticket">Limitar creación de tickets</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="maximum_allow_tickets">Máximo de tickets</label>
                <input type="number" class="form-control" id="maximum_allow_tickets" name="maximum_allow_tickets"
                    value="{{ old('maximum_allow_tickets', $settings['maximum_allow_tickets'] ?? 5) }}" min="1" max="100">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="maximum_allow_hours">Período (horas)</label>
                <input type="number" class="form-control" id="maximum_allow_hours" name="maximum_allow_hours"
                    value="{{ old('maximum_allow_hours', $settings['maximum_allow_hours'] ?? 24) }}" min="1" max="168">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-clock"></i> Cierre automático</h5>
        <div class="row">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="auto_close_ticket"
                        name="auto_close_ticket" value="1"
                        {{ ($settings['auto_close_ticket'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_close_ticket">Cerrar tickets automáticamente</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="auto_close_ticket_time">Días de inactividad</label>
                <input type="number" class="form-control" id="auto_close_ticket_time" name="auto_close_ticket_time"
                    value="{{ old('auto_close_ticket_time', $settings['auto_close_ticket_time'] ?? 30) }}" min="1" max="365">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-cogs"></i> Opciones generales</h5>
        <div class="row g-3">
            @foreach(['guest_ticket' => 'Permitir tickets de invitados', 'ticket_rating' => 'Habilitar valoración de tickets', 'cc_email' => 'Copiar por correo (CC)', 'user_file_upload_enable' => 'Subida de archivos para usuarios', 'guest_file_upload_enable' => 'Subida de archivos para invitados'] as $field => $label)
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1"
                        {{ ($settings[$field] ?? false) ? 'checked' : '' }}>
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
