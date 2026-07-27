{{-- Modal: Filtrar conversaciones (mejorado) --}}
<div class="bv-modal" data-bv-modal-name="filter">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-filter"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.filter_eyebrow') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.filter_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Accesos rápidos --}}
            <div class="fl-section">
                <div class="fl-section-title">{{ __('helpdesk::helpdesk.inbox.modals.filter_quick_access') }}</div>
                <div class="fl-saved" data-preset="urgent">
                    <div class="ico"><i class="fas fa-fire"></i></div>
                    <div class="body">
                        <div class="t">{{ __('helpdesk::helpdesk.inbox.modals.filter_preset_urgent') }}</div>
                        <div class="s">{{ __('helpdesk::helpdesk.inbox.modals.filter_preset_urgent_desc') }}</div>
                    </div>
                    <span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
                </div>
                <div class="fl-saved" data-preset="unread">
                    <div class="ico"><i class="fas fa-envelope-open"></i></div>
                    <div class="body">
                        <div class="t">{{ __('helpdesk::helpdesk.inbox.modals.filter_preset_unread') }}</div>
                        <div class="s">{{ __('helpdesk::helpdesk.inbox.modals.filter_preset_unread_desc') }}</div>
                    </div>
                    <span class="c">{{ $sidebarCounters['unread'] ?? 0 }}</span>
                </div>
            </div>

            {{-- Canales --}}
            <div class="fl-section">
                <div class="fl-section-title">{{ __('helpdesk::helpdesk.inbox.modals.filter_channels') }}</div>
                <div class="fl-pills">
                    <button class="fl-pill" data-key="channel" data-val="whatsapp">
                        <span class="d bv-fl-dot-wa"></span>WhatsApp<span class="c">{{ $sidebarCounters['whatsapp'] ?? 0 }}</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="facebook">
                        <span class="d bv-fl-dot-fb"></span>Messenger<span class="c">{{ $sidebarCounters['facebook'] ?? 0 }}</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="instagram">
                        <span class="d bv-fl-dot-ig"></span>Instagram<span class="c">{{ $sidebarCounters['instagram'] ?? 0 }}</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="web">
                        <span class="d bv-fl-dot-widget"></span>{{ __('helpdesk::helpdesk.inbox.modals.filter_channel_web') }}<span class="c">{{ $sidebarCounters['web'] ?? 0 }}</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="email">
                        <span class="d bv-fl-dot-email"></span>Email<span class="c">{{ $sidebarCounters['email'] ?? 0 }}</span>
                    </button>
                </div>
            </div>

            <div class="bv-filter-grid">
                {{-- Estado --}}
                <div class="fl-section">
                    <div class="fl-section-title">{{ __('helpdesk::helpdesk.inbox.modals.filter_status_label') }}</div>
                    <div class="fl-pills">
                        <button class="fl-pill" data-key="status" data-val="open">{{ __('helpdesk::helpdesk.inbox.modals.filter_status_open') }}</button>
                        <button class="fl-pill" data-key="status" data-val="pending">{{ __('helpdesk::helpdesk.inbox.modals.filter_status_pending') }}<span class="c">{{ $sidebarCounters['pending'] ?? 0 }}</span></button>
                        <button class="fl-pill" data-key="status" data-val="closed">{{ __('helpdesk::helpdesk.inbox.modals.filter_status_closed') }}<span class="c">{{ $sidebarCounters['archived'] ?? 0 }}</span></button>
                    </div>
                </div>

                {{-- Prioridad --}}
                <div class="fl-section">
                    <div class="fl-section-title">{{ __('helpdesk::helpdesk.inbox.modals.filter_priority_label') }}</div>
                    <div class="fl-pills">
                        <button class="fl-pill" data-key="priority" data-val="urgent">
                            <span class="d bv-fl-dot-danger"></span>{{ __('helpdesk::helpdesk.inbox.modals.priority_urgent') }}<span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="high">
                            <span class="d bv-fl-dot-warning"></span>{{ __('helpdesk::helpdesk.inbox.modals.priority_high') }}
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="normal">
                            <span class="d bv-fl-dot-info"></span>{{ __('helpdesk::helpdesk.inbox.modals.priority_normal') }}
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="low">
                            <span class="d bv-fl-dot-success"></span>{{ __('helpdesk::helpdesk.inbox.modals.priority_low') }}
                        </button>
                    </div>
                </div>

                {{-- Asignación --}}
                <div class="fl-section">
                    <div class="fl-section-title">{{ __('helpdesk::helpdesk.inbox.modals.filter_assignment_label') }}</div>
                    <div class="fl-pills">
                        <button class="fl-pill" data-key="mine" data-val="1">{{ __('helpdesk::helpdesk.inbox.modals.filter_mine') }}<span class="c">{{ $sidebarCounters['mine'] ?? 0 }}</span></button>
                        <button class="fl-pill" data-key="assignee" data-val="unassigned">{{ __('helpdesk::helpdesk.inbox.modals.unassigned') }}<span class="c">{{ $sidebarCounters['unassigned'] ?? 0 }}</span></button>
                    </div>
                </div>

                {{-- Etiquetas --}}
                <div class="fl-section">
                    <div class="fl-section-title">{{ __('helpdesk::helpdesk.inbox.modals.filter_tags_label') }}</div>
                    <div class="fl-pills">
                        @forelse($inboxTags ?? [] as $tag)
                            <button class="fl-pill" data-key="tag" data-val="{{ $tag->id }}"
                                    style="--bv-tag-color: {{ $tag->color ?? '#6c757d' }}">
                                <span class="d" style="background:{{ $tag->color ?? '#6c757d' }}"></span>
                                {{ $tag->name }}
                                @if($tag->conversations_count)<span class="c">{{ $tag->conversations_count }}</span>@endif
                            </button>
                        @empty
                            <span class="bv-text-muted-12">{{ __('helpdesk::helpdesk.inbox.modals.filter_no_tags') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Rango de fechas --}}
            <div class="fl-section bv-filter-section-last">
                <div class="fl-section-title">{{ __('helpdesk::helpdesk.inbox.modals.date') }}</div>
                <div class="fl-pills bv-fl-date-pills">
                    <button class="fl-pill on" data-key="date" data-val="today">{{ __('helpdesk::helpdesk.inbox.modals.filter_date_today') }}</button>
                    <button class="fl-pill" data-key="date" data-val="yesterday">{{ __('helpdesk::helpdesk.inbox.modals.filter_date_yesterday') }}</button>
                    <button class="fl-pill" data-key="date" data-val="7d">{{ __('helpdesk::helpdesk.inbox.modals.filter_date_last7') }}</button>
                    <button class="fl-pill" data-key="date" data-val="30d">{{ __('helpdesk::helpdesk.inbox.modals.filter_date_last30') }}</button>
                    <button class="fl-pill" data-key="date" data-val="custom">{{ __('helpdesk::helpdesk.inbox.modals.filter_date_custom') }}</button>
                </div>
                <div class="fl-range">
                    <input type="date" id="flDateFrom" value="{{ date('Y-m-01') }}">
                    <span class="bv-date-arrow">→</span>
                    <input type="date" id="flDateTo" value="{{ date('Y-m-d') }}">
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <span id="flActiveCount" class="bv-filter-count">{{ __('helpdesk::helpdesk.inbox.modals.filter_none_active') }}</span>
            <button class="btn-primary" id="flBtnApply">{{ __('helpdesk::helpdesk.inbox.modals.filter_apply') }}</button>
            <button class="btn-secondary" id="flBtnSaveView">{{ __('helpdesk::helpdesk.inbox.modals.filter_save_view') }}</button>
            <button class="btn-secondary" id="flBtnClear">{{ __('helpdesk::helpdesk.inbox.modals.filter_clear') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/filter.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/filter.js')) }}"></script>
@endpush
@endonce
