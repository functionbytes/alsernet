@extends('layouts.theme')

@section('title', 'Preview email - ' . $email->subject)

@section('content')

    <div class="row g-3">
        {{-- Main Preview --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold">Vista previa</h6>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Device preview">
                            <button type="button" class="btn btn-outline-primary active" id="btnDesktopView">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnMobileView">
                                <i class="fas fa-mobile-screen"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="preview-wrapper">
                        <div class="preview-email-container preview-desktop-view" id="previewContainer">
                            @if($email->body_html)
                                {!! clean_html($email->body_html) !!}
                            @else
                                <div class="p-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        El contenido del email no esta disponible o no se guardo en la base de datos.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Este es el contenido exacto del email que fue enviado al destinatario.
                    </small>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-lg-4">
            {{-- Email Details --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom bg-warning-subtle">
                    <h6 class="mb-0 fw-bold">Detalle del email</h6>
                    <small class="text-muted">Informacion del correo enviado</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">ASUNTO DEL EMAIL</h6>
                            <p class="mb-0">{{ $email->subject }}</p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">DESTINATARIO</h6>
                            <p class="mb-0">{{ $email->recipient_email }}</p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">TIPO DE EMAIL</h6>
                            <p class="mb-0">
                                <span class="badge bg-info-subtle text-info">{{ $email->email_type_label }}</span>
                            </p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">ESTADO</h6>
                            <p class="mb-0">
                                <span class="badge bg-{{ $email->status_color }}-subtle text-{{ $email->status_color }}">
                                    {{ $email->status_label }}
                                </span>
                            </p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">ENVIADO POR</h6>
                            <p class="mb-0">
                                @if($email->sender)
                                    {{ $email->sender->firstname }} {{ $email->sender->lastname }}
                                @else
                                    <span class="text-muted">Sistema (automatico)</span>
                                @endif
                            </p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">FECHA DE ENVIO</h6>
                            <p class="mb-0 small">
                                {{ ($email->sent_at ?? $email->created_at)->format('d/m/Y H:i:s') }}
                                <span class="text-muted">({{ ($email->sent_at ?? $email->created_at)->diffForHumans() }})</span>
                            </p>
                        </div>

                        @if($email->error_message)
                            <div class="col-12">
                                <h6 class="text-danger fw-semibold small mb-1">ERROR</h6>
                                <div class="alert alert-danger mb-0 small">{{ $email->error_message }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Acciones rapidas</h6>
                    <small class="text-muted">Opciones disponibles</small>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if(in_array($email->status, ['sent', 'failed']))
                            <button type="button" class="btn btn-primary" id="resendEmailBtn">
                                Reenviar email
                            </button>
                        @endif
                        <button type="button" class="btn btn-info" id="btnPrintEmail">
                            Imprimir
                        </button>
                        <a href="{{ route('forms.inbox.emails.index', $submission) }}" class="btn btn-secondary">
                            Volver a emails
                        </a>
                        <a href="{{ route('forms.inbox.manage', $submission) }}" class="btn btn-outline-primary">
                            Gestionar
                        </a>
                    </div>
                </div>
            </div>

            {{-- Submission Info --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Submission relacionada</h6>
                    <small class="text-muted">Informacion del formulario</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">ID</h6>
                            <p class="mb-0 fw-bold">
                                <a href="{{ route('forms.inbox.show', $submission) }}" class="text-decoration-none">
                                    #{{ $submission->id }}
                                </a>
                            </p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">FORMULARIO</h6>
                            <p class="mb-0">{{ $submission->form->name ?? '—' }}</p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">EMAIL SOLICITANTE</h6>
                            <p class="mb-0">{{ $submission->getEmailValue() ?? 'No disponible' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('styles')
<style>
    .variable-badge {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        padding: 8px 10px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .preview-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        width: 100%;
        padding: 30px;
        background: #f8f9fa;
        min-height: 500px;
    }

    .preview-email-container {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 100%;
        width: 100%;
        background: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    .preview-email-container img { max-width: 100%; height: auto; }
    .preview-email-container table { width: 100%; }

    .preview-email-container.preview-desktop-view { max-width: 100% !important; width: 100% !important; }
    .preview-email-container.preview-mobile-view { max-width: 375px !important; width: 375px !important; margin: 0 auto !important; }

    .btn-group .btn.active {
        background-color: #b10100 !important;
        border-color: #b10100 !important;
        color: white !important;
    }

    @media print {
        .col-lg-4, .card-header, .card-footer, .btn-group, button, .btn { display: none !important; }
        body { background: white !important; }
        .preview-wrapper { background: white !important; padding: 0 !important; }
        .preview-email-container { box-shadow: none !important; border-radius: 0 !important; }
    }

    @media (max-width: 991px) {
        .preview-wrapper { padding: 15px !important; min-height: auto !important; }
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    $('#btnDesktopView').on('click', function () {
        $('#previewContainer').removeClass('preview-mobile-view').addClass('preview-desktop-view');
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
    });

    $('#btnMobileView').on('click', function () {
        $('#previewContainer').removeClass('preview-desktop-view').addClass('preview-mobile-view');
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
    });

    $('#btnPrintEmail').on('click', function () {
        window.print();
    });

    $('#resendEmailBtn').on('click', function () {
        if (!confirm('¿Esta seguro de reenviar este email?')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Reenviando...');

        $.ajax({
            url: '{{ route('forms.inbox.emails.resend', [$submission, $email]) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message || 'Email reenviado correctamente', 'Exito');
                    setTimeout(function () {
                        window.location.href = '{{ route('forms.inbox.emails.index', $submission) }}';
                    }, 1500);
                } else {
                    toastr.error(response.message || 'No se pudo reenviar el email', 'Error');
                }
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al reenviar el email', 'Error');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-redo me-1"></i>Reenviar email');
            }
        });
    });
});
</script>
@endpush

@endsection
