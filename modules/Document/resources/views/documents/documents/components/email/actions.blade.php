{{-- Componente: Tarjeta de Acciones de Email --}}
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Acciones de email</h5>
        <p class="small mb-0 text-muted">Comunicación con el cliente</p>
    </div>
    <div class="card-body">
        @if(auth()->user()->canDocument('send-initial-request'))
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Solicitud inicial
                </label>
                <p class="text-muted small mb-2">
                    Enviar solicitud inicial de documentos al cliente
                </p>
                <button type="button" class="btn btn-outline-primary w-100 send-notification-btn"
                        data-uid="{{ $document->uid }}">
                    Enviar solicitud
                </button>
            </div>
        @endif

        @if(auth()->user()->canDocument('send-missing-docs'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Documentos faltantes
                </label>
                <p class="text-muted small mb-2">
                    Notificar al cliente sobre documentos específicos faltantes
                </p>
                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#missingDocsModal">
                    Enviar notificación
                </button>
            </div>
        @endif

        @if(auth()->user()->canDocument('send-reminders'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Recordatorio
                </label>
                <p class="text-muted small mb-2">
                    Enviar recordatorio de documentos pendientes al cliente
                </p>
                <button type="button" class="btn btn-outline-primary w-100 send-reminder-btn"
                        data-uid="{{ $document->uid }}">
                    Enviar recordatorio
                </button>
            </div>
        @endif

        @if(auth()->user()->canDocument('send-upload-confirmation'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Confirmación de carga
                </label>
                <p class="text-muted small mb-2">
                    Confirmar al cliente que sus documentos han sido recibidos
                </p>
                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#uploadConfirmationModal">
                    Enviar confirmación
                </button>
            </div>
        @endif

        @if(auth()->user()->canDocument('send-approval'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Notificación de aprobación
                </label>
                <p class="text-muted small mb-2">
                    Notificar al cliente que sus documentos fueron aprobados
                </p>
                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#approvalModal">
                    Enviar aprobación
                </button>
            </div>
        @endif

        @if(auth()->user()->canDocument('send-rejection'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Notificación de rechazo
                </label>
                <p class="text-muted small mb-2">
                    Notificar al cliente que sus documentos fueron rechazados
                </p>
                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#rejectionModal">
                    Enviar rechazo
                </button>
            </div>
        @endif

        @if(auth()->user()->canDocument('send-custom-email'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                   Correo personalizado
                </label>
                <p class="text-muted small mb-2">
                    Enviar email con contenido personalizado al cliente
                </p>
                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#customEmailModal">
                    Redactar correo
                </button>
            </div>
        @endif
    </div>
</div>

{{-- MODALES DE EMAIL (Condicionales por permisos) --}}
@if(auth()->user()->canDocument('send-missing-docs'))
    @include('documents::documents.documents.components.email.modals.missing')
@endif

@if(auth()->user()->canDocument('send-initial-request'))
    @include('documents::documents.documents.components.email.modals.request')
@endif

@if(auth()->user()->canDocument('send-reminders'))
    @include('documents::documents.documents.components.email.modals.reminder')
@endif

@if(auth()->user()->canDocument('send-upload-confirmation'))
    @include('documents::documents.documents.components.email.modals.confirmation')
@endif

@if(auth()->user()->canDocument('send-approval'))
    @include('documents::documents.documents.components.email.modals.approval')
@endif

@if(auth()->user()->canDocument('send-rejection'))
    @include('documents::documents.documents.components.email.modals.rejection', [
        'requiredDocuments' => $requiredDocuments ?? []
    ])
@endif

@if(auth()->user()->canDocument('send-custom-email'))
    @include('documents::documents.documents.components.email.modals.custom')
@endif
