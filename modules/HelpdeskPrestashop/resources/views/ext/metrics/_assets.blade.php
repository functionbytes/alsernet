{{-- CSS/JS de «Métricas del chat · PrestaShop». prestashop-chat.css aporta
     el vocabulario .psc-* (KPIs, barras .psc-bars/.psc-h-N, chips, tabla);
     metrics.css solo lo propio de esta pantalla. --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/prestashop-chat.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/prestashop-chat.css')) }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/metrics.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/metrics.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/metrics.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/metrics.js')) }}" defer></script>
@endpush
