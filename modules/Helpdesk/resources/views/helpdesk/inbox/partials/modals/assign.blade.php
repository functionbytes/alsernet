{{-- Modal: Asignar conversación --}}
<div class="bv-modal" data-bv-modal-name="assign">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-user-check"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.label_conversation') }}</span>
                <div class="bv-modal-title">
                    {{ __('helpdesk::helpdesk.inbox.modals.assign_title') }}
                    @if(!empty($selectedConversation))<span class="bv-chip-id">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Búsqueda --}}
            <div class="bv-modal-search bv-modal-search--hint">
                <i class="fas fa-magnifying-glass"></i>
                <input id="assign-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.assign_search_placeholder') }}" autocomplete="off">
                <span class="bv-kbd asgn-search-hint">↑↓</span>
            </div>

            {{-- Lista unificada: agentes + equipos --}}
            <div id="assign-unified-list" class="asgn-list">

                {{-- Sección: Agentes --}}
                <div class="asgn-sec-lbl">{{ __('helpdesk::helpdesk.inbox.modals.assign_suggested') }}</div>

                {{-- Sin asignar --}}
                <button class="asgn-item {{ !$selectedConversation?->assignee_id ? 'on' : '' }}" data-agent-id="">
                    <div class="asgn-ico"><i class="fas fa-user-slash"></i></div>
                    <div class="asgn-body">
                        <span class="asgn-t">{{ __('helpdesk::helpdesk.inbox.modals.unassigned') }}</span>
                        <span class="asgn-s">{{ __('helpdesk::helpdesk.inbox.modals.assign_remove_current') }}</span>
                    </div>
                    <div class="asgn-check"><i class="fas fa-check"></i></div>
                </button>

                @foreach($agents ?? [] as $agent)
                    @php
                        $status    = $agent->helpdesk_status ?? 'offline';
                        $initials  = strtoupper(substr($agent->firstname ?? '', 0, 1) . substr($agent->lastname ?? '', 0, 1));
                        $colorIdx  = ($loop->index % 7) + 1;
                        $subtitle  = trim($agent->role ?? '') ?: ($agent->email ?? '');
                        $openCount = (int) ($agent->open_count ?? 0);
                        $loadClass = $openCount >= 10 ? 'full' : ($openCount >= 7 ? 'warn' : '');
                    @endphp
                    <button class="asgn-item {{ $selectedConversation?->assignee_id === $agent->id ? 'on' : '' }}"
                            data-agent-id="{{ $agent->id }}">
                        <div class="bv-av c{{ $colorIdx }} asgn-av">
                            {{ $initials }}
                            <span class="bv-av-dot {{ $status }}"></span>
                        </div>
                        <div class="asgn-body">
                            <span class="asgn-t">{{ $agent->firstname }} {{ $agent->lastname }}</span>
                            <span class="asgn-s">{{ $subtitle }}</span>
                        </div>
                        @if($openCount > 0)
                            <span class="asgn-load {{ $loadClass }}">{{ $openCount }}</span>
                        @endif
                        <div class="asgn-check"><i class="fas fa-check"></i></div>
                    </button>
                @endforeach

                {{-- Sección: Equipos --}}
                @if(!empty($groups) && count($groups) > 0)
                    <div class="asgn-sec-lbl">{{ __('helpdesk::helpdesk.inbox.modals.teams') }}</div>
                    @foreach($groups as $group)
                        <button class="asgn-item {{ $selectedConversation?->group_id === $group->id ? 'on' : '' }}"
                                data-team-id="{{ $group->id }}">
                            <div class="asgn-ico"><i class="fas fa-users"></i></div>
                            <div class="asgn-body">
                                <span class="asgn-t">{{ $group->name }}</span>
                                @if(isset($group->agents_count) && $group->agents_count > 0)
                                    <span class="asgn-s">{{ $group->agents_count }} {{ __('helpdesk::helpdesk.inbox.modals.assign_agents_suffix') }}</span>
                                @endif
                            </div>
                            <div class="asgn-check"><i class="fas fa-check"></i></div>
                        </button>
                    @endforeach
                @endif

            </div>

            {{-- Notificaciones al agente --}}
            <div class="asgn-notify">
                <div class="asgn-notify-lbl">{{ __('helpdesk::helpdesk.inbox.modals.assign_notify_label') }}</div>
                <div class="asgn-notify-opts">
                    <label class="asgn-notify-opt on">
                        <input type="checkbox" id="asgn-notify-email" checked>
                        <i class="far fa-envelope"></i>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.assign_notify_email') }}</span>
                    </label>
                    <label class="asgn-notify-opt on">
                        <input type="checkbox" id="asgn-notify-push" checked>
                        <i class="far fa-bell"></i>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.assign_notify_push') }}</span>
                    </label>
                    <label class="asgn-notify-opt">
                        <input type="checkbox" id="asgn-notify-wa">
                        <i class="fab fa-whatsapp"></i>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.assign_notify_whatsapp') }}</span>
                    </label>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="assign-btn-apply">{{ __('helpdesk::helpdesk.inbox.modals.assign_title') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    $(document).on('click', '[data-bv-modal-name="assign"] .asgn-item', function () {
        $(this).closest('#assign-unified-list').find('.asgn-item').removeClass('on');
        $(this).addClass('on');
    });

    $(document).on('change', '[data-bv-modal-name="assign"] .asgn-notify-opt input', function () {
        $(this).closest('.asgn-notify-opt').toggleClass('on', this.checked);
    });

    $(document).on('input', '#assign-search', function () {
        var q = $(this).val().toLowerCase();
        $('#assign-unified-list .asgn-item').each(function () {
            $(this).toggle(!q || $(this).find('.asgn-t').text().toLowerCase().includes(q));
        });
        $('#assign-unified-list .asgn-sec-lbl').each(function () {
            var $lbl = $(this);
            var $items = $lbl.nextUntil('.asgn-sec-lbl', '.asgn-item');
            $lbl.toggle(!q || $items.filter(':visible').length > 0);
        });
    });
}());
</script>
@endpush
@endonce
