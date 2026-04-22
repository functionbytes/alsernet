@extends('layouts.theme')

@section('title', 'Configuración de Tickets - Helpdesk')

@section('content')
<div class="container-fluid">
    <!-- Header with Save Button -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">Configuración General de Tickets</h4>
                    <p class="text-muted mb-0">Administra el comportamiento, límites y automatizaciones del sistema de tickets</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="submit" form="ticketsForm" class="btn btn-primary" id="saveBtn" disabled>
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('manager.helpdesk.settings.tickets.update') }}" id="ticketsForm">
        @csrf
        @method('PUT')

        <!-- Accordion Container -->
        <div class="accordion" id="ticketsAccordion">

            {{-- 1. CONFIGURACIÓN BÁSICA --}}
            <div class="accordion-item mb-3 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicConfig">
                        <i class="fas fa-cog text-primary me-2"></i>
                        <strong>Configuración Básica</strong>
                        <span class="badge bg-primary-subtle text-primary ms-2">2 opciones</span>
                    </button>
                </h2>
                <div id="basicConfig" class="accordion-collapse collapse show" data-bs-parent="#ticketsAccordion">
                    <div class="accordion-body">
                        <!-- ID de boleto personalizado -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-hashtag text-muted me-1"></i> Prefijo de ID de Ticket
                            </label>
                            <p class="text-muted mb-2">Personaliza el prefijo del ID de ticket (ej: SPT-1, TKT-1)</p>
                            <input type="text" class="form-control" name="customer_ticketid" value="{{ $settings['customer_ticketid'] }}" placeholder="SPT">
                        </div>

                        <!-- Límite de caracteres del título -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-ruler text-muted me-1"></i> Límite de Caracteres del Título <span class="text-danger">*</span>
                            </label>
                            <p class="text-muted mb-2">Número máximo de caracteres permitidos en el título del ticket</p>
                            <input type="number" class="form-control" name="ticket_character" value="{{ $settings['ticket_character'] }}" placeholder="255" min="50" max="500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. LÍMITES Y RESTRICCIONES --}}
            <div class="accordion-item mb-3 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#limits">
                        <i class="fas fa-ban text-warning me-2"></i>
                        <strong>Límites y Restricciones</strong>
                        <span class="badge bg-warning-subtle text-warning ms-2">3 opciones</span>
                    </button>
                </h2>
                <div id="limits" class="accordion-collapse collapse" data-bs-parent="#ticketsAccordion">
                    <div class="accordion-body">
                        <!-- Restringir creación de tickets -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-ticket-alt text-warning me-1"></i> Limitar Creación Continua de Tickets
                                    </label>
                                    <p class="text-muted mb-0">Evita que clientes creen múltiples tickets en poco tiempo</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="restrict_to_create_ticket" id="restrict_to_create_ticket" @checked($settings['restrict_to_create_ticket'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Máximo de tickets permitidos</label>
                                    <input type="number" class="form-control" name="maximum_allow_tickets" value="{{ $settings['maximum_allow_tickets'] }}" min="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Período de tiempo (horas)</label>
                                    <input type="number" class="form-control" name="maximum_allow_hours" value="{{ $settings['maximum_allow_hours'] }}" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Restringir respuestas -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-reply text-warning me-1"></i> Limitar Respuestas Continuas
                                    </label>
                                    <p class="text-muted mb-0">Evita que clientes respondan múltiples veces seguidas</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="restrict_to_reply_ticket" id="restrict_to_reply_ticket" @checked($settings['restrict_to_reply_ticket'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Máximo de respuestas permitidas</label>
                                    <input type="number" class="form-control" name="maximum_allow_replies" value="{{ $settings['maximum_allow_replies'] }}" min="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Período de tiempo (horas)</label>
                                    <input type="number" class="form-control" name="reply_allow_in_hours" value="{{ $settings['reply_allow_in_hours'] }}" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Restringir edición de respuestas -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-edit text-warning me-1"></i> Restringir Edición de Respuestas
                                    </label>
                                    <p class="text-muted mb-0">Limita el tiempo disponible para editar respuestas</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="restrict_reply_edit" id="restrict_reply_edit" @checked($settings['restrict_reply_edit'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Tiempo límite para editar (minutos)</label>
                                    <input type="number" class="form-control" name="reply_edit_with_in_time" value="{{ $settings['reply_edit_with_in_time'] }}" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. AUTOMATIZACIONES --}}
            <div class="accordion-item mb-3 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#automation">
                        <i class="fas fa-robot text-success me-2"></i>
                        <strong>Automatizaciones</strong>
                        <span class="badge bg-success-subtle text-success ms-2">5 opciones</span>
                    </button>
                </h2>
                <div id="automation" class="accordion-collapse collapse" data-bs-parent="#ticketsAccordion">
                    <div class="accordion-body">
                        <!-- Tiempo de respuesta automática -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="far fa-clock text-success me-1"></i> Cambio Automático a "Esperando Respuesta"
                                    </label>
                                    <p class="text-muted mb-0">Cambia el estado cuando el cliente no responde en el tiempo especificado</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_responsetime_ticket" id="auto_responsetime_ticket" @checked($settings['auto_responsetime_ticket'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Tiempo de espera (horas)</label>
                                    <input type="number" class="form-control" name="auto_responsetime_ticket_time" value="{{ $settings['auto_responsetime_ticket_time'] }}" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Cierre automático -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-times-circle text-success me-1"></i> Cierre Automático de Tickets Inactivos
                                    </label>
                                    <p class="text-muted mb-0">Cierra automáticamente tickets sin actividad reciente</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_close_ticket" id="auto_close_ticket" @checked($settings['auto_close_ticket'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Días de inactividad para cierre</label>
                                    <input type="number" class="form-control" name="auto_close_ticket_time" value="{{ $settings['auto_close_ticket_time'] }}" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Reapertura -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-undo text-success me-1"></i> Permitir Reapertura de Tickets
                                    </label>
                                    <p class="text-muted mb-0">Define cuántos días después del cierre se puede reabrir un ticket (0 = ilimitado)</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="user_reopen_issue" id="user_reopen_issue" @checked($settings['user_reopen_issue'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Días permitidos para reabrir (0 = sin límite)</label>
                                    <input type="number" class="form-control" name="user_reopen_time" value="{{ $settings['user_reopen_time'] }}" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Eliminación automática papelera -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-trash text-success me-1"></i> Eliminación Automática de Papelera
                                    </label>
                                    <p class="text-muted mb-0">Elimina permanentemente tickets en papelera después del tiempo especificado</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="trashed_ticket_autodelete" id="trashed_ticket_autodelete" @checked($settings['trashed_ticket_autodelete'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Días en papelera antes de eliminar</label>
                                    <input type="number" class="form-control" name="trashed_ticket_delete_time" value="{{ $settings['trashed_ticket_delete_time'] }}" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Notificaciones -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="far fa-bell text-success me-1"></i> Limpieza Automática de Notificaciones
                                    </label>
                                    <p class="text-muted mb-0">Elimina notificaciones leídas después del tiempo especificado</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_notification_delete_enable" id="auto_notification_delete_enable" @checked($settings['auto_notification_delete_enable'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Días antes de eliminar notificaciones</label>
                                    <input type="number" class="form-control" name="auto_notification_delete_days" value="{{ $settings['auto_notification_delete_days'] }}" min="1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. NOTIFICACIONES Y ALERTAS --}}
            <div class="accordion-item mb-3 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#notifications">
                        <i class="far fa-envelope text-info me-2"></i>
                        <strong>Notificaciones y Alertas</strong>
                        <span class="badge bg-info-subtle text-info ms-2">3 opciones</span>
                    </button>
                </h2>
                <div id="notifications" class="accordion-collapse collapse" data-bs-parent="#ticketsAccordion">
                    <div class="accordion-body">
                        <!-- Infracciones -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-exclamation-triangle text-info me-1"></i> Sistema de Infracciones
                                    </label>
                                    <p class="text-muted mb-0">Registra infracciones cuando clientes violan políticas</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_overdue_ticket" id="auto_overdue_ticket" @checked($settings['auto_overdue_ticket'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Máximo de infracciones permitidas</label>
                                    <input type="number" class="form-control" name="auto_overdue_ticket_time" value="{{ $settings['auto_overdue_ticket_time'] }}" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Correo al cliente -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="far fa-paper-plane text-info me-1"></i> Notificar Tickets Vencidos
                                    </label>
                                    <p class="text-muted mb-0">Envía email al cliente cuando un ticket está vencido</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_overdue_customer" id="auto_overdue_customer" @checked($settings['auto_overdue_customer'])/>
                                </div>
                            </div>
                        </div>

                        <!-- Nota por correo -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-sticky-note text-info me-1"></i> Notificar Creación de Notas
                                    </label>
                                    <p class="text-muted mb-0">Envía email al superadministrador cuando se crea una nota en un ticket</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="note_create_mails" id="note_create_mails" @checked($settings['note_create_mails'])/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. PRIVACIDAD Y SEGURIDAD --}}
            <div class="accordion-item mb-3 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#privacy">
                        <i class="fas fa-shield-alt text-danger me-2"></i>
                        <strong>Privacidad y Seguridad</strong>
                        <span class="badge bg-danger-subtle text-danger ms-2">2 opciones</span>
                    </button>
                </h2>
                <div id="privacy" class="accordion-collapse collapse" data-bs-parent="#ticketsAccordion">
                    <div class="accordion-body">
                        <!-- Privacidad del empleado -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-user-secret text-danger me-1"></i> Ocultar Nombres de Empleados
                                    </label>
                                    <p class="text-muted mb-0">Muestra un nombre genérico en lugar del nombre real del empleado</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="customer_panel_employee_protect" id="customer_panel_employee_protect" @checked($settings['customer_panel_employee_protect'])/>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small">Nombre genérico a mostrar</label>
                                    <input type="text" class="form-control" name="employee_protect_name" value="{{ $settings['employee_protect_name'] }}" placeholder="Soporte">
                                </div>
                            </div>
                        </div>

                        <!-- OTP invitado -->
                        <div class="setting-item mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1">
                                        <i class="fas fa-key text-danger me-1"></i> Deshabilitar OTP para Invitados
                                    </label>
                                    <p class="text-muted mb-0">Los tickets de invitados no requerirán verificación OTP</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="guest_ticket_otp" id="guest_ticket_otp" @checked($settings['guest_ticket_otp'])/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. OPCIONES DE CLIENTES --}}
            <div class="accordion-item mb-3 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#customer">
                        <i class="fas fa-users text-secondary me-2"></i>
                        <strong>Permisos de Clientes</strong>
                        <span class="badge bg-secondary-subtle text-secondary ms-2">7 opciones</span>
                    </button>
                </h2>
                <div id="customer" class="accordion-collapse collapse" data-bs-parent="#ticketsAccordion">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <!-- Boleto de invitado -->
                            <div class="col-md-6">
                                <div class="setting-item p-3 bg-light rounded h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold mb-1">
                                                <i class="fas fa-user-tag text-secondary me-1"></i> Tickets de Invitados
                                            </label>
                                            <p class="text-muted mb-0">Permite crear tickets sin cuenta</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="guest_ticket" id="guest_ticket" @checked($settings['guest_ticket'])/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cliente crear ticket -->
                            <div class="col-md-6">
                                <div class="setting-item p-3 bg-light rounded h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold mb-1">
                                                <i class="fas fa-plus-circle text-secondary me-1"></i> Clientes Crear Tickets
                                            </label>
                                            <p class="text-muted mb-0">Deshabilita creación de tickets</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="customer_ticket" id="customer_ticket" @checked($settings['customer_ticket'])/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Eliminación de ticket -->
                            <div class="col-md-6">
                                <div class="setting-item p-3 bg-light rounded h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold mb-1">
                                                <i class="fas fa-trash-alt text-secondary me-1"></i> Clientes Eliminar Tickets
                                            </label>
                                            <p class="text-muted mb-0">Oculta opción de eliminar tickets</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="restict_to_delete_ticket" id="restict_to_delete_ticket" @checked($settings['restict_to_delete_ticket'])/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Calificación -->
                            <div class="col-md-6">
                                <div class="setting-item p-3 bg-light rounded h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold mb-1">
                                                <i class="fas fa-star text-secondary me-1"></i> Sistema de Calificaciones
                                            </label>
                                            <p class="text-muted mb-0">Deshabilita calificaciones al cerrar</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="ticket_rating" id="ticket_rating" @checked($settings['ticket_rating'])/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Carga de archivos clientes -->
                            <div class="col-md-6">
                                <div class="setting-item p-3 bg-light rounded h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold mb-1">
                                                <i class="fas fa-paperclip text-secondary me-1"></i> Adjuntos de Clientes
                                            </label>
                                            <p class="text-muted mb-0">Permite subir archivos en tickets</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="user_file_upload_enable" id="user_file_upload_enable" @checked($settings['user_file_upload_enable'])/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Carga archivos invitado -->
                            <div class="col-md-6">
                                <div class="setting-item p-3 bg-light rounded h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold mb-1">
                                                <i class="fas fa-file-upload text-secondary me-1"></i> Adjuntos de Invitados
                                            </label>
                                            <p class="text-muted mb-0">Permite adjuntos en tickets guest</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="guest_file_upload_enable" id="guest_file_upload_enable" @checked($settings['guest_file_upload_enable'])/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CC Email -->
                            <div class="col-md-6">
                                <div class="setting-item p-3 bg-light rounded h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold mb-1">
                                                <i class="far fa-envelope text-secondary me-1"></i> Campo CC Email
                                            </label>
                                            <p class="text-muted mb-0">Muestra campo para copias de email</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="cc_email" id="cc_email" @checked($settings['cc_email'])/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 7. SLA Y ESCALACIÓN --}}
            <div class="accordion-item mb-3 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#slaEscalation">
                        <i class="fas fa-layer-group text-primary me-2"></i>
                        <strong>SLA y escalación</strong>
                        <span class="badge bg-primary-subtle text-primary ms-2">5 opciones</span>
                    </button>
                </h2>
                <div id="slaEscalation" class="accordion-collapse collapse" data-bs-parent="#ticketsAccordion">
                    <div class="accordion-body">
                        <!-- SLA Timezone -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-globe text-muted me-1"></i> SLA Timezone
                            </label>
                            <p class="text-muted mb-2">Timezone used for SLA business hours calculations.</p>
                            <select name="sla_timezone" class="form-select">
                                @foreach(timezone_identifiers_list() as $tz)
                                    <option value="{{ $tz }}" {{ (config('helpdesk.sla.business_hours.timezone') === $tz) ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Priority Escalation -->
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">Priority escalation</h6></div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="escalation_enabled" id="escalation_enabled"
                                           {{ config('helpdesk.escalation.enabled', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="escalation_enabled">Enable automatic priority escalation</label>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small">Low → Normal (hours)</label>
                                        <input type="number" name="escalation_low_hours" class="form-control form-control-sm"
                                               value="{{ config('helpdesk.escalation.thresholds.low', 48) }}" min="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Normal → High (hours)</label>
                                        <input type="number" name="escalation_normal_hours" class="form-control form-control-sm"
                                               value="{{ config('helpdesk.escalation.thresholds.normal', 24) }}" min="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">High → Urgent (hours)</label>
                                        <input type="number" name="escalation_high_hours" class="form-control form-control-sm"
                                               value="{{ config('helpdesk.escalation.thresholds.high', 12) }}" min="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<style>
.setting-item {
    transition: all 0.2s ease;
}

.setting-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
}

.form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}
</style>

<script>
$(document).ready(function() {
    const form = $('#ticketsForm');
    const saveBtn = $('#saveBtn');
    let originalFormData = form.serialize();

    // Form Dirty Detection
    function checkFormDirty() {
        const currentFormData = form.serialize();
        const isDirty = originalFormData !== currentFormData;
        saveBtn.prop('disabled', !isDirty);
    }

    // Monitor all form inputs for changes
    form.on('change input', 'input, select, textarea', function() {
        checkFormDirty();
    });

    // Form submission
    form.on('submit', function(e) {
        e.preventDefault();
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: form.attr('action'),
            type: 'PUT',
            data: form.serialize(),
            success: function(response) {
                if(response.success) {
                    toastr.success(response.message, 'Éxito');
                    originalFormData = form.serialize();
                    saveBtn.html('<i class="fas fa-save"></i> Guardar Cambios');
                    checkFormDirty();
                }
            },
            error: function(xhr) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
                const error = xhr.responseJSON?.message || 'Error al guardar la configuración';
                toastr.error(error, 'Error');
            }
        });
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Configuración actualizada');
        setTimeout(function() {
            originalFormData = form.serialize();
            checkFormDirty();
        }, 100);
    @endif
});
</script>
@endpush
