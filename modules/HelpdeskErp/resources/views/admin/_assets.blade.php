{{-- CSS/JS de «Ajustes de Gestión» y «Métricas de Gestión» (.era-*). --}}
@once
@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-admin.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-admin.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-admin.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-admin.js')) }}" defer></script>
@endpush
@endonce
