{{--
    Contenedor de los filtros avanzados de un listado.

    Un mismo contenido se dispone de dos maneras segun custom.filters_style
    (PANEL_FILTERS_STYLE en el .env):

      grid  → modal ancho con los campos en dos columnas; todo a la vista,
              sin scroll, para pantallas con sitio.
      panel → panel lateral que no tapa la tabla, para ir viendo el
              resultado mientras se ajustan los filtros.

    Los campos van en el slot por defecto, cada uno envuelto en .fs-field
    (mas .fs-field--full para el que deba ocupar la fila entera). La
    disposicion la decide el contenedor, asi que el mismo markup sirve para
    los dos estilos.

        <x-filter-shell :count="$activeFilterCount" apply-id="filter-apply-btn" clear-id="filter-clear-btn">
            <x-slot:applied> ...chips... </x-slot:applied>
            <div class="fs-field"> ...campo... </div>
        </x-filter-shell>

    @param string      $id        Id del contenedor (por defecto filter-shell)
    @param string      $title     Titulo de la cabecera
    @param int         $count     Numero de filtros aplicados
    @param string      $applyId   Id del boton de aplicar (para el JS de la pagina)
    @param string      $clearId   Id del boton de limpiar
    @param string|null $applyLabel
    @param string|null $clearLabel
    @param string|null $style     Fuerza un estilo; por defecto, el del .env
--}}
@props([
    'id' => 'filter-shell',
    'title' => 'Filtros avanzados',
    'count' => 0,
    'applyId' => 'filter-apply-btn',
    'clearId' => 'filter-clear-btn',
    'applyLabel' => 'Aplicar filtros',
    'clearLabel' => 'Limpiar',
    'style' => null,
    'applied' => null,
])

@php
    $fsStyle = in_array($style, ['grid', 'panel'], true)
        ? $style
        : (config('custom.filters_style') === 'panel' ? 'panel' : 'grid');
@endphp

@if($fsStyle === 'panel')
    <div class="offcanvas offcanvas-end fs-shell fs-shell--panel" tabindex="-1" id="{{ $id }}" aria-labelledby="{{ $id }}-title">
        <div class="offcanvas-header fs-head">
            <div class="fs-head-left">
                <span class="fs-badge">
                    <i class="fas fa-filter"></i>
                </span>
                <h6 class="fs-title mb-0" id="{{ $id }}-title">{{ $title }}</h6>
                @if($count > 0)
                    <span class="fs-count">{{ $count }}</span>
                @endif
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>

        <div class="offcanvas-body fs-body">
            @if($applied)
                <div class="fs-applied">{{ $applied }}</div>
            @endif
            <div class="fs-grid">{{ $slot }}</div>
        </div>

        <div class="fs-foot">
            <button type="button" id="{{ $applyId }}" class="btn btn-primary w-100">{{ $applyLabel }}</button>
            <button type="button" id="{{ $clearId }}" class="btn btn-secondary w-100">{{ $clearLabel }}</button>
        </div>
    </div>
@else
    <div class="modal fade fs-shell fs-shell--grid" id="{{ $id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable fs-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header fs-head">
                    <div class="fs-head-left">
                        <span class="fs-badge">
                            <i class="fas fa-filter"></i>
                        </span>
                        <h6 class="fs-title mb-0">{{ $title }}</h6>
                        @if($count > 0)
                            <span class="fs-count">{{ $count }} activos</span>
                        @endif
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body fs-body">
                    @if($applied)
                        <div class="fs-applied">{{ $applied }}</div>
                    @endif
                    <div class="fs-grid">{{ $slot }}</div>
                </div>

                <div class="modal-footer fs-foot">
                    <button type="button" id="{{ $applyId }}" class="btn btn-primary w-100">{{ $applyLabel }}</button>
                    <button type="button" id="{{ $clearId }}" class="btn btn-secondary w-100">{{ $clearLabel }}</button>
                </div>
            </div>
        </div>
    </div>
@endif

