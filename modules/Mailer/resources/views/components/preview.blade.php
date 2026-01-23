@extends('layouts.theme')

@section('page_title', 'Preview - ' . $component->subject)

@section('content')

        {{-- Breadcrumb Card --}}
        @include('core::components.card', [
            'title' => 'Vista Previa de Componente'
        ])


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
                                    <i class="fas fa-desktop me-1"></i>Desktop
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="btnMobileView" data-width="375px">
                                    <i class="fas fa-mobile-screen me-1"></i>Mobile
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="preview-wrapper" style="background-color: #f5f5f5; min-height: 600px;">
                            <div class="preview-email-container" id="previewContainer" style="background-color: white; max-width: 100%; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; transition: all 0.3s ease;">
                                {!! $html !!}
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Esta es una vista previa aproximada. El resultado final puede variar según el cliente de email.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-12 col-lg-4">

                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom bg-warning-subtle">
                        <h6 class="mb-0 fw-bold">
                            Detalle del componente
                        </h6>
                        <small class="text-muted">Información detectada en el componente</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">NOMBRE DEL COMPONENTE</h6>
                                        <p class="mb-0 fw-bold">{{ $component->subject ?? 'Sin nombre' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">ALIAS</h6>
                                        <p class="mb-0"><code class="text-primary">{{ $component->alias }}</code></p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">CÓDIGO</h6>
                                        <p class="mb-0"><code class="text-primary">{{ $component->code }}</code></p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">TIPO</h6>
                                        <p class="mb-0">
                                            <span class="badge bg-{{ $component->type === 'partial' ? 'info' : ($component->type === 'layout' ? 'success' : 'secondary') }}-subtle text-{{ $component->type === 'partial' ? 'info' : ($component->type === 'layout' ? 'success' : 'secondary') }}">
                                                {{ ucfirst($component->type) }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">IDIOMA</h6>
                                        <p class="mb-0">{{ $translation->lang?->title ?? 'No definido' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">MÓDULO</h6>
                                        <p class="mb-0">
                                            <span class="badge bg-info-subtle text-info">{{ ucfirst($component->module ?? 'General') }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">PROTEGIDA</h6>
                                        <p class="mb-0">
                                            @if ($component->is_protected)
                                                <span class="badge bg-warning-subtle text-warning">
                                                <i class="fas fa-lock"></i> Sí
                                            </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">CREADO</h6>
                                        <p class="mb-0 small">{{ $component->created_at->format('d/m/Y H:i') }} {{ $component->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <div>
                                        <h6 class="text-muted fw-semibold small mb-1">ACTUALIZADO</h6>
                                        <p class="mb-0 small">{{ $component->updated_at->format('d/m/Y H:i') }} {{ $component->updated_at->diffForHumans() }}</p>
                                    </div>
                                </div>
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
                        <small class="text-muted">Acciones rápidas para gestionar</small>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('mailers.components.edit', $component->uid) }}" class="btn btn-primary">
                                Editar
                            </a>
                            <button type="button" class="btn btn-info" id="btnPrintComponent">
                                Imprimir
                            </button>

                            <a href="{{ route('mailers.components.index') }}" class="btn btn-secondary">
                                Volver
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            Variables en uso
                        </h6>
                        <small class="text-muted">Variables detectadas en el componente</small>
                    </div>
                    <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                        @php
                            preg_match_all('/\{([A-Z_]+)\}/', $translation->content ?? '', $matches);
                            $usedVariables = array_unique($matches[1]);
                        @endphp

                        @if (count($usedVariables) > 0)
                            <div class="row g-2">
                                @foreach ($usedVariables as $var)
                                    <div class="col-6">
                                        <div class="variable-badge">
                                            <i class="fas fa-tag"></i>
                                            <code>{{{ $var }}}</code>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">No se encontraron variables</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    @push('head')
        <style>
            /* Variable Badges */
            .variable-badge {
                background: white;
                border: 2px solid #e5e7eb;
                border-radius: 6px;
                padding: 8px 10px;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
            }

            .variable-badge:hover {
                border-color: #90bb13;
                background: #f6faf0;
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(144, 187, 19, 0.15);
            }

            .variable-badge i {
                color: #90bb13;
                font-size: 12px;
            }

            .variable-badge code {
                font-family: 'JetBrains Mono', 'Courier New', monospace;
                font-size: 11px;
                font-weight: 600;
                color: #1f2937;
                background: transparent;
                padding: 0;
            }

            /* Preview Wrapper */
            .preview-wrapper {
                display: flex;
                justify-content: center;
                align-items: flex-start;
                width: 100%;
                padding: 30px;
            }

            /* Preview Container Transitions */
            .preview-email-container {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                max-width: 100% !important;
                width: 100% !important;
                flex-shrink: 0;
            }

            /* Card Headers with Color */
            .card-header.bg-primary-subtle {
                background-color: rgba(13, 110, 253, 0.1) !important;
                border-bottom-color: rgba(13, 110, 253, 0.2) !important;
            }

            .card-header.bg-success-subtle {
                background-color: rgba(25, 135, 84, 0.1) !important;
                border-bottom-color: rgba(25, 135, 84, 0.2) !important;
            }

            .card-header.bg-info-subtle {
                background-color: rgba(13, 202, 240, 0.1) !important;
                border-bottom-color: rgba(13, 202, 240, 0.2) !important;
            }

            /* Button Group Active State */
            .btn-group .btn.active {
                background-color: #90bb13 !important;
                border-color: #90bb13 !important;
                color: white !important;
            }

            @media print {
                /* Hide everything by default */
                * {
                    margin: 0 !important;
                    padding: 0 !important;
                }

                /* Hide breadcrumb, sidebar, navigation */
                .container-fluid > .card:first-child,
                .col-lg-4,
                .navbar,
                nav,
                header {
                    display: none !important;
                }

                /* Hide all card headers and footers */
                .card-header {
                    display: none !important;
                }

                .card-footer {
                    display: none !important;
                }

                /* Hide buttons and UI elements */
                .btn-group,
                button,
                .btn {
                    display: none !important;
                }

                /* Setup body */
                body {
                    background: white !important;
                    color: black !important;
                    font-family: Arial, sans-serif !important;
                }

                /* Setup main containers */
                .container-fluid {
                    max-width: 100% !important;
                }

                .row {
                    display: block !important;
                }

                .col-12,
                .col-lg-8 {
                    width: 100% !important;
                    max-width: 100% !important;
                }

                /* Style for main preview card - show only the card itself */
                .col-lg-8 .card {
                    border: none !important;
                    box-shadow: none !important;
                    page-break-inside: avoid;
                }

                .col-lg-8 .card-body {
                    background: white !important;
                    padding: 0 !important;
                }

                /* Preview wrapper styling */
                .preview-wrapper {
                    background: white !important;
                    padding: 30px !important;
                    margin: 0 !important;
                    min-height: auto !important;
                    overflow: visible !important;
                }

                /* Email container - show at full size */
                .preview-email-container {
                    background: white !important;
                    box-shadow: none !important;
                    border: none !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    border-radius: 0 !important;
                    page-break-inside: avoid;
                    display: block !important;
                }

                /* Iframe styling */
                .preview-email-container iframe {
                    border: none !important;
                    box-shadow: none !important;
                    max-width: 100% !important;
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
            try {
                if (typeof Quill !== 'undefined') {
                    window.QuillDisabled = true;
                }
                window.PreviewPageActive = true;
            } catch (error) {
                console.warn('Preview initialization warning:', error);
            }

            $(document).ready(function() {
                // Device Preview Switcher
                $('#btnDesktopView, #btnMobileView').on('click', function(e) {
                    e.preventDefault();
                    const width = $(this).data('width');
                    const $container = $('#previewContainer');

                    // Update active button
                    $('.btn-group .btn').removeClass('active');
                    $(this).addClass('active');

                    // Animate container width
                    $container.css('max-width', width);

                    // Visual feedback
                    if (width === '375px') {
                        toastr.info('Vista móvil activada', 'Vista Previa', {
                            timeOut: 1500,
                            progressBar: true
                        });
                    } else {
                        toastr.info('Vista desktop activada', 'Vista Previa', {
                            timeOut: 1500,
                            progressBar: true
                        });
                    }
                });

                // Print Button
                $('#btnPrintComponent').on('click', function(e) {
                    e.preventDefault();
                    window.print();
                });
            });
        </script>
    @endpush

@endsection
