{{-- Modal: Atajos de teclado (#56 ve-shortcuts · ?) --}}
@php
    $shortcutCols = [
        ['Navegación', [
            ['Buscar global', ['⌘', 'K']],
            ['Siguiente conversación', ['J']],
            ['Conversación anterior', ['K']],
            ['Ir al hilo', ['Tab']],
            ['Ir al panel derecho', ['⇧', 'Tab']],
            ['Mostrar atajos', ['?']],
        ]],
        ['Conversación', [
            ['Responder', ['R']],
            ['Nota interna', ['N']],
            ['Asignar agente', ['A']],
            ['Etiquetar', ['T']],
            ['Cambiar estado', ['S']],
            ['Cambiar prioridad', ['P']],
            ['Aplicar macro', ['M']],
            ['Cerrar conversación', ['#']],
        ]],
        ['Composer', [
            ['Enviar mensaje', ['⌘', '⏎']],
            ['Nueva línea', ['⇧', '⏎']],
            ['Respuestas guardadas', ['/']],
            ['Mencionar agente', ['@']],
        ]],
        ['Bandeja y sistema', [
            ['No leídas', ['G', 'U']],
            ['Mías', ['G', 'M']],
            ['Todas', ['G', 'A']],
            ['Urgentes', ['G', 'R']],
            ['Filtrar', ['F']],
            ['Archivar', ['⌘', 'E']],
            ['Cerrar (directo)', ['⌘', '⇧', 'D']],
            ['Abrir/cerrar atajos', ['⌘', '/']],
        ]],
    ];
@endphp
<div class="bv-modal" data-bv-modal-name="shortcuts">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-keyboard"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.shortcuts_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.shortcuts_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close aria-label="{{ __('helpdesk::helpdesk.inbox.modals.shortcuts_close_aria') }}">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-cheat-grid">
                @foreach($shortcutCols as [$title, $rows])
                    <div class="bv-cheat-col">
                        <div class="bv-cheat-h">{{ $title }}</div>
                        @foreach($rows as [$label, $keys])
                            <div class="bv-cheat-row">
                                <span class="bv-cheat-lbl">{{ $label }}</span>
                                <span class="bv-cheat-keys">
                                    @foreach($keys as $key)
                                        <kbd class="bv-cheat-kbd">{{ $key }}</kbd>
                                    @endforeach
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sc-print">{{ __('helpdesk::helpdesk.inbox.modals.shortcuts_print') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.shortcuts_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';
    $(document).on('click', '#bv-sc-print', function () {
        window.print();
    });
}(window.jQuery));
</script>
@endpush
@endonce
