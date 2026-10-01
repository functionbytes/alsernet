{{-- HelpdeskAiPrompts — "Acciones IA" en el composer de la bandeja (source
     `agent`). Mismo patrón que helpdeskdocument::partials.thread-extension:
     el botón se regenera en cada render (lleva las URLs de la conversación);
     modal y script van en los stacks que sobreviven al swap AJAX de pane. --}}
@php
    $aaConvo = $selectedConversation ?? null;
    $aaUser = auth()->user();
    $aaEnabled = $aaUser?->can('helpdesk.ai-prompts.agent-actions')
        && \Illuminate\Support\Facades\Route::has('helpdesk-ai-prompts.agent-actions.index');
@endphp

@if($aaEnabled)
{{-- Modal y script SIEMPRE (aunque no haya conversación abierta): si no,
     tras el primer swap AJAX el botón queda sin su JS ni su modal. --}}
@push('hd-thread-modals')
@include('helpdeskaiprompts::partials.agent-actions-modal')
@endpush

@push('hd-thread-scripts')
<script src="{{ asset('modules/helpdeskaiprompts/js/ai-prompts-agent-actions.js') }}?v={{ @filemtime(public_path('modules/helpdeskaiprompts/js/ai-prompts-agent-actions.js')) }}"></script>
@endpush

@if($aaConvo)
@push('hd-composer-toolbar-buttons')
<button class="btn-ico" type="button" id="bv-agent-actions-btn" data-bv-tip="{{ __('helpdeskaiprompts::agent-actions.button') }}"
    aria-label="{{ __('helpdeskaiprompts::agent-actions.button') }}"
    data-aa-list-url="{{ route('helpdesk-ai-prompts.agent-actions.index', $aaConvo->id) }}"
    data-aa-run-url="{{ route('helpdesk-ai-prompts.agent-actions.run', $aaConvo->id) }}">
    <i class="fas fa-bolt-lightning" aria-hidden="true"></i>
</button>
@endpush

@endif
@endif
