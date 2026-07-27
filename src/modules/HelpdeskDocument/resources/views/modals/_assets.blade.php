{{--
    Document module · Assets globales para los modales.
    Incluir UNA sola vez en la vista padre (Helpdesk show, Customer profile, etc).
    Ejemplo:
        @include('helpdeskdocument::modals._assets')
--}}
@once
    {{-- <link> inline (NO @push): este partial se incluye en el panel derecho del
         inbox (inbox-slots/right-panel-document-tab), que se inyecta por AJAX vía
         pane() — allí @push('css') no llega a ningún @stack y el CSS se perdía. --}}
    <link rel="stylesheet" href="{{ asset('modules/document/css/modals.css') }}?v={{ filemtime(base_path('modules/Document/public/css/modals.css')) }}">

    @push('scripts')
        <script src="{{ asset('modules/document/js/modals.js') }}?v={{ filemtime(base_path('modules/Document/public/js/modals.js')) }}"></script>
    @endpush
@endonce
