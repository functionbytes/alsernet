@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formTickets" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Configuración de tickets</h5>
                        </div>

                        <div class="row mt-20">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <label  class="control-label col-form-label ">ID de boleto personalizado</label>
                                    <p class="card-subtitle mb-3 mt-0">(Simplemente personalice su ID de boleto. Por ejemplo, SPT-1 es el ID de boleto. Solo puede personalizar las primeras letras del ID de boleto de su elección. Muestra SPT-1 para el usuario registrado y SPTG-1 para el usuario invitado. . De forma predeterminada, la letra "G" representa al usuario invitado.)</p>
                                    <input type="text" class="form-control" id="customer_ticketid"  name="customer_ticketid" value="{{ setting('customer_ticketid') }}">
                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <label  class="control-label col-form-label ">Límite de caracteres del título del boleto <span class="text-red">*</span></label>
                                    <p class="card-subtitle mb-3 mt-0">(El límite de caracteres del título de un ticket se puede fijar aquí. Ingrese el número de caracteres del título del ticket que desee. Y los caracteres del título ahora no pueden exceder ese valor)</p>
                                    <input type="text" class="form-control" id="ticket_character"  name="ticket_character" value="{{setting('ticket_character') }}">
                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Restringir a los clientes la creación de tickets continuamente</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si habilita esta configuración de boleto, los clientes no pueden crear múltiples boletos a la vez. Los clientes estarán restringidos al valor especificado en "Número máximo de boletos permitidos" hasta el período de tiempo indicado "En horas")</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="restrict_to_create_ticket" id="restrict_to_create_ticket"   @if(setting('restrict_to_create_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="maximum_allow_tickets"  name="maximum_allow_tickets" value="{{ setting('maximum_allow_tickets') }}">
                                        </div>
                                        <label for="exampleInputPassword1" class="form-label fw-semibold col-sm-9 col-form-label">Número máximo de entradas permitidas</label>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="maximum_allow_hours"  name="maximum_allow_hours" value="{{ setting('maximum_allow_hours') }}">
                                        </div>
                                        <label for="exampleInputPassword1" class="form-label fw-semibold col-sm-9 col-form-label">En horas</label>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Restringir que el cliente responda al ticket continuamente</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si habilita esta configuración de ticket, los clientes no podrán "Responder" a sus tickets dentro de las horas mencionadas y los tickets en los campos de entrada como se muestra a continuación).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="restrict_to_reply_ticket" id="restrict_to_reply_ticket"   @if(setting('restrict_to_reply_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="maximum_allow_replies"  name="maximum_allow_replies" value="{{ setting('maximum_allow_replies') }}">
                                        </div>
                                        <label for="maximumallowreplies" class="form-label fw-semibold col-sm-9 col-form-label">Máximo de respuestas permitidas</label>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="reply_allow_in_hours"  name="reply_allow_in_hours" value="{{ setting('reply_allow_in_hours') }}">
                                        </div>
                                        <label for="repliesallowhours" class="form-label fw-semibold col-sm-9 col-form-label">Respuestas permitidas en horas</label>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Habilitación del tiempo de respuesta automática del ticket</label>
                                            <p class="card-subtitle mb-3 mt-0">(Esta configuración se utiliza para cambiar el estado del ticket a "Esperando respuesta" cuando un cliente no responde al ticket dentro de las horas mencionadas en el campo de entrada, y también se enviará un correo electrónico al cliente. Si desactiva esta configuración del ticket, entonces no cambiará el estado del ticket).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_responsetime_ticket" id="auto_responsetime_ticket"   @if(setting('auto_responsetime_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="auto_responsetime_ticket_time"  name="auto_responsetime_ticket_time" value="{{ setting('auto_responsetime_ticket_time') }}">
                                        </div>
                                        <label for="autoresponsetickettime" class="form-label fw-semibold col-sm-9 col-form-label">Tiempo de respuesta automática del ticket en horas</label>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Activar ticket de cierre automático</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración de ticket, el ticket inactivo no se cerrará automáticamente. Los usuarios deberán cerrar el ticket manualmente. Si está habilitado, el ticket inactivo se cerrará automáticamente después de completar los días que se mencionan en la entrada campo a continuación.)</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_close_ticket" id="auto_close_ticket"   @if(setting('auto_close_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="auto_close_ticket_time"  name="auto_close_ticket_time" value="{{ setting('auto_close_ticket_time') }}">
                                        </div>
                                        <label for="autoresponsetickettime" class="form-label fw-semibold col-sm-9 col-form-label">Días de cierre automático</label>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Habilitar ticket de reapertura del cliente</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración de boleto, los clientes no podrán "Reabrir" sus boletos. Si está habilitado, los clientes pueden "Reabrir" sus boletos dentro de los días mencionados en el campo de entrada a continuación. Y si está configurado a 0 (cero), entonces los clientes pueden reabrir sus boletos en cualquier momento.)</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="user_reopen_issue" id="user_reopen_issue"   @if(setting('user_reopen_issue')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="user_reopen_time"  name="user_reopen_time" value="{{ setting('user_reopen_time') }}">
                                        </div>
                                        <label for="userreopentime" class="form-label fw-semibold col-sm-9 col-form-label">Reabrir boleto en días</label>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Infracción de cliente/billete</label>
                                            <p class="card-subtitle mb-3 mt-0">(A los clientes que infrinjan las políticas se les puede emitir una multa por infracción. Se mostrará un interruptor de "Infracción del cliente" y luego podrá infringir el perfil del cliente una vez que el número total de infracciones alcance el límite máximo especificado a continuación. A partir de ese momento, los clientes infractores no podrá iniciar sesión en la aplicación).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_overdue_ticket" id="auto_overdue_ticket"   @if(setting('auto_overdue_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="auto_overdue_ticket_time"  name="auto_overdue_ticket_time" value="{{ setting('auto_overdue_ticket_time') }}">
                                        </div>
                                        <label for="userreopentime" class="form-label fw-semibold col-sm-9 col-form-label">Multa máxima permitida por infracción.</label>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Restringir al cliente para editar la respuesta</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si habilita esta configuración de ticket, la última respuesta del ticket no será editable para el cliente).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="restrict_reply_edit" id="restrict_reply_edit"   @if(setting('restrict_reply_edit')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="reply_edit_with_in_time"  name="reply_edit_with_in_time" value="{{ setting('reply_edit_with_in_time') }}">
                                        </div>
                                        <label for="userreopentime" class="form-label fw-semibold col-sm-9 col-form-label">Responder editar dentro del tiempo en minutos</label>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Activar ticket automático vencido</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración de ticket, el estado "vencido" no se reflejará en la tabla de tickets en el panel de administración. Si está habilitado y los usuarios de un panel de administración no responden al cliente dentro de los días mencionados , luego el estado del ticket cambia a "Atrasado").</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_overdue_ticket" id="auto_overdue_ticket"   @if(setting('auto_overdue_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="auto_overdue_ticket_time"  name="auto_overdue_ticket_time" value="{{ setting('auto_overdue_ticket_time') }}">
                                        </div>
                                        <label for="userreopentime" class="form-label fw-semibold col-sm-9 col-form-label">Boleto Auto Vencido En Días</label>
                                    </div>

                                </div>
                            </div>



                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Enviar correo al cliente</label>
                                            <p class="card-subtitle mb-3 mt-0">(si habilita esta configuración de ticket, se enviará un correo electrónico al cliente cuando el ticket esté vencido).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_overdue_customert" id="auto_overdue_customer"   @if(setting('auto_overdue_customer')=='true' ) checked @endif/>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>



                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Eliminación automática de tickets en la papelera</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si habilita esta configuración de tickets, los tickets descartados se eliminarán automáticamente después del tiempo mencionado en el campo de entrada a continuación).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="trashed_ticket_autodelete" id="trashed_ticket_autodelete"   @if(setting('trashed_ticket_autodelete')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="trashed_ticket_delete_time"  name="trashed_ticket_delete_time" value="{{ setting('trashed_ticket_delete_time') }}">
                                        </div>
                                        <label for="userreopentime" class="form-label fw-semibold col-sm-9 col-form-label">Eliminación automática de tickets en la papelera en días</label>
                                    </div>

                                </div>
                            </div>




                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Activar notificaciones de eliminación automática</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración de notificación, la notificación de lectura no se eliminará de ambos paneles, es decir, el panel de cliente y de administración. Si está habilitada, las notificaciones de lectura se eliminarán después de completar los días mencionados en el campo de entrada abajo.)</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_notification_delete_enable" id="auto_notification_delete_enable"   @if(setting('auto_notification_delete_enable')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="auto_notification_delete_days"  name="auto_notification_delete_days" value="{{ setting('auto_notification_delete_days') }}">
                                        </div>
                                        <label for="userreopentime" class="form-label fw-semibold col-sm-9 col-form-label">Notificación de eliminación automática en días</label>
                                    </div>

                                </div>
                            </div>




                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Privacidad del nombre del empleado</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si "habilita" esta configuración, los clientes solo podrán ver el nombre que proporcione en el campo de entrada a continuación. No podrán ver el nombre ni la función de los empleados).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="customer_panel_employee_protect" id="customer_panel_employee_protect"   @if(setting('customer_panel_employee_protect')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="employee_protect_name"  name="employee_protect_name" value="{{ setting('employee_protect_name') }}">
                                        </div>
                                        <label for="userreopentime" class="form-label fw-semibold col-sm-9 col-form-label">Notificación de eliminación automática en días</label>
                                    </div>

                                </div>
                            </div>


                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Habilitar boleto de invitado</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración de ticket, la opción "Ticket de invitado" desaparecerá de la sección de encabezado de la aplicación).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="guest_ticket" id="guest_ticket"   @if(setting('guest_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>


                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Nota creada por correo para el administrador</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si habilita esta configuración de ticket, se enviará un correo electrónico al superadministrador cuando se cree una nota de ticket).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="note_create_mails" id="note_create_mails"   @if(setting('note_create_mails')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>


                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Eliminación de ticket por desactivación del cliente</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si habilita esta configuración de ticket, la opción de eliminar ticket desaparecerá del panel del cliente).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="restict_to_delete_ticket" id="restict_to_delete_ticket"   @if(setting('restict_to_delete_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>



                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Cargas de archivos de clientes para boletos</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración de ticket, la opción "Cargar archivo" desaparecerá de la página de creación de ticket, mientras crea o responde al ticket).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="user_file_upload_enable" id="user_file_upload_enable"   @if(setting('user_file_upload_enable')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>


                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Carga de archivos de usuario invitado en ticket</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si desactiva esta configuración de ticket, la opción "Cargar archivo" desaparecerá de la página "Ticket de invitado" mientras crea o responde el ticket a los usuarios invitados).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="guest_file_upload_enable" id="guest_file_upload_enable"   @if(setting('guest_file_upload_enable')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>



                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Deshabilitar OTP de boleto de invitado</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si habilita esta configuración de boleto, la opción "OTP de boleto de invitado" se deshabilitará).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="guest_ticket_otp" id="guest_ticket_otp"   @if(setting('guest_ticket_otp')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>



                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Cliente Crear Ticket Desactivar</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si habilita esta configuración de ticket, la opción de crear ticket desaparecerá del panel del cliente).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="customer_ticket" id="customer_ticket"   @if(setting('customer_ticket')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>




                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Página de calificación Desactivar</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si "Habilita" esta configuración, la "Página de calificación" no aparecerá para los clientes después de cerrar el ticket).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="ticket_rating" id="ticket_rating"   @if(setting('ticket_rating')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>



                            <div class="col-12 border-top">
                                <div class="mb-4 mt-3">
                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <label  class="control-label col-form-label ">Habilitar correo CC</label>
                                            <p class="card-subtitle mb-3 mt-0">(Si "Habilita" esta configuración de "Correo CC", las opciones del campo de entrada de Correo CC aparecerán en las páginas Crear ticket, Crear ticket de administrador y Ticket de invitado).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="cc_email" id="cc_email"   @if(setting('cc_email')=='true' ) checked @endif/>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>
                    
                </form>
            </div>

        </div>

    </div>

@endsection



@push('scripts')

    <script type="text/javascript">
        Dropzone.autoDiscover = false;

        $(document).ready(function() {



            $("#formTickets").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    customer_ticketid: {
                        required: true,
                        minlength: 1,
                        maxlength: 4,
                    },
                    user_reopen_time: {
                        required: true,
                        number: true,
                        min: 0,
                        max: 365,
                    },
                    auto_close_ticket_time: {
                        required: true,
                        number: true,
                        min: 0,
                        max: 365,
                    },
                    auto_overdue_ticket_time: {
                        required: true,
                        number: true,
                        min: 0,
                        max: 365,
                    },
                    auto_responsetime_ticket_time: {
                        required: true,
                        number: true,
                        min: 0,
                        max: 365,
                    },
                    auto_notification_delete_days: {
                        required: true,
                        number: true,
                        min: 0,
                        max: 365,
                    },
                    ticket_character: {
                        required: true,
                        number: true,
                        min: 0,
                        max: 365,
                    },
                    employee_protect_name: {
                        required: true,
                        minlength: 3,
                        maxlength: 20,
                    },
                },
                messages: {
                    customer_ticketid: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 4 caracter",
                    },
                    user_reopen_time: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        min: "Debe mayor o igual a 0",
                        max: "Debe ser menor o igual a 365",
                    },
                    auto_close_ticket_time: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        min: "Debe mayor o igual a 0",
                        max: "Debe ser menor o igual a 365",
                    },
                    auto_overdue_ticket_time: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        min: "Debe mayor o igual a 0",
                        max: "Debe ser menor o igual a 365",
                    },
                    auto_responsetime_ticket_time: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        min: "Debe mayor o igual a 0",
                        max: "Debe ser menor o igual a 365",
                    },
                    auto_notification_delete_days: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        min: "Debe mayor o igual a 0",
                        max: "Debe ser menor o igual a 365",
                    },
                    ticket_character: {
                        required: "El parametro es necesario.",
                        number: 'Solo se puede ingresar números.',
                        min: "Debe mayor o igual a 0",
                        max: "Debe ser menor o igual a 365",
                    },
                    employee_protect_name: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 20 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formTickets');
                    var formData = new FormData($form[0]);
                    var customer_ticketid = $("#customer_ticketid").val();
                    var user_reopen_time = $("#user_reopen_time").val();
                    var maximum_allow_tickets = $("#maximum_allow_tickets").val();
                    var maximum_allow_hours = $("#maximum_allow_hours").val();
                    var maximum_allow_replies = $("#maximum_allow_replies").val();
                    var reply_allow_in_hours = $("#reply_allow_in_hours").val();
                    var auto_responsetime_ticket_time = $("#auto_responsetime_ticket_time").val();
                    var auto_close_ticket_time = $("#auto_close_ticket_time").val();
                    var auto_overdue_ticket_time = $("#auto_overdue_ticket_time").val();
                    var reply_edit_with_in_time = $("#reply_edit_with_in_time").val();
                    var trashed_ticket_delete_time = $("#trashed_ticket_delete_time").val();
                    var auto_notification_delete_days = $("#auto_notification_delete_days").val();
                    var ticket_character = $("#ticket_character").val();
                    var employee_protect_name = $("#employee_protect_name").val();
                    var restrict_to_create_ticket = $("#restrict_to_create_ticket").is(':checked');
                    var restrict_to_reply_ticket = $("#restrict_to_reply_ticket").is(':checked');
                    var auto_responsetime_ticket = $("#auto_responsetime_ticket").is(':checked');
                    var auto_close_ticket = $("#auto_close_ticket").is(':checked');
                    var user_reopen_issue = $("#user_reopen_issue").is(':checked');
                    var auto_overdue_ticket = $("#auto_overdue_ticket").is(':checked');
                    var restrict_reply_edit = $("#restrict_reply_edit").is(':checked');
                    var trashed_ticket_autodelete = $("#trashed_ticket_autodelete").is(':checked');
                    var auto_overdue_customer = $("#auto_overdue_customer").is(':checked');
                    var auto_notification_delete_enable = $("#auto_notification_delete_enable").is(':checked');
                    var customer_panel_employee_protect = $("#customer_panel_employee_protect").is(':checked');
                    var guest_ticket = $("#guest_ticket").is(':checked');
                    var note_create_mails = $("#note_create_mails").is(':checked');
                    var restict_to_delete_ticket = $("#restict_to_delete_ticket").is(':checked');
                    var user_file_upload_enable = $("#user_file_upload_enable").is(':checked');
                    var guest_file_upload_enable = $("#guest_file_upload_enable").is(':checked');
                    var guest_ticket_otp = $("#guest_ticket_otp").is(':checked');
                    var customer_ticket = $("#customer_ticket").is(':checked');
                    var ticket_rating = $("#ticket_rating").is(':checked');
                    var admin_reply_mail = $("#admin_reply_mail").is(':checked');
                    var cc_email = $("#cc_email").is(':checked');

                    formData.append('customer_ticketid', customer_ticketid);
                    formData.append('user_reopen_time', user_reopen_time);
                    formData.append('maximum_allow_tickets', maximum_allow_tickets);
                    formData.append('maximum_allow_hours', maximum_allow_hours);
                    formData.append('maximum_allow_replies', maximum_allow_replies);
                    formData.append('reply_allow_in_hours', reply_allow_in_hours);
                    formData.append('auto_responsetime_ticket_time', auto_responsetime_ticket_time);
                    formData.append('auto_close_ticket_time', auto_close_ticket_time);
                    formData.append('auto_overdue_ticket_time', auto_overdue_ticket_time);
                    formData.append('reply_edit_with_in_time', reply_edit_with_in_time);
                    formData.append('trashed_ticket_delete_time', trashed_ticket_delete_time);
                    formData.append('auto_notification_delete_days', auto_notification_delete_days);
                    formData.append('ticket_character', ticket_character);
                    formData.append('employee_protect_name', employee_protect_name);
                    formData.append('restrict_to_create_ticket', restrict_to_create_ticket);
                    formData.append('restrict_to_reply_ticket', restrict_to_reply_ticket);
                    formData.append('auto_responsetime_ticket', auto_responsetime_ticket);
                    formData.append('auto_close_ticket', auto_close_ticket);
                    formData.append('user_reopen_issue', user_reopen_issue);
                    formData.append('auto_overdue_ticket', auto_overdue_ticket);
                    formData.append('restrict_reply_edit', restrict_reply_edit);
                    formData.append('trashed_ticket_autodelete', trashed_ticket_autodelete);
                    formData.append('auto_overdue_customer', auto_overdue_customer);
                    formData.append('auto_notification_delete_enable', auto_notification_delete_enable);
                    formData.append('customer_panel_employee_protect', customer_panel_employee_protect);
                    formData.append('trashed_ticket_autodelete', trashed_ticket_autodelete);
                    formData.append('guest_ticket', guest_ticket);
                    formData.append('note_create_mails', note_create_mails);
                    formData.append('restict_to_delete_ticket', restict_to_delete_ticket);
                    formData.append('user_file_upload_enable', user_file_upload_enable);
                    formData.append('guest_file_upload_enable', guest_file_upload_enable);
                    formData.append('guest_ticket_otp', guest_ticket_otp);
                    formData.append('customer_ticket', customer_ticket);
                    formData.append('ticket_rating', ticket_rating);
                    formData.append('admin_reply_mail', admin_reply_mail);
                    formData.append('cc_email', cc_email);


                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.settings.tickets.update') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                message = response.message;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location.href = "{{ route('manager.dashboard') }}";
                                }, 2000);

                            }else{

                                $submitButton.prop('disabled', false);
                                error = response.message;

                                toastr.warning(error, "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                $('.errors').text(error);
                                $('.errors').removeClass('d-none');

                            }

                        }
                    });

                }

            });



        });

    </script>

@endpush





