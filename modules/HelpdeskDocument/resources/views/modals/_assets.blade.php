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
         registrar sus handlers en cada cambio de conversación.

         21-sep-2026: igual que en right-panel-document-tab, el guard se
         quedaba en true aunque la carga fallara, dejando window.DocsModal
         sin definir (y el panel muerto al clic) el resto de la sesión.
         __docsModalsLoaded solo se marca en onload; onerror libera
         __docsModalsLoading para reintentar en el próximo render. --}}
    <script>
        (function () {
            if (window.__docsModalsLoaded || window.__docsModalsLoading) { return; }
            window.__docsModalsLoading = true;
            var s = document.createElement('script');
            s.src = @json(asset('modules/document/js/modals.js').'?v='.filemtime(base_path('modules/Document/public/js/modals.js')));
            s.defer = true;
            s.onload = function () {
                window.__docsModalsLoading = false;
                window.__docsModalsLoaded = true;
            };
            s.onerror = function () {
                window.__docsModalsLoading = false;
                console.error('[Document] No se pudo cargar modals.js — los paneles de expediente no funcionarán hasta que se reintente.');
            };
            document.head.appendChild(s);
        })();
    </script>
@endonce
