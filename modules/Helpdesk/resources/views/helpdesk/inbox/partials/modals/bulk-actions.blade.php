{{-- Modal: Acciones masivas (#58 ve-bulk-actions) --}}
<div class="bv-modal" data-bv-modal-name="bulk-actions">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-list-check"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_title') }} <span class="bv-chip-id" id="bulkActionsCount">0</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-warn-box bv-warn-box--info bv-x21">
                <div class="bv-warn-box__body">
                    {{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_notice_1') }} <strong id="bulkActionsCountText">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_default_count') }}</strong> {{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_notice_2') }}
                </div>
            </div>

            <div class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_available_label') }}</div>
            <div class="bv-bulk-grid">
                <button class="bv-bulk-act" data-bulk-action="assign">
                    <i class="fas fa-user-check"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_assign') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="priority">
                    <i class="fas fa-flag"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_priority') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="resolve">
                    <i class="fas fa-check"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_resolve') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="close">
                    <i class="fas fa-xmark"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_close') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="archive">
                    <i class="fas fa-box-archive"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_archive') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="team">
                    <i class="fas fa-people-group"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_team') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="tag">
                    <i class="fas fa-tag"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_tag') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="snooze">
                    <i class="far fa-clock"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_snooze') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="mute">
                    <i class="fas fa-bell-slash"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_mute') }}</span>
                </button>
            </div>

            {{-- Sub-panel condicional por acción --}}
            <div id="bulkSubPanel" style="display:none;margin-top:14px">

                {{-- Assign sub-panel --}}
                <div id="bulkSubAssign" class="bv-bulk-sub" style="display:none">
                    <div class="bv-modal-search">
                        <i class="fas fa-magnifying-glass"></i>
                        <input id="bulkAssignSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_assign_search_placeholder') }}">
                    </div>
                    <div id="bulkAssignList" class="asgn-list">
                        <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                </div>

                {{-- Priority sub-panel --}}
                <div id="bulkSubPriority" class="bv-bulk-sub" style="display:none">
                    <div class="bv-opt-list">
                        <button class="bv-opt" data-bv-value="low"><div class="bv-opt__ic"><i class="fas fa-chevron-down"></i></div><div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_priority_low') }}</span></div></button>
                        <button class="bv-opt on" data-bv-value="normal"><div class="bv-opt__ic"><i class="fas fa-minus"></i></div><div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_priority_normal') }}</span></div></button>
                        <button class="bv-opt" data-bv-value="high"><div class="bv-opt__ic"><i class="fas fa-chevron-up"></i></div><div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_priority_high') }}</span></div></button>
                        <button class="bv-opt" data-bv-value="urgent"><div class="bv-opt__ic"><i class="fas fa-angles-up"></i></div><div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_priority_urgent') }}</span></div></button>
                    </div>
                </div>

                {{-- Delete confirm sub-panel --}}
                <div id="bulkSubDelete" class="bv-bulk-sub" style="display:none">
                    <div class="bv-warn-box">
                        <div class="bv-warn-box__body">
                            <strong>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_delete_confirm_strong') }}</strong> {{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_delete_confirm_text') }}
                        </div>
                    </div>
                </div>

                {{-- Team sub-panel --}}
                <div id="bulkSubTeam" class="bv-bulk-sub" style="display:none">
                    <div class="bv-opt-list">
                        @forelse($groups ?? [] as $group)
                            <button type="button" class="bv-opt" data-bulk-group-id="{{ $group->id }}">
                                <div class="bv-av c{{ ($loop->index % 8) + 1 }}"><i class="fas fa-users bv-icon-sm"></i></div>
                                <div class="body"><div class="name">{{ $group->name }}</div></div>
                                <i class="fas fa-check check"></i>
                            </button>
                        @empty
                            <div class="bv-empty-msg">{{ __('helpdesk::helpdesk.inbox.modals.move_team_empty') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- Tag sub-panel (multi-select) --}}
                <div id="bulkSubTag" class="bv-bulk-sub" style="display:none">
                    <div class="bv-tags-chip-wrap">
                        @forelse($inboxTags ?? [] as $tag)
                            <span class="bv-rtag" data-bulk-tag-id="{{ $tag->id }}">{{ $tag->name }}</span>
                        @empty
                            <em class="bv-tags-empty">{{ __('helpdesk::helpdesk.inbox.modals.tags_none_available') }}</em>
                        @endforelse
                    </div>
                </div>

                {{-- Snooze sub-panel --}}
                <div id="bulkSubSnooze" class="bv-bulk-sub" style="display:none">
                    <div class="snz-list">
                        <button type="button" class="snz-opt on" data-bulk-snooze="1h">
                            <i class="fa-solid fa-stopwatch snz-ic"></i>
                            <div class="snz-body"><b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_1h') }}</b></div>
                        </button>
                        <button type="button" class="snz-opt" data-bulk-snooze="4h">
                            <i class="fa-regular fa-clock snz-ic"></i>
                            <div class="snz-body"><b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_4h') }}</b></div>
                        </button>
                        <button type="button" class="snz-opt" data-bulk-snooze="tom">
                            <i class="fa-solid fa-sun snz-ic"></i>
                            <div class="snz-body"><b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_tomorrow') }}</b></div>
                        </button>
                        <button type="button" class="snz-opt" data-bulk-snooze="week">
                            <i class="fa-solid fa-calendar-week snz-ic"></i>
                            <div class="snz-body"><b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_week') }}</b></div>
                        </button>
                    </div>
                </div>

            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-bulk-apply" disabled>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_apply') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/bulk-actions.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/bulk-actions.js')) }}"></script>
@endpush
@endonce
