{{-- Extensión "settings" en el inbox: no pinta ningún modal. Pasa al JS las
     respuestas rápidas guardadas en «Ajustes del chat» (ya aplicadas sobre
     config por SettingsOverrides) y carga settings.js, que define
     window.PscQuickRepliesProvider: right-panel-prestashop-tabs.js lo usa en
     vez de sus respuestas de serie y rellena las variables con los datos
     reales del cliente abierto. --}}
@php
    $psSettingsReplies = array_values(array_filter(
        (array) config('helpdeskprestashop.ext.settings.quick_replies', []),
        fn ($r) => is_array($r) && trim((string) ($r['t'] ?? '')) !== '' && trim((string) ($r['text'] ?? '')) !== ''
    ));
@endphp
<div class="bv-hidden" id="psSettingsCfg"
     data-replies="{{ json_encode(array_map(fn ($r) => ['t' => (string) $r['t'], 's' => (string) ($r['s'] ?? ''), 'text' => (string) $r['text']], $psSettingsReplies), JSON_UNESCAPED_UNICODE) }}"></div>
@once
    @push('scripts')
        <script src="{{ asset('modules/helpdeskprestashop/js/ext/settings.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/settings.js')) }}" defer></script>
    @endpush
@endonce