@once
    @push('styles')
        <style>
            .fs-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid var(--pb-border, #e5e7eb);
            }

            .fs-head-left {
                display: flex;
                align-items: center;
                gap: .625rem;
            }

            .fs-badge {
                width: 32px;
                height: 32px;
                flex-shrink: 0;
                border-radius: 50%;
                background: var(--pb-primary-soft, rgba(144, 187, 19, .08));
                border: 1px solid var(--pb-primary-border, rgba(144, 187, 19, .25));
                color: var(--pb-primary-dark, #6d8c0c);
                font-size: .8rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .fs-title { font-size: 1rem; font-weight: 700; }

            .fs-count {
                font-size: .75rem;
                font-weight: 600;
                padding: 2px 9px;
                border-radius: 999px;
                background: var(--pb-primary-soft, rgba(144, 187, 19, .08));
                border: 1px solid var(--pb-primary-border, rgba(144, 187, 19, .25));
                color: var(--pb-primary-dark, #6d8c0c);
            }

            .fs-body { padding: 1rem 1.5rem 1.25rem; }

            .fs-applied {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: .5rem;
                padding: .75rem .875rem;
                margin-bottom: 1.125rem;
                background: var(--pb-surface-soft, #f8f9fa);
                border: 1px solid var(--pb-border, #e5e7eb);
                border-radius: var(--pb-radius, .625rem);
            }

            .fs-chip {
                display: inline-flex;
                align-items: center;
                gap: .375rem;
                font-size: .78rem;
                padding: 4px 10px;
                border-radius: 999px;
                background: var(--pb-primary-soft, rgba(144, 187, 19, .08));
                border: 1px solid var(--pb-primary-border, rgba(144, 187, 19, .25));
                color: var(--pb-primary-dark, #6d8c0c);
            }

            .fs-chip b { font-weight: 600; }

            .fs-applied-label {
                font-size: .78rem;
                font-weight: 600;
                color: var(--pb-muted, #5a6a85);
            }

            /* Una columna por defecto: es lo que necesita el panel lateral y
               lo que deja el modal legible en pantallas estrechas. */
            .fs-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: .875rem 1.25rem;
            }

            .fs-field { display: flex; flex-direction: column; gap: .375rem; }
            .fs-field .form-label { font-size: .8rem; font-weight: 600; margin-bottom: 0; }
            .fs-field--full { grid-column: 1 / -1; }

            .fs-foot {
                display: flex;
                flex-direction: column;
                gap: .5rem;
                padding: 1rem 1.5rem;
                border-top: 1px solid var(--pb-border, #e5e7eb);
            }

            /* El footer de un modal de Bootstrap reparte los botones en fila;
               aqui van apilados y a todo el ancho, como el resto de modales. */
            .fs-shell--grid .modal-footer > * { margin: 0; }

            .fs-shell--grid .fs-dialog { max-width: 760px; }

            @media (min-width: 768px) {
                .fs-shell--grid .fs-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }

            /* Bootstrap fija el ancho del offcanvas con su propia variable, asi
               que un width: suelto aqui no gana. */
            .fs-shell--panel { --bs-offcanvas-width: 420px; }
            .fs-shell--panel .fs-body { overflow-y: auto; flex: 1 1 auto; }
            .fs-shell--panel .fs-foot { flex-shrink: 0; }

            @media (max-width: 575.98px) {
                .fs-shell--panel { --bs-offcanvas-width: 100%; }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // El JS de cada listado no debe saber si los filtros viven en un
            // modal o en un panel: se cierran y se localizan por aqui.
            window.FilterShell = (function () {
                function el(id) {
                    return document.getElementById(id || 'filter-shell');
                }

                return {
                    el: function (id) { return $(el(id)); },
                    close: function (id) {
                        var node = el(id);

                        if (! node) return;

                        if (node.classList.contains('offcanvas')) {
                            (bootstrap.Offcanvas.getInstance(node) || new bootstrap.Offcanvas(node)).hide();
                        } else {
                            (bootstrap.Modal.getInstance(node) || new bootstrap.Modal(node)).hide();
                        }
                    },
                };
            })();
        </script>
    @endpush
@endonce
