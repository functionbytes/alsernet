{{-- Modal: Guía de menciones (user-facing) --}}
<div class="bv-modal" data-bv-modal-name="mention-help">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-circle-question"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.mention_help_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.mention_help_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close type="button"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <p class="bv-help-intro">
                {{ __('helpdesk::helpdesk.inbox.modals.mention_help_intro_1') }} <code>@</code> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_intro_2') }}
                {{ __('helpdesk::helpdesk.inbox.modals.mention_help_intro_3') }}
            </p>

            {{-- Sintaxis --}}
            <div class="bv-help-section">
                <div class="bv-help-section-title">{{ __('helpdesk::helpdesk.inbox.modals.mention_help_section_syntax') }}</div>
                <table class="bv-help-table">
                    <tbody>
                        <tr>
                            <td><code>@nombre.apellido</code></td>
                            <td>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_syntax_agent') }}</td>
                        </tr>
                        <tr>
                            <td><code>@team_key</code></td>
                            <td>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_syntax_team') }}</td>
                        </tr>
                        <tr>
                            <td><code>@all</code></td>
                            <td>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_syntax_all') }}</td>
                        </tr>
                        <tr>
                            <td><code>@here</code></td>
                            <td>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_syntax_here_1') }} <strong>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_syntax_here_2') }}</strong></td>
                        </tr>
                        <tr>
                            <td><code>@team</code></td>
                            <td>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_syntax_conversation_team') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Cómo funciona --}}
            <div class="bv-help-section">
                <div class="bv-help-section-title">{{ __('helpdesk::helpdesk.inbox.modals.mention_help_section_how_it_works') }}</div>
                <ul class="bv-help-list">
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_how_1') }}</li>
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_how_2_1') }} <span class="bv-mention-chip">@ejemplo</span> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_how_2_2') }}</li>
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_how_3') }}</li>
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_how_4_1') }} <code>@handles</code> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_how_4_2') }}</li>
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_how_5') }}</li>
                </ul>
            </div>

            {{-- Canales --}}
            <div class="bv-help-section">
                <div class="bv-help-section-title">{{ __('helpdesk::helpdesk.inbox.modals.mention_help_section_channels') }}</div>
                <ul class="bv-help-list">
                    <li><strong>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_channel_bell_title') }}</strong> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_channel_bell_desc') }}</li>
                    <li><strong>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_channel_realtime_title') }}</strong> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_channel_realtime_desc') }}</li>
                    <li><strong>Email</strong> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_channel_email_desc') }}</li>
                </ul>
            </div>

            {{-- Atajos --}}
            <div class="bv-help-section">
                <div class="bv-help-section-title">{{ __('helpdesk::helpdesk.inbox.modals.mention_help_section_shortcuts') }}</div>
                <ul class="bv-help-shortcuts">
                    <li><kbd>@</kbd> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_shortcut_at') }}</li>
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mention_help_shortcut_button_1') }} <kbd><i class="fas fa-at"></i></kbd> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_shortcut_button_2') }}</li>
                    <li><kbd>↑</kbd> <kbd>↓</kbd> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_shortcut_nav_1') }} <kbd>Enter</kbd> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_shortcut_nav_2') }}</li>
                    <li><kbd>Esc</kbd> {{ __('helpdesk::helpdesk.inbox.modals.mention_help_shortcut_esc') }}</li>
                </ul>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" data-bv-close type="button">{{ __('helpdesk::helpdesk.inbox.modals.mention_help_understood') }}</button>
        </div>
    </div>
</div>
