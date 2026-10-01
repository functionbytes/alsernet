{{-- Assets de la UI de medios. $target: inbox | tickets. Idempotente: el JS se
     protege contra doble carga (window.HdMedia) porque el panel de la bandeja
     se reemplaza por AJAX. --}}
@php
    $hdmVersion = fn (string $file): int => (int) @filemtime(public_path('modules/helpdeskmedia/'.$file));
    $hdmI18n = collect(['scan_clean', 'scan_clean_title', 'scan_unavailable', 'scan_unavailable_title', 'scan_infected', 'scan_infected_title',
        'scan_infected_untitled', 'converted_from', 'saving', 'transcript', 'transcript_pending', 'transcript_language', 'copy', 'copied', 'copy_failed'])
        ->mapWithKeys(fn (string $key): array => [$key => __('helpdeskmedia::ui.'.$key)]);
@endphp
<script>
window.HdMediaConfig = {
    conversationUrl: @json(route('helpdesk-media.conversation', ['conversation' => '__ID__'])),
    ticketUrl: @json(route('helpdesk-media.ticket', ['ticket' => '__ID__'])),
    i18n: @json($hdmI18n)
};
</script>
<link rel="stylesheet" href="{{ asset('modules/helpdeskmedia/css/media-ui.css') }}?v={{ $hdmVersion('css/media-ui.css') }}">
<script src="{{ asset('modules/helpdeskmedia/js/media-common.js') }}?v={{ $hdmVersion('js/media-common.js') }}"></script>
<script src="{{ asset('modules/helpdeskmedia/js/media-'.$target.'.js') }}?v={{ $hdmVersion('js/media-'.$target.'.js') }}"></script>
