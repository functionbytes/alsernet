@extends('layouts.theme')

@section('title', 'Configuración de tickets')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de tickets'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('manager.helpdesk.settings.tickets.general.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configuración de tickets</h5>
                        <small class="text-muted">Administra las opciones generales de tickets del helpdesk</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Identificación --}}
                        <h6 class="fw-semibold mb-1">Identificación</h6>
                        <p class="text-muted small mb-3">Prefijo del número de ticket y longitud de la descripción</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Prefijo de ticket <span class="text-brand">*</span></label>
                                <input type="text" name="customer_ticketid"
                                       class="form-control @error('customer_ticketid') is-invalid @enderror"
                                       value="{{ old('customer_ticketid', $settings['customer_ticketid'] ?? 'SPT') }}"
                                       maxlength="4" required>
                                <small class="form-text text-muted">Máximo 4 caracteres, ej: TCK</small>
                                @error('customer_ticketid')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Máximo de caracteres en descripción <span class="text-brand">*</span></label>
                                <input type="number" name="ticket_character"
                                       class="form-control @error('ticket_character') is-invalid @enderror"
                                       value="{{ old('ticket_character', $settings['ticket_character'] ?? 100) }}"
                                       min="10" max="500" required>
                                @error('ticket_character')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Restricciones de creación --}}
                        <h6 class="fw-semibold mb-1">Restricciones de creación</h6>
                        <p class="text-muted small mb-3">Límite de tickets que un mismo cliente puede crear en un período</p>
                        <div class="row g-3 mb-4">

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'restrict_to_create_ticket',
                                'label' => 'Limitar creación de tickets',
                                'default' => false,
                            ])

                            <div class="col-12 col-md-3">
                                <label class="form-label">Máximo de tickets</label>
                                <input type="number" name="maximum_allow_tickets"
                                       class="form-control @error('maximum_allow_tickets') is-invalid @enderror"
                                       value="{{ old('maximum_allow_tickets', $settings['maximum_allow_tickets'] ?? 5) }}"
                                       min="1" max="100">
                                @error('maximum_allow_tickets')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Período (horas)</label>
                                <input type="number" name="maximum_allow_hours"
                                       class="form-control @error('maximum_allow_hours') is-invalid @enderror"
                                       value="{{ old('maximum_allow_hours', $settings['maximum_allow_hours'] ?? 24) }}"
                                       min="1" max="168">
                                @error('maximum_allow_hours')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Restricciones de respuesta --}}
                        <h6 class="fw-semibold mb-1">Restricciones de respuesta</h6>
                        <p class="text-muted small mb-3">Límite de respuestas por ticket y ventana para editarlas</p>
                        <div class="row g-3 mb-4">

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'restrict_to_reply_ticket',
                                'label' => 'Limitar respuestas',
                                'default' => false,
                            ])

                            <div class="col-12 col-md-3">
                                <label class="form-label">Máximo de respuestas</label>
                                <input type="number" name="maximum_allow_replies"
                                       class="form-control @error('maximum_allow_replies') is-invalid @enderror"
                                       value="{{ old('maximum_allow_replies', $settings['maximum_allow_replies'] ?? 10) }}"
                                       min="1" max="100">
                                @error('maximum_allow_replies')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Período (horas)</label>
                                <input type="number" name="reply_allow_in_hours"
                                       class="form-control @error('reply_allow_in_hours') is-invalid @enderror"
                                       value="{{ old('reply_allow_in_hours', $settings['reply_allow_in_hours'] ?? 1) }}"
                                       min="1" max="24">
                                @error('reply_allow_in_hours')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'restrict_reply_edit',
                                'label' => 'Limitar edición de respuestas',
                                'default' => false,
                            ])

                            <div class="col-12 col-md-3">
                                <label class="form-label">Minutos para editar</label>
                                <input type="number" name="reply_edit_with_in_time"
                                       class="form-control @error('reply_edit_with_in_time') is-invalid @enderror"
                                       value="{{ old('reply_edit_with_in_time', $settings['reply_edit_with_in_time'] ?? 15) }}"
                                       min="1" max="1440">
                                @error('reply_edit_with_in_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Al responder --}}
                        <h6 class="fw-semibold mb-1">Al responder</h6>
                        <p class="text-muted small mb-3">Qué le pasa al ticket cuando un agente envía una respuesta al cliente. Las notas internas no disparan ninguna de las dos cosas.</p>
                        <div class="row g-3 mb-4">

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'assign_on_reply',
                                'label' => 'Asignarme el ticket al responder si no tiene dueño',
                                'default' => true,
                            ])

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'status_on_reply',
                                'label' => 'Cambiar el estado al enviar una respuesta',
                                'default' => true,
                            ])

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado al responder</label>
                                <select name="status_on_reply_slug"
                                        class="form-select select2 @error('status_on_reply_slug') is-invalid @enderror">
                                    @foreach($replyStatuses as $replyStatus)
                                        <option value="{{ $replyStatus['slug'] }}"
                                            {{ old('status_on_reply_slug', $settings['status_on_reply_slug'] ?? 'resolved') === $replyStatus['slug'] ? 'selected' : '' }}>
                                            {{ $replyStatus['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status_on_reply_slug')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Tiempos y automatización --}}
                        <h6 class="fw-semibold mb-1">Tiempos y automatización</h6>
                        <p class="text-muted small mb-3">Alertas de respuesta, cierre, reapertura y vencimiento automáticos</p>
                        <div class="row g-3 mb-4">

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'auto_responsetime_ticket',
                                'label' => 'Alertar tiempo objetivo de primera respuesta',
                                'default' => false,
                            ])

                            <div class="col-12 col-md-6">
                                <label class="form-label">Horas objetivo</label>
                                <input type="number" name="auto_responsetime_ticket_time"
                                       class="form-control @error('auto_responsetime_ticket_time') is-invalid @enderror"
                                       value="{{ old('auto_responsetime_ticket_time', $settings['auto_responsetime_ticket_time'] ?? 48) }}"
                                       min="1" max="365">
                                @error('auto_responsetime_ticket_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'auto_close_ticket',
                                'label' => 'Cerrar tickets automáticamente',
                                'default' => true,
                            ])

                            <div class="col-12 col-md-6">
                                <label class="form-label">Días de inactividad</label>
                                <input type="number" name="auto_close_ticket_time"
                                       class="form-control @error('auto_close_ticket_time') is-invalid @enderror"
                                       value="{{ old('auto_close_ticket_time', $settings['auto_close_ticket_time'] ?? 30) }}"
                                       min="1" max="365">
                                @error('auto_close_ticket_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'user_reopen_issue',
                                'label' => 'Permitir que el cliente reabra tickets cerrados',
                                'default' => true,
                            ])

                            <div class="col-12 col-md-6">
                                <label class="form-label">Días tras el cierre en que puede reabrir</label>
                                <input type="number" name="user_reopen_time"
                                       class="form-control @error('user_reopen_time') is-invalid @enderror"
                                       value="{{ old('user_reopen_time', $settings['user_reopen_time'] ?? 7) }}"
                                       min="0" max="365">
                                @error('user_reopen_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'auto_overdue_ticket',
                                'label' => 'Marcar tickets como vencidos',
                                'default' => false,
                            ])

                            <div class="col-12 col-md-6">
                                <label class="form-label">Días para considerar vencido</label>
                                <input type="number" name="auto_overdue_ticket_time"
                                       class="form-control @error('auto_overdue_ticket_time') is-invalid @enderror"
                                       value="{{ old('auto_overdue_ticket_time', $settings['auto_overdue_ticket_time'] ?? 5) }}"
                                       min="1" max="100">
                                @error('auto_overdue_ticket_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'auto_overdue_customer',
                                'label' => 'Notificar al cliente cuando su ticket vence',
                                'default' => false,
                            ])

                        </div>

                        {{-- Notificaciones a agentes --}}
                        <h6 class="fw-semibold mb-1">Notificaciones a agentes</h6>
                        <p class="text-muted small mb-3">
                            Aviso interno cuando entra un ticket nuevo. Se envía solo a los usuarios del grupo
                            asignado al ticket (o, si no tiene grupo propio, al grupo por defecto de su categoría) —
                            revisa que <a href="{{ route('manager.helpdesk.settings.ticket-categories.index') }}">categorías</a>
                            y <a href="{{ route('manager.helpdesk.settings.ticket-groups.index') }}">grupos</a> estén bien
                            configurados antes de activarlo.
                        </p>
                        <div class="row g-3 mb-4">

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'notify_agents_new_ticket',
                                'label' => 'Notificar por correo a los agentes del grupo al crearse un ticket',
                                'default' => false,
                            ])

                        </div>

                        {{-- Papelera y notificaciones --}}
                        <h6 class="fw-semibold mb-1">Papelera y notificaciones</h6>
                        <p class="text-muted small mb-3">Limpieza automática de tickets eliminados y notificaciones antiguas</p>
                        <div class="row g-3 mb-4">

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'trashed_ticket_autodelete',
                                'label' => 'Eliminar definitivamente tickets en papelera',
                                'default' => true,
                            ])

                            <div class="col-12 col-md-6">
                                <label class="form-label">Días en papelera antes de borrar</label>
                                <input type="number" name="trashed_ticket_delete_time"
                                       class="form-control @error('trashed_ticket_delete_time') is-invalid @enderror"
                                       value="{{ old('trashed_ticket_delete_time', $settings['trashed_ticket_delete_time'] ?? 30) }}"
                                       min="1" max="365">
                                @error('trashed_ticket_delete_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'auto_notification_delete_enable',
                                'label' => 'Eliminar notificaciones antiguas',
                                'default' => true,
                            ])

                            <div class="col-12 col-md-6">
                                <label class="form-label">Días de antigüedad</label>
                                <input type="number" name="auto_notification_delete_days"
                                       class="form-control @error('auto_notification_delete_days') is-invalid @enderror"
                                       value="{{ old('auto_notification_delete_days', $settings['auto_notification_delete_days'] ?? 15) }}"
                                       min="1" max="365">
                                @error('auto_notification_delete_days')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Panel del cliente --}}
                        <h6 class="fw-semibold mb-1">Panel del cliente</h6>
                        <p class="text-muted small mb-3">Cómo interactúan los clientes con sus tickets desde el portal</p>
                        <div class="row g-3 mb-4">

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'customer_panel_employee_protect',
                                'label' => 'Ocultar el nombre real del agente al cliente',
                                'default' => false,
                            ])

                            <div class="col-12">
                                <label class="form-label">Nombre a mostrar en su lugar</label>
                                <input type="text" name="employee_protect_name"
                                       class="form-control @error('employee_protect_name') is-invalid @enderror"
                                       value="{{ old('employee_protect_name', $settings['employee_protect_name'] ?? 'Equipo de Soporte') }}"
                                       minlength="3" maxlength="50">
                                @error('employee_protect_name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'guest_ticket',
                                'label' => 'Permitir tickets de invitados (sin cuenta)',
                                'default' => true,
                            ])

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'guest_ticket_otp',
                                'label' => 'Verificar invitados por código enviado al correo',
                                'default' => false,
                            ])

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'customer_ticket',
                                'label' => 'Requerir cuenta de cliente para ver el historial',
                                'default' => false,
                            ])

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'note_create_mails',
                                'label' => 'Notificar por correo al añadir una nota interna',
                                'default' => false,
                            ])

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'restict_to_delete_ticket',
                                'label' => 'Restringir la eliminación de tickets',
                                'default' => false,
                            ])

                        </div>

                        {{-- Archivos y valoración --}}
                        <h6 class="fw-semibold mb-1">Archivos y valoración</h6>
                        <p class="text-muted small mb-3">Adjuntos permitidos y encuesta de satisfacción al cerrar un ticket</p>
                        <div class="row g-3">

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'user_file_upload_enable',
                                'label' => 'Subida de archivos para usuarios registrados',
                                'default' => true,
                            ])

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'guest_file_upload_enable',
                                'label' => 'Subida de archivos para invitados',
                                'default' => true,
                            ])

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'ticket_rating',
                                'label' => 'Habilitar valoración de tickets (CSAT)',
                                'default' => false,
                            ])

                            @include('helpdesktickets::managers.settings.general._toggle', [
                                'field' => 'cc_email',
                                'label' => 'Copiar por correo (CC) en las notificaciones',
                                'default' => false,
                            ])

                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre esta configuración</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Estas opciones controlan el comportamiento general de los tickets: límites de creación y
                        respuesta, cierre/reapertura automáticos, papelera y lo que ve el cliente desde su portal.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas prácticas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Un prefijo corto y reconocible facilita identificar tickets en correos y reportes</li>
                        <li class="mb-2">Limitar la creación evita abuso de un mismo cliente sin bloquear casos legítimos</li>
                        <li class="mb-2">El cierre automático mantiene la bandeja al día sin perder el historial</li>
                        <li class="mb-0">Revisa la papelera antes de acortar los días de retención</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Canales de correo</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Cada buzón conectado es un canal: sus correos entrantes generan tickets automáticamente.</p>
                    <a href="{{ route('manager.helpdesk.settings.email-channels.index') }}" class="btn btn-secondary w-100">
                        Configurar canales de correo
                    </a>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script src="{{ asset('modules/helpdesktickets/js/select2-init.js') }}"></script>
@endpush
