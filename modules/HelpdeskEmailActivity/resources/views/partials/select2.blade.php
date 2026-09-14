{{-- Inicialización de Select2 para todos los .evx-select del módulo.

     Se incluye desde cada vista que tenga desplegables:

       @include('helpdeskemailactivity::partials.select2')

     Trae su propio @push('scripts'), así que basta con incluirlo una vez por
     vista, en cualquier punto del cuerpo. --}}
@push('scripts')
<script>
$(function () {
    if (! $.fn.select2) return;

    // Sin theme 'bootstrap-5': ese CSS no lo sirve este tema y deja los
    // controles rotos. El aspecto lo da .evx-select en emaillog.css, que
    // replica sobre el markup de Select2 el borde, radio y tipografía que
    // tenía el <select> nativo.

    // El buscador interno solo donde hay bastantes opciones que filtrar; en
    // un desplegable de tres entradas estorba más de lo que ayuda.
    const conBuscador = ['module', 'causer_id', 'from_address'];

    $('.evx-select').each(function () {
        const $sel = $(this);
        if ($sel.data('select2')) return;

        // dropdownParent importa en dos sitios: dentro de un modal (si no, el
        // panel se pinta detrás del backdrop y el buscador no recibe foco) y
        // dentro de #evx-filters-more, que nace con [hidden] y haría que
        // Select2 midiera mal el ancho al desplegarlo.
        const $modal = $sel.closest('.modal');
        const $plegable = $sel.closest('.evx-filters-more');
        const $padre = $modal.length ? $modal : ($plegable.length ? $plegable : $(document.body));

        $sel.select2({
            width: 'resolve',
            minimumResultsForSearch: conBuscador.includes($sel.attr('name')) ? 0 : Infinity,
            dropdownParent: $padre,
            language: {
                noResults: () => @json(__('helpdeskemailactivity::emaillog.filters.select_no_results')),
                searching: () => @json(__('helpdeskemailactivity::emaillog.filters.select_searching')),
            },
        });
    });
});
</script>
@endpush
