@extends('layouts.theme')

@section('title', 'Vista previa del correo bloqueado')

@section('page_header')
    @include('core::components.card', ['title' => 'Correo bloqueado', 'url' => route('manager.helpdesk.settings.ticket-blacklist.history')])
@endsection

@section('content')

    <div class="row g-3">
        {{-- Main Preview --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold">
                            Vista previa
                        </h6>
                        @if($hit->body_html)
                            <div class="btn-group btn-group-sm" role="group" aria-label="Device preview">
                                <button type="button" class="btn btn-outline-primary active" id="btnDesktopView" data-width="100%">
                                    <i class="fas fa-desktop"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="btnMobileView" data-width="375px">
                                    <i class="fas fa-mobile-screen"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="preview-wrapper">
                        @if($hit->body_html)
                            <div class="preview-email-container preview-desktop-view" id="previewContainer">
                                {!! clean_html($hit->body_html) !!}
                            </div>
                        @elseif($hit->body_text)
                            <div class="preview-email-container preview-desktop-view preview-plain-text">
                                {{ $hit->body_text }}
                            </div>
                        @else
                            <div class="preview-email-container preview-desktop-view text-center text-muted py-5">
                                Este correo no guardó contenido de cuerpo (solo remitente y asunto).
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Este es el contenido exacto del correo entrante que la lista negra descartó — nunca llegó a crear cliente ni ticket.
                    </small>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-lg-4">
            {{-- Hit Details Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom bg-warning-subtle">
                    <h6 class="mb-0 fw-bold">
                        Detalle del bloqueo
                    </h6>
                    <small class="text-muted">Información del correo descartado</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">ASUNTO DEL CORREO</h6>
                            <p class="mb-0 fw-bold">{{ $hit->subject ?: 'Sin asunto' }}</p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">REMITENTE BLOQUEADO</h6>
                            <p class="mb-0">
                                <code class="text-primary">{{ $hit->from_email }}</code>
                            </p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">REGLA QUE LO BLOQUEÓ</h6>
                            <p class="mb-0">
                                @if($hit->blacklist)
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        {{ $hit->blacklist->type === 'domain' ? 'Dominio' : 'Email' }}
                                    </span>
                                    <code class="text-primary">{{ $hit->blacklist->value }}</code>
                                @else
                                    <span class="text-muted">Regla eliminada</span>
                                @endif
                            </p>
                        </div>

                        @if($hit->blacklist?->reason)
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">MOTIVO DE LA REGLA</h6>
                            <p class="mb-0 small">{{ $hit->blacklist->reason }}</p>
                        </div>
                        @endif

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">FECHA DEL BLOQUEO</h6>
                            <p class="mb-0 small">
                                {{ $hit->created_at->format('d/m/Y H:i:s') }}
                                <br>
                                <span class="text-muted">{{ $hit->created_at->diffForHumans() }}</span>
                            </p>
                        </div>
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
                        <a href="{{ route('manager.helpdesk.settings.ticket-blacklist.history') }}" class="btn btn-secondary">
                            Volver al historial
                        </a>
                        <a href="{{ route('manager.helpdesk.settings.ticket-blacklist.index') }}" class="btn btn-primary">
                            Ver lista negra
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .preview-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        width: 100%;
        padding: 30px;
        background: #f5f6f8;
        min-height: 500px;
    }

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

    .preview-email-container.preview-desktop-view {
        max-width: 100% !important;
        width: 100% !important;
    }

    .preview-email-container.preview-mobile-view {
        max-width: 375px !important;
        width: 375px !important;
        margin: 0 auto !important;
    }

    .preview-plain-text {
        padding: 24px;
        white-space: pre-wrap;
        word-break: break-word;
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        font-size: 0.9rem;
        color: #333;
    }

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
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    $('#btnDesktopView').on('click', function() {
        $('#previewContainer').removeClass('preview-mobile-view').addClass('preview-desktop-view');
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
    });

    $('#btnMobileView').on('click', function() {
        $('#previewContainer').removeClass('preview-desktop-view').addClass('preview-mobile-view');
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
    });

    $('#btnPrintEmail').on('click', function() {
        window.print();
    });
});
</script>
@endpush
