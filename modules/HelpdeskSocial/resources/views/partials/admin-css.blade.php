{{--
    CSS propio del módulo para las pantallas de gestión (truncado de texto,
    badges/puntos de color de etiquetas, barras de progreso). Incluir una
    vez por vista:

      @include('helpdesksocial::partials.admin-css')
--}}
@push('css')
<link rel="stylesheet" href="{{ asset('modules/helpdesksocial/css/social-admin.css') }}?v={{ filemtime(public_path('modules/helpdesksocial/css/social-admin.css')) }}">
@endpush
