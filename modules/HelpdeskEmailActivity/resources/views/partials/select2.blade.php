{{-- Inicialización de Select2 para todos los .evx-select del módulo.

     Se incluye desde cada vista que tenga desplegables:

       @include('helpdeskemailactivity::partials.select2')

     Trae su propio @push('scripts'), así que basta con incluirlo una vez por
     vista, en cualquier punto del cuerpo. --}}
@push('scripts')
{{-- data-* en vez de interpolar @json(__()) dentro del .js estatico: la
     logica vive en select2-init.js, y estas 2 cadenas traducidas son el
     unico dato que ese fichero no puede resolver por su cuenta. --}}
<script src="{{ asset('modules/helpdeskemailactivity/js/select2-init.js') }}?v={{ filemtime(public_path('modules/helpdeskemailactivity/js/select2-init.js')) }}"
        data-evx-select2-i18n
        data-no-results="{{ __('helpdeskemailactivity::emaillog.filters.select_no_results') }}"
        data-searching="{{ __('helpdeskemailactivity::emaillog.filters.select_searching') }}"></script>
@endpush
