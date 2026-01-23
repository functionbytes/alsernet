@extends('layouts.theme')

@section('content')


    @include('core::components.card', ['title' => 'Documento #' . $document->order_id, 'url' => route('documents.manage', $document->uid)])

    {{-- Preview Content --}}
    <div class="row g-3">
        {{-- Main Preview --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold">
                            Vista previa
                        </h6>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Device preview">
                            <button type="button" class="btn btn-outline-primary active" id="btnDesktopView" data-width="100%">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnMobileView" data-width="375px">
                                <i class="fas fa-mobile-screen"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="preview-wrapper">
                        <div class="preview-email-container preview-desktop-view" id="previewContainer">
                            {!! $mail->body_html !!}
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Este es el contenido exacto del email que fue enviado al cliente.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-lg-4">
            {{-- Email Details Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom bg-warning-subtle">
                    <h6 class="mb-0 fw-bold">
                        Detalle del email
                    </h6>
                    <small class="text-muted">Información del correo enviado</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">ASUNTO DEL EMAIL</h6>
                                    <p class="mb-0 fw-bold">{{ $mail->subject }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">DESTINATARIO</h6>
                                    <p class="mb-0">
                                        <code class="text-primary">{{ $mail->recipient_email }}</code>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">TIPO DE EMAIL</h6>
                                    <p class="mb-0">
                                        @php
                                            $typeConfig = [
                                                'request' => ['color' => 'primary', 'bg' => 'primary-subtle'],
                                                'reminder' => ['color' => 'warning', 'bg' => 'warning-subtle'],
                                                'upload' => ['color' => 'info', 'bg' => 'info-subtle'],
                                                'approval' => ['color' => 'success', 'bg' => 'success-subtle'],
                                                'rejection' => ['color' => 'danger', 'bg' => 'danger-subtle'],
                                                'missing' => ['color' => 'warning', 'bg' => 'warning-subtle'],
                                                'custom' => ['color' => 'secondary', 'bg' => 'secondary-subtle'],
                                            ];
                                            $config = $typeConfig[$mail->email_type] ?? ['color' => 'secondary', 'bg' => 'secondary-subtle'];
                                        @endphp
                                        <span class="badge bg-{{ $config['bg'] }} text-{{ $config['color'] }}">
                                            {{ $mail->email_type_label }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">ESTADO</h6>
                                    <p class="mb-0">
                                        @if($mail->status === 'sent')
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fas fa-check me-1"></i>Enviado
                                            </span>
                                        @elseif($mail->status === 'failed')
                                            <span class="badge bg-danger-subtle text-white">
                                                <i class="fas fa-times me-1"></i>Fallido
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="fas fa-clock me-1"></i>En cola
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">ENVIADO POR</h6>
                                    <p class="mb-0">
                                        @if($mail->sender)
                                            {{ $mail->sender->firstname }} {{ $mail->sender->lastname }}
                                        @else
                                            <span class="text-muted">Sistema (automático)</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">FECHA DE ENVÍO</h6>
                                    <p class="mb-0 small">
                                        {{ $mail->sent_at ? $mail->sent_at->format('d/m/Y H:i:s') : $mail->created_at->format('d/m/Y H:i:s') }}
                                        <br>
                                        <span class="text-muted">{{ $mail->sent_at ? $mail->sent_at->diffForHumans() : $mail->created_at->diffForHumans() }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if($mail->template)
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">PLANTILLA USADA</h6>
                                    <p class="mb-0">
                                        <code class="text-primary">{{ $mail->template->name ?? $mail->template->key }}</code>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($mail->error_message)
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div class="w-100">
                                    <h6 class="text-danger fw-semibold small mb-1">ERROR</h6>
                                    <div class="alert alert-danger mb-0 small">
                                        {{ $mail->error_message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        Acciones rápidas
                    </h6>
                    <small class="text-muted">Opciones disponibles</small>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-info" id="btnPrintEmail">
                            Imprimir
                        </button>
                        <a href="{{ route('api.documents.emails', $document->uid) }}" class="btn btn-secondary">
                            Volver a emails
                        </a>
                        <a href="{{ route('documents.manage', $document->uid) }}" class="btn btn-primary">
                            Gestionar documento
                        </a>
                    </div>
                </div>
            </div>

            {{-- Document Info Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        Documento relacionado
                    </h6>
                    <small class="text-muted">Información del documento</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">ORDEN ID</h6>
                                    <p class="mb-0 fw-bold">#{{ $document->order_id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">CLIENTE</h6>
                                    <p class="mb-0">{{ $document->customer_firstname }} {{ $document->customer_lastname }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div>
                                    <h6 class="text-muted fw-semibold small mb-1">ESTADO DOCUMENTO</h6>
                                    <p class="mb-0">
                                        @if($document->status)
                                            <span class="badge" style="background-color: {{ $document->status->color ?? '#6c757d' }}">
                                                {{ $document->status->label }}
                                            </span>
                                        @else
                                            <span class="text-muted">Sin estado</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($mail->metadata && count($mail->metadata) > 0)
            {{-- Metadata Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        Datos adicionales
                    </h6>
                    <small class="text-muted">Metadata del envío</small>
                </div>
                <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                    <div class="row g-2">
                        @foreach($mail->metadata as $key => $value)
                            <div class="col-12">
                                <div class="variable-badge">
                                    <i class="fas fa-tag"></i>
                                    <div>
                                        <small class="text-muted d-block">{{ ucfirst(str_replace('_', ' ', $key)) }}</small>
                                        <code>{{ is_array($value) ? json_encode($value) : Str::limit($value, 100) }}</code>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

@push('styles')
<style>
    /* Variable Badges */
    .variable-badge {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        padding: 8px 10px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .variable-badge:hover {
        border-color: #90bb13;
        background: #f6faf0;
    }

    .variable-badge i {
        color: #90bb13;
        font-size: 12px;
        margin-top: 4px;
    }

    .variable-badge code {
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        font-size: 11px;
        font-weight: 600;
        color: #1f2937;
        background: transparent;
        padding: 0;
        word-break: break-all;
    }

    /* Preview Wrapper */
    .preview-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        width: 100%;
        padding: 30px;
        background: #f8f9fa;
        min-height: 500px;
    }

    /* Preview Container Transitions */
    .preview-email-container {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 100%;
        width: 100%;
        flex-shrink: 0;
        background: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    /* Desktop View - Full width */
    .preview-email-container.preview-desktop-view {
        max-width: 100% !important;
        width: 100% !important;
    }

    /* Mobile View - 375px width */
    .preview-email-container.preview-mobile-view {
        max-width: 375px !important;
        width: 375px !important;
        margin: 0 auto !important;
    }

    /* Button Group Active State */
    .btn-group .btn.active {
        background-color: #90bb13 !important;
        border-color: #90bb13 !important;
        color: white !important;
    }

    @media print {
        .col-lg-4,
        .card-header,
        .card-footer,
        .btn-group,
        button,
        .btn {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .preview-wrapper {
            background: white !important;
            padding: 0 !important;
        }

        .preview-email-container {
            box-shadow: none !important;
            border-radius: 0 !important;
        }
    }

    @media (max-width: 991px) {
        .preview-wrapper {
            padding: 15px !important;
            min-height: auto !important;
        }
    }

    @media (max-width: 576px) {
        .preview-wrapper {
            padding: 10px !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Desktop/Mobile toggle
    $('#btnDesktopView').on('click', function() {
        $('#previewContainer')
            .removeClass('preview-mobile-view')
            .addClass('preview-desktop-view');
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
    });

    $('#btnMobileView').on('click', function() {
        $('#previewContainer')
            .removeClass('preview-desktop-view')
            .addClass('preview-mobile-view');
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
    });

    // Print button
    $('#btnPrintEmail').on('click', function() {
        window.print();
    });
});
</script>
@endpush

@endsection
