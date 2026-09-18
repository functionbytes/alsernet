{{-- Modal: Gestionar etiquetas --}}
{{-- data-url-store: la ruta viaja por atributo, no interpolada en el bloque JS,
     para que el JS pueda vivir en un fichero propio y cachearse. --}}
<div class="bv-modal" data-bv-modal-name="tags" data-url-store="{{ route('settings.helpdesk.tags.store') }}">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-tags"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.tags_eyebrow') }}</span>
                <div class="bv-modal-title">
                    {{ __('helpdesk::helpdesk.inbox.modals.tags_title') }}
                    @if(!empty($selectedConversation))<span class="bv-chip-id">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Search / create --}}
            <div class="bv-modal-search bv-mb-10">
                <i class="fas fa-magnifying-glass"></i>
                <input id="tags-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.tags_search_placeholder') }}" autocomplete="off">
            </div>

            {{-- Applied section --}}
            <div class="bv-tags-section">
                <div class="bv-tags-section-label">{{ __('helpdesk::helpdesk.inbox.modals.tags_applied_label') }}</div>
                <div id="tags-applied" class="bv-tags-chip-wrap">
                    @forelse($selectedConversation?->conversationTags ?? [] as $tag)
                        <span class="bv-rtag bv-rtag--on" data-tag-id="{{ $tag->id }}">
                            {{ $tag->name }}<i class="fas fa-xmark bv-rtag-x tags-remove-chip"></i>
                        </span>
                    @empty
                        <em class="bv-tags-empty" id="tags-applied-empty">{{ __('helpdesk::helpdesk.inbox.modals.tags_none_applied') }}</em>
                    @endforelse
                </div>
            </div>

            {{-- Available tags --}}
            <div class="bv-tags-section">
                <div class="bv-tags-section-label">{{ __('helpdesk::helpdesk.inbox.modals.tags_available_label') }}</div>
                <div class="bv-tags-chip-wrap" id="tags-list">
                    @if(!empty($inboxTags) && $inboxTags->isNotEmpty())
                        @foreach($inboxTags as $tag)
                            <span class="bv-rtag {{ $selectedConversation && $selectedConversation->conversationTags->contains('id', $tag->id) ? 'bv-rtag--on' : '' }}"
                                  data-tag-id="{{ $tag->id }}">{{ $tag->name }}</span>
                        @endforeach
                    @else
                        <em class="bv-tags-empty">{{ __('helpdesk::helpdesk.inbox.modals.tags_none_available') }}</em>
                    @endif
                    <span class="bv-rtag bv-rtag--add" id="tags-create-chip">
                        <i class="fas fa-plus bv-x42"></i>
                        <span id="tags-create-text">{{ __('helpdesk::helpdesk.inbox.modals.tags_create_new') }}</span>
                    </span>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <div class="bv-tags-foot-hint">
                <span class="bv-kbd">⏎</span> {{ __('helpdesk::helpdesk.inbox.modals.tags_create_new') }} · <span class="bv-kbd">⌫</span> {{ __('helpdesk::helpdesk.inbox.modals.tags_remove_last') }}
            </div>
            <button class="btn-primary" id="bv-tags-apply">{{ __('helpdesk::helpdesk.inbox.modals.tags_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/tags.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/tags.js')) }}" defer></script>
@endpush
@endonce
