{{-- Helper JS compartido para los modales de pedidos y carritos --}}
@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/_commerce-js.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/_commerce-js.js')) }}"></script>
@endpush
@endonce
