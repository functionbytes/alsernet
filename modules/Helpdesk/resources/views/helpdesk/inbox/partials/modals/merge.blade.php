{{-- Modal: Fusionar conversación --}}
<div class="bv-modal" data-bv-modal-name="merge">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box danger"><i class="fas fa-code-fork"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_label') }}</span>
                <div class="bv-modal-title">
                    {{ __('helpdesk::helpdesk.inbox.modals.merge_title') }}
                    @if(!empty($selectedConversation))<span class="bv-chip-id">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            {{-- Warning --}}
            <div class="alert warning">
                <i class="fas fa-triangle-exclamation lead"></i>
                <div>{{ __('helpdesk::helpdesk.inbox.modals.merge_warning_pre') }} <b>{{ __('helpdesk::helpdesk.inbox.modals.merge_warning_bold') }}</b>. {{ __('helpdesk::helpdesk.inbox.modals.merge_warning_post') }}</div>
            </div>

            {{-- Search --}}
            <div class="bv-modal-search bv-mb-12">
                <i class="fas fa-magnifying-glass"></i>
                <input id="merge-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.merge_search_placeholder') }}" autocomplete="off">
            </div>

            {{-- Conversation list --}}
            <div class="bv-right-section-title bv-mb-8">{{ __('helpdesk::helpdesk.inbox.modals.merge_same_contact') }}</div>
            <div class="bv-opt-list" id="merge-list">
                <div class="bv-opt on" data-conv-id="7801">
                    <div class="bv-av c5 bv-av-rounded bv-merge-av"><i class="fab fa-facebook-messenger"></i></div>
                    <div class="body">
                        <div class="name">#7801 · Consulta de pedido</div>
                        <div class="sub">Hola, quería saber si llegó ya mi pedido… · hace 4 días</div>
                    </div>
                    <span class="bv-modal-radio-dot flex-shrink-0"></span>
                </div>
                <div class="bv-opt" data-conv-id="7654">
                    <div class="bv-av c2 bv-av-rounded bv-merge-av"><i class="far fa-comment"></i></div>
                    <div class="body">
                        <div class="name">#7654 · Formulario web</div>
                        <div class="sub">Buenas tardes, tengo una duda sobre… · 12 mar</div>
                    </div>
                    <span class="bv-modal-radio-dot flex-shrink-0"></span>
                </div>
                <div class="bv-opt" data-conv-id="7512">
                    <div class="bv-av c4 bv-av-rounded bv-merge-av bv-merge-av--sm"><i class="fas fa-envelope"></i></div>
                    <div class="body">
                        <div class="name">#7512 · Seguimiento devolución</div>
                        <div class="sub">Hola, quería saber en qué estado está mi… · 2 mar</div>
                    </div>
                    <span class="bv-modal-radio-dot flex-shrink-0"></span>
                </div>
            </div>

            {{-- Merge preview --}}
            <div class="bv-right-section-title bv-mt-16 bv-mb-8">{{ __('helpdesk::helpdesk.inbox.modals.merge_preview_title') }}</div>
            <div class="bv-merge-preview-grid">
                <div class="bv-merge-conv-panel">
                    <div class="bv-merge-panel-label">{{ __('helpdesk::helpdesk.inbox.modals.merge_current_conversation') }}</div>
                    <div class="bv-bubble bv-merge-bubble">{{ __('helpdesk::helpdesk.inbox.modals.merge_sample_current_1') }}</div>
                    <div class="bv-bubble bv-merge-bubble">{{ __('helpdesk::helpdesk.inbox.modals.merge_sample_current_2') }}</div>
                    <div class="bv-bubble bv-merge-bubble-last">{{ __('helpdesk::helpdesk.inbox.modals.merge_sample_current_3') }}</div>
                </div>
                <div class="bv-merge-conv-panel">
                    <div class="bv-merge-panel-label">{{ __('helpdesk::helpdesk.inbox.modals.merge_target_conversation') }}</div>
                    <div class="bv-bubble bv-merge-bubble">{{ __('helpdesk::helpdesk.inbox.modals.merge_sample_target_1') }}</div>
                    <div class="bv-bubble bv-merge-bubble">{{ __('helpdesk::helpdesk.inbox.modals.merge_sample_target_2') }}</div>
                    <div class="bv-bubble bv-merge-bubble-last">{{ __('helpdesk::helpdesk.inbox.modals.merge_sample_target_3') }}</div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-merge-apply">{{ __('helpdesk::helpdesk.inbox.modals.merge_apply') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/merge.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/merge.js')) }}"></script>
@endpush
@endonce
