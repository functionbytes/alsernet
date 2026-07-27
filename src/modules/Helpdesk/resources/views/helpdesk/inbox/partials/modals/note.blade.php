{{-- Modal: Notas internas --}}
{{-- data-agent-*: identidad del agente por atributo, para que el JS no necesite Blade.
     Se lee full_name, no name: la tabla users no tiene columna "name" (guarda
     firstname/lastname y el modelo expone el accessor full_name), asi que el
     ->name de antes era siempre null y el modal mostraba el literal "Agente"
     con inicial "A" para todo el mundo. --}}
@php($hdAgentName = trim(auth()->user()?->full_name ?? '') ?: 'Agente')
<div class="bv-modal" data-bv-modal-name="note"
     data-agent-name="{{ $hdAgentName }}"
     data-agent-initials="{{ collect(preg_split('/\s+/', $hdAgentName))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode('') }}">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-note-sticky bv-modal-title-icon bv-modal-title-icon--warning"></i> {{ __('helpdesk::helpdesk.inbox.modals.note_title') }}</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Compose --}}
            <div class="mv4-note-compose">
                <div class="head">
                    <div class="av c1">ML</div>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.note_compose_as') }} <b>María López</b></span>
                    <span class="bv-flex-spacer"></span>
                    <label class="pin">
                        <input type="checkbox" id="notePin"> <i class="fas fa-thumbtack"></i> {{ __('helpdesk::helpdesk.inbox.modals.note_pin_label') }}
                    </label>
                </div>
                <textarea id="noteBody" rows="3" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.note_body_placeholder') }}"></textarea>
                <div class="tools">
                    <button class="tt" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.note_tool_mention_tooltip') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.note_tool_mention_tooltip') }}"><i class="fas fa-at"></i></button>
                    <button class="tt" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.note_tool_tag_tooltip') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.note_tool_tag_tooltip') }}"><i class="fas fa-tag"></i></button>
                    <button class="tt" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.note_tool_attach_tooltip') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.note_tool_attach_tooltip') }}"><i class="fas fa-paperclip"></i></button>
                    <button class="tt" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.note_tool_link_tooltip') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.note_tool_link_tooltip') }}"><i class="fas fa-link"></i></button>
                    <span class="bv-note-team-tag">{{ __('helpdesk::helpdesk.inbox.modals.note_team_only_tag') }}</span>
                </div>
            </div>

            {{-- Notas existentes --}}
            <div class="mv4-sec-title bv-mt-18 bv-step-hidden" id="noteListTitle">{{ __('helpdesk::helpdesk.inbox.modals.note_existing_notes_title') }}</div>
            <div class="mv4-notes-list" id="noteList">
                <div class="bv-list-state">{{ __('helpdesk::helpdesk.inbox.modals.note_loading') }}</div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="noteBtnSave">{{ __('helpdesk::helpdesk.inbox.modals.note_add') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.note_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/note.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/note.js')) }}"></script>
@endpush
@endonce
