{{-- Modal: Mencionar agente o equipo --}}
<div class="bv-modal" data-bv-modal-name="mention">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-at"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.mention_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.mention_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close type="button"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Search --}}
            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="bv-mention-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.mention_search_placeholder') }}" autocomplete="off">
                <button type="button" class="bv-mention-help-link" data-bv-modal="mention-help" title="{{ __('helpdesk::helpdesk.inbox.modals.mention_view_guide') }}"
                    <i class="far fa-circle-question"></i>
                </button>
            </div>

            {{-- Tabs Agentes / Equipos --}}
            <div class="bv-modal-tabs">
                <button class="bv-modal-tab on" type="button" data-tab="agents">
                    <i class="far fa-user"></i> {{ __('helpdesk::helpdesk.inbox.modals.mention_tab_agents') }}
                    <span class="bv-mention-tab-count" id="bv-mention-tab-count-agents">0</span>
                </button>
                <button class="bv-modal-tab" type="button" data-tab="teams">
                    <i class="far fa-users"></i> {{ __('helpdesk::helpdesk.inbox.modals.mention_tab_teams') }}
                    <span class="bv-mention-tab-count" id="bv-mention-tab-count-teams">0</span>
                </button>
            </div>

            {{-- Tab: Agentes --}}
            <div class="bv-mention-tab" data-panel="agents">
                <div class="bv-opt-list" id="bv-mention-modal-list-agents">
                    <div class="bv-mention-modal-empty">{{ __('helpdesk::helpdesk.inbox.modals.mention_loading') }}</div>
                </div>
            </div>

            {{-- Tab: Equipos --}}
            <div class="bv-mention-tab bv-tab-hidden" data-panel="teams">
                <div class="bv-opt-list" id="bv-mention-modal-list-teams">
                    <div class="bv-mention-modal-empty">{{ __('helpdesk::helpdesk.inbox.modals.mention_loading') }}</div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-mention-modal-insert" disabled type="button">
                {{ __('helpdesk::helpdesk.inbox.modals.mention_insert') }}
            </button>
            <button class="btn-secondary" data-bv-close type="button">{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>
