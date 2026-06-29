@extends('layouts.theme')

@section('title', 'Codigo QR — ' . $campaign->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Codigo QR de campaña'])
@endsection

@section('content')

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">

                    {{-- Encabezado --}}
                    <div class="mb-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 qr-header-icon">
                            <i class="fas fa-qrcode fa-3x text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-1">{{ $campaign->name }}</h5>
                        <p class="text-muted mb-0">
                            Escanea este código QR para dejar una reseña en
                            <strong>{{ $campaign->location->name ?? '—' }}</strong>
                        </p>
                    </div>

                    <hr class="my-4">

                    {{-- Código QR --}}
                    <div class="text-center mb-4">
                        <div id="qrcode" class="qr-canvas d-inline-block mb-3"></div>
                        <p class="text-muted text-break small mb-0">
                            <i class="fas fa-link me-1"></i>
                            <a href="{{ $campaign->review_url }}" target="_blank" rel="noopener">{{ $campaign->review_url }}</a>
                        </p>
                    </div>

                    {{-- Cómo usar --}}
                    <div class="bg-light rounded-3 p-4 mb-4">
                        <h6 class="fw-bold mb-3">Cómo usar este código QR</h6>
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center qr-step-badge">1</span>
                            </div>
                            <div>
                                <strong class="d-block">Imprime o muestra en pantalla</strong>
                                <small class="text-muted">Coloca el QR en un lugar visible de tu negocio.</small>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center qr-step-badge">2</span>
                            </div>
                            <div>
                                <strong class="d-block">El cliente escanea con su móvil</strong>
                                <small class="text-muted">Cualquier cámara moderna o app de QR lo lee al instante.</small>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="me-3">
                                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center qr-step-badge">3</span>
                            </div>
                            <div>
                                <strong class="d-block">Reseña en segundos</strong>
                                <small class="text-muted">El cliente llega directamente al formulario de reseña sin buscar nada.</small>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer d-grid gap-2">
                    <button id="btn-descargar" class="btn btn-primary w-100 py-2">Descargar PNG</button>
                    <button id="btn-imprimir" class="btn btn-light border w-100 py-2">Imprimir</button>
                    <a href="{{ route('reviews.campaigns.index') }}" class="btn btn-light border w-100 py-2">Volver a campañas</a>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
<script>
$(function () {
    var reviewUrl = @json($campaign->review_url);
    var campaignName = @json($campaign->name);

    new QRCode(document.getElementById('qrcode'), {
        text: reviewUrl,
        width: 260,
        height: 260,
        colorDark: '#222222',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H,
    });

    $('#btn-descargar').on('click', function () {
        setTimeout(function () {
            var canvas = document.querySelector('#qrcode canvas');
            if (!canvas) {
                toastr.error('No se pudo generar el canvas del QR.');
                return;
            }
            var link = document.createElement('a');
            link.download = 'qr-' + campaignName.toLowerCase().replace(/\s+/g, '-') + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }, 100);
    });

    $('#btn-imprimir').on('click', function () {
        window.print();
    });
});
</script>
@endpush

@push('styles')
<style>
    .qr-header-icon {
        width: 80px;
        height: 80px;
        background: rgba(144, 187, 19, 0.12);
    }

    .qr-step-badge {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
    }

    .qr-canvas {
        border: 8px solid #fff;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
        border-radius: 8px;
    }

    @media print {
        .btn, nav, .sidebar, header, footer, .card-header, .bg-light { display: none !important; }
        .card { box-shadow: none !important; border: none !important; }
    }
</style>
@endpush
