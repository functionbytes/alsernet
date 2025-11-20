@extends('layouts.managers')

@section('content')
    <style>
        .location-section-title {
            background: linear-gradient(135deg, #90bb13 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            margin: 20px 0 15px 0;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
        }

        .barcode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .barcode-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .barcode-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .barcode-card h5 {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }

        .barcode-card-subtitle {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }

        .barcode-svg-container {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        svg {
            max-width: 100%;
            height: auto;
        }

        .barcode-code {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #333;
            background: #f6f6f6 !important;
            padding: 8px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 3px solid #90bb13;
            word-break: break-all;
            font-weight: 600;
        }

        .barcode-details {
            font-size: 11px;
            color: #555;
            text-align: left;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .detail-label {
            font-weight: 700;
            color: #333;
        }

        @media print {
            body {
                background: white;
            }

            .navbar,
            .sidebar,
            .btn,
            .card-header,
            .row:first-child,
            .no-print {
                display: none !important;
            }

            .page-wrapper {
                padding: 0;
            }

            .content {
                margin: 0;
            }

            .card {
                box-shadow: none;
                border: none;
                padding: 0;
            }

            .card-body {
                padding: 0;
            }

            .barcode-details {
                display: none;
            }

            .location-section-title {
                page-break-after: avoid;
                margin: 20px 0 10px 0;
            }

            .barcode-card {
                page-break-inside: avoid;
                border: 1px solid #ddd;
            }

            .barcode-grid {
                gap: 10px;
                margin-bottom: 20px;
            }
        }
    </style>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <div>
                            <h5 class="card-title mb-1">Códigos de Barras - Piso {{ $floor->name }}</h5>
                            <p class="text-muted mb-0">Almacén: <strong>{{ $warehouse->name }}</strong></p>
                        </div>
                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-sm btn-success no-print">
                                <i class="ti ti-printer"></i>
                            </button>
                            <a href="{{ route('manager.warehouse.locations', [$warehouse->uid, $floor->uid]) }}" class="btn btn-sm btn-secondary no-print">
                                <i class="ti ti-arrow-left"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Info Cards -->
                    <div class="row mb-4 no-print">
                        <div class="col-md-4">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h5 class="text-primary">{{ $warehouse->name }}</h5>
                                        <p class="text-muted mb-0 small">Almacén</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h5 class="text-primary">{{ $floor->code }}</h5>
                                        <p class="text-muted mb-0 small">Piso</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h5 class="text-primary">{{ $locations->sum(fn($l) => $l->sections->count()) }}</h5>
                                        <p class="text-muted mb-0 small">Total niveles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Locations with Barcodes -->
                    @if ($locations->count() > 0)
                        @foreach ($locations as $location)
                            @if ($location->sections->count() > 0)
                                <!-- Location Section Title -->
                                <div class="location-section-title">
                                    📍 {{ $location->code }} - {{ $location->style?->name ?? 'Sin Estilo' }}
                                </div>

                                <!-- Barcodes Grid for this Location -->
                                <div class="barcode-grid">
                                    @foreach ($location->sections as $section)
                                        <div class="barcode-card">
                                            <h5>{{ $section->code }}</h5>
                                            <div class="barcode-card-subtitle">
                                                Nivel {{ $section->level }}
                                                @if($section->face)
                                                    • {{ $section->face == 'front' ? 'Frontal' : 'Posterior' }}
                                                @endif
                                            </div>

                                            <div class="barcode-svg-container">
                                                <svg id="barcode-{{ $location->id }}-{{ $section->id }}"></svg>
                                            </div>

                                            <div class="barcode-code">{{ $section->barcode }}</div>

                                            <div class="barcode-details">
                                                <div class="detail-row">
                                                    <span class="detail-label">Ubicación:</span>
                                                    <span>{{ $location->code }}</span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="detail-label">Sección:</span>
                                                    <span>{{ $section->code }}</span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="detail-label">Código:</span>
                                                    <span>{{ $section->barcode }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i> No hay ubicaciones registradas para este piso.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- JsBarcode Script -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach ($locations as $location)
                @foreach ($location->sections as $section)
                    JsBarcode("#barcode-{{ $location->id }}-{{ $section->id }}", "{{ $section->barcode }}", {
                        format: "CODE128",
                        width: 2,
                        height: 50,
                        displayValue: false,
                        lineColor: "#000000"
                    });
                @endforeach
            @endforeach
        });
    </script>
@endsection
