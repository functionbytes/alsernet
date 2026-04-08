@extends('layouts.theme')

@section('title', 'Codigo QR — ' . $campaign->name)

@section('content')

    @include('core::components.card', ['title' => 'Codigo QR de campaña'])

    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body p-5">

                    <h4 class="mb-1">{{ $campaign->name }}</h4>
                    <p class="text-muted mb-4">
                        <i class="fas fa-map-marker-alt"></i>
                        {{ $campaign->location->name ?? '—' }}
                    </p>

                    <div id="qrcode" class="d-inline-block mb-4" style="border: 8px solid #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.12); border-radius: 8px;"></div>

                    <p class="text-muted text-break mb-4">
                        <i class="fas fa-link"></i>
                        <a href="{{ $campaign->review_url }}" target="_blank" rel="noopener">{{ $campaign->review_url }}</a>
                    </p>

                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <button id="btn-descargar" class="btn btn-primary">
                            <i class="fas fa-download"></i> Descargar PNG
                        </button>
                        <button id="btn-imprimir" class="btn btn-outline-secondary">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <a href="{{ route('reviews.campaigns.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>

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

    var qr = new QRCode(document.getElementById('qrcode'), {
        text: reviewUrl,
        width: 300,
        height: 300,
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
@media print {
    .btn, nav, .sidebar, header, footer, .card-header { display: none !important; }
    .card { box-shadow: none !important; border: none !important; }
}
</style>
@endpush
