{{-- Modal: Mover conversación a equipo --}}
<div class="bv-modal" data-bv-modal-name="move-to-team">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-right-left"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.label_conversation') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.move_team_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            {{-- Search --}}
            <div class="bv-modal-search bv-mb-12">
                <i class="fas fa-magnifying-glass"></i>
                <input id="move-team-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.move_team_search_placeholder') }}" autocomplete="off">
            </div>

            {{-- Group list --}}
            <div class="bv-opt-list" id="move-team-list">
                @forelse($groups ?? [] as $group)
                    <button class="bv-opt" data-group-id="{{ $group->id }}">
                        <div class="bv-av c{{ ($loop->index % 8) + 1 }}">
                            <i class="fas fa-users bv-icon-sm"></i>
                        </div>
                        <div class="body">
                            <div class="name">{{ $group->name }}</div>
                        </div>
                        <i class="fas fa-check check"></i>
                    </button>
                @empty
                    <div class="bv-empty-msg">{{ __('helpdesk::helpdesk.inbox.modals.move_team_empty') }}</div>
                @endforelse
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="move-team-btn">{{ __('helpdesk::helpdesk.inbox.modals.move_team_apply') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/move-to-team.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/move-to-team.js')) }}" defer></script>
@endpush
@endonce
