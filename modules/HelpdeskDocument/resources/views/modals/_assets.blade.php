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

    {{-- Mismo motivo que el <link> de arriba: en el panel derecho inyectado por
         AJAX, @push('scripts') no llega a ningún @stack y window.DocsModal no
         se definía, así que "Crear expediente" y abrir un expediente no hacían
         nada. Se carga inline con un guard para no duplicarlo ni volver a
         registrar sus handlers en cada cambio de conversación. --}}
    <script>
        (function () {
            if (window.__docsModalsLoading) { return; }
            window.__docsModalsLoading = true;
            var s = document.createElement('script');
            s.src = @json(asset('modules/document/js/modals.js').'?v='.filemtime(base_path('modules/Document/public/js/modals.js')));
            s.defer = true;
            document.head.appendChild(s);
        })();
    </script>
@endonce
