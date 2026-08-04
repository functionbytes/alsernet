{{-- Modal: Búsqueda global / command palette (#55 ve-command-palette · ⌘K) --}}
{{-- Nota: No usa el sistema bv-modal estándar. Se abre con Ctrl+K / Cmd+K (handler
     en conversations.js) o window.openCommandPalette(). Estilos en conversations.css. --}}
<div id="bv-cmd-palette" class="bv-cmd-overlay" data-tickets-enabled="{{ (function_exists('helpdesk_tickets_enabled') && helpdesk_tickets_enabled()) ? 1 : 0 }}" aria-hidden="true" role="dialog" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.command_palette_aria_label') }}">
    <div class="bv-cmd-modal" role="search">
        <div class="bv-cmd-input-wrap">
            <i class="fas fa-magnifying-glass bv-cmd-icon"></i>
            <input id="bvCmdInput" type="text" class="bv-cmd-input"
                   placeholder="{{ __('helpdesk::helpdesk.inbox.modals.command_palette_search_placeholder') }}"
                   autocomplete="off" spellcheck="false">
            <kbd class="bv-cmd-esc" id="bvCmdEsc">ESC</kbd>
        </div>
        <div id="bvCmdBody" class="bv-cmd-body">
            <div class="bv-cmd-empty">
                <i class="fas fa-magnifying-glass"></i>
                <span>{{ __('helpdesk::helpdesk.inbox.modals.command_palette_empty_hint') }}</span>
            </div>
        </div>
        <div class="bv-cmd-foot">
            <span class="bv-cmd-foot-k"><kbd class="bv-cmd-kbd">↑↓</kbd> {{ __('helpdesk::helpdesk.inbox.modals.command_palette_nav_up_down') }}</span>
            <span class="bv-cmd-foot-k"><kbd class="bv-cmd-kbd">⏎</kbd> {{ __('helpdesk::helpdesk.inbox.modals.command_palette_nav_open') }}</span>
            <span class="bv-cmd-foot-k"><kbd class="bv-cmd-kbd">esc</kbd> {{ __('helpdesk::helpdesk.inbox.modals.command_palette_nav_close') }}</span>
            <span class="bv-cmd-foot-count" id="bvCmdCount"></span>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/command-palette.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/command-palette.js')) }}" defer></script>
@endpush
@endonce
