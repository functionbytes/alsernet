{{--
    Boton que abre los filtros avanzados. Va emparejado con <x-filter-shell>:
    lee el mismo ajuste, asi que el gesto de apertura (modal u offcanvas)
    concuerda siempre con el contenedor que se haya renderizado.

    @param string      $target Id del contenedor de filtros
    @param int         $count  Filtros aplicados; se pinta como globo sobre el boton
    @param string|null $style  Fuerza un estilo; por defecto, el del .env
--}}
@props([
    'target' => 'filter-shell',
    'count' => 0,
    'style' => null,
])

@php
    $fsStyle = in_array($style, ['grid', 'panel'], true)
        ? $style
        : (config('custom.filters_style') === 'panel' ? 'panel' : 'grid');
@endphp

<button type="button"
        class="btn btn-secondary position-relative flex-shrink-0"
        data-bs-toggle="{{ $fsStyle === 'panel' ? 'offcanvas' : 'modal' }}"
        data-bs-target="#{{ $target }}"
        title="Filtros avanzados"
        aria-label="Filtros avanzados">
    <i class="fas fa-filter"></i>
    @if($count > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary fs-btn-count">{{ $count }}</span>
    @endif
</button>

@once
    @push('styles')
        <style>
            .fs-btn-count { font-size: .6rem; }
        </style>
    @endpush
@endonce
