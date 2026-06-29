{{--
    Document module · Assets globales para los modales.
    Incluir UNA sola vez en la vista padre (Helpdesk show, Customer profile, etc).
    Ejemplo:
        @include('helpdeskdocument::modals._assets')
--}}
@once
    @push('css')
        <link rel="stylesheet" href="{{ asset('modules/document/css/modals.css') }}?v={{ md5_file(base_path('modules/Document/public/css/modals.css')) }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('modules/document/js/modals.js') }}?v={{ md5_file(base_path('modules/Document/public/js/modals.js')) }}"></script>
    @endpush
@endonce
