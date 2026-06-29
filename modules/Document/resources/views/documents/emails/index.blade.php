@extends('layouts.theme')

@section('content')


    @include('core::components.card', ['title' => 'Emails'])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Emails enviados</h5>
                        <p class="small mb-0 text-muted">Historial de correos electrónicos enviados al cliente de este documento</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('documents.manage', $document->uid) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al documento
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert bg-light border mb-0" role="alert">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-file-alt fs-4 me-3 mt-1 text-primary"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Documento #{{ $document->order_id }}</h6>
                                    <p class="mb-0 small text-muted">
                                        <strong>Cliente:</strong> {{ $document->customer_firstname }} {{ $document->customer_lastname }}
                                        <span class="mx-2">|</span>
                                        <strong>Email:</strong> {{ $document->customer_email ?? 'No disponible' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            @if($document->status)
                                <span class="badge bg-primary" >
                                    {{ $document->status->label }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Emails Table --}}
            @if ($mails->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th >Asunto</th>
                                <th >Estado</th>
                                <th >Enviado</th>
                                <th >Tipo</th>
                                <th >Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mails as $mail)
                                <tr>

                                    <td>
                                        <div>
                                            <p class="d-block text-truncate" style="max-width: 350px;" title="{{ $mail->subject }}">
                                                {{ strlen($mail->subject) > 40 ? substr($mail->subject, 0, 40) . '...' : $mail->subject }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        @if($mail->status === 'sent')
                                            <span class="badge bg-success-subtle text-success">
                                               Enviado
                                            </span>
                                        @elseif($mail->status === 'failed')
                                            <span class="badge bg-danger-subtle text-white">
                                                Fallido
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">
                                                En cola
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($mail->sender)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <span class="small fw-semibold text-muted">
                                                        {{ strtoupper(substr($mail->sender->firstname ?? 'S', 0, 1)) }}
                                                    </span>
                                                </div>
                                                <span class="small">{{ $mail->sender->firstname ?? 'Usuario' }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted small">
                                                Sistema
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $mail->email_type_label }}
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $mail->sent_at ? $mail->sent_at->format('d/m/Y') : $mail->created_at->format('d/m/Y') }}
                                            {{ $mail->sent_at ? $mail->sent_at->format('H:i') : $mail->created_at->format('H:i') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('documents.emails.preview', $mail->uid) }}" target="_blank">
                                                        Vista previa
                                                    </a>
                                                </li>
                                                @if($mail->status === 'failed' && $mail->error_message)
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#errorModal{{ $mail->id }}">
                                                       Ver error
                                                    </button>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Error Modal --}}
                                @if($mail->status === 'failed' && $mail->error_message)
                                <div class="modal fade" id="errorModal{{ $mail->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>Error de envío
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-2"><strong>Fecha:</strong> {{ $mail->created_at->format('d/m/Y H:i:s') }}</p>
                                                <p class="mb-2"><strong>Tipo:</strong> {{ $mail->email_type_label }}</p>
                                                <hr>
                                                <p class="mb-0"><strong>Mensaje de error:</strong></p>
                                                <pre class="bg-light p-3 rounded mt-2 small">{{ $mail->error_message }}</pre>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-envelope-open-text fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay emails enviados</h5>
                    <p class="text-muted mb-4">
                        Aún no se han enviado correos electrónicos para este documento.
                    </p>
                    <a href="{{ route('documents.manage', $document->uid) }}" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Enviar notificación
                    </a>
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if($mails->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $mails->firstItem() }} - {{ $mails->lastItem() }} de {{ $mails->total() }} emails
                        </div>
                        <div>
                            {{ $mails->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

@endsection
