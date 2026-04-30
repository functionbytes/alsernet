{{-- Attachment config provided by ConversationController via AttachmentConfigService --}}
<div class="chatting-box d-block h-100 d-flex flex-column">
    <!-- Chat Header - Customer Info and Quick Actions -->
    @include('Chat::chats.conversation.partials.headers.chat-header', compact('conversation'))

    <!-- Chat Content Area -->
    <div class="d-flex parent-chat-box flex-grow-1 min-h-0">
        <div class="chat-box w-xs-100 d-flex flex-column">
            <!-- Messages Container -->
            <div class="chat-box-inner p-9 flex-grow-1 overflow-y-auto" data-simplebar>
                <div class="chat-list chat active-chat " data-conversation-id="{{ $conversation->id }}">
                    @include('Chat::chats.conversation.partials.messages.message-list', compact('conversation', 'mimeIconMap'))
                </div>
            </div>

            <!-- Message Input Footer -->
            @include('Chat::chats.conversation.partials.messages.message-input', compact('conversation', 'attachEnabled', 'acceptAttr', 'attachMaxMb', 'attachMaxCount', 'attachTypes'))
        </div>

        <!-- Right Sidebar - Contact Details -->
        @include('Chat::chats.conversation.partials.layout.sidebar-right', compact('conversation', 'statuses', 'teams', 'users', 'priorities', 'labelsByTitle', 'macros'))
    </div>
</div>

<!-- Attachment Viewer Modal -->
@include('Chat::chats.conversation.partials.modals.attachment-lightbox')

<!-- Email Transcript Modal -->
@include('Chat::chats.conversation.partials.modals.email-transcript', compact('conversation'))

{{-- Configuración global para los módulos JS --}}
<script>
    window.ChatConfig = {
        conversationId:    {{ $conversation->id }},
        csrfToken:         document.querySelector('meta[name="csrf-token"]').content,
        attachmentEnabled: {{ $attachmentConfig['enabled'] ? 'true' : 'false' }},
        allowedTypes:      {!! json_encode($attachmentConfig['allowedTypes']) !!},
        maxFileSize:       {{ $attachmentConfig['maxFileSizeMb'] }} * 1024 * 1024,
        maxAttachCount:    {{ $attachmentConfig['maxAttachments'] }},
        fileIconMap:       {!! json_encode($mimeIconMap) !!},
        acceptAttribute:   '{{ $acceptAttribute }}',
        baseUrl:           '{{ url("/chat/conversations") }}'
    };

    // Alias globales para atributos onclick inline en Blade (disponibles antes de cargar módulos)
    function openAttachmentViewer(url, filename, mimeType) {
        window.ChatViewer && window.ChatViewer.openViewer(url, filename, mimeType);
    }
    function sendMessage(convId) {
        window.ChatSender && window.ChatSender.sendMessage(convId);
    }
    function executeMacro() {
        window.ChatActions && window.ChatActions.executeMacro();
    }

</script>

{{-- Módulos JS del chat (orden de dependencias) --}}
<script>
// Load chat modules sequentially and wait for all to complete
(function() {
    var retries = 0;
    var maxRetries = 50;

    function loadScript(src) {
        return new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
        });
    }

    function loadChatModules() {
        if (typeof jQuery === 'undefined' && retries < maxRetries) {
            retries++;
            setTimeout(loadChatModules, 50);
            return;
        }

        if (typeof jQuery !== 'undefined') {
            var scripts = [
                '{{ asset('chat-module/js/chat/core/performance-monitor.js') }}',
                '{{ asset('chat-module/js/chat/core/message-utils.js') }}',
                '{{ asset('chat-module/js/chat/ui/attachments.js') }}',
                '{{ asset('chat-module/js/chat/messaging/message-sender.js') }}',
                '{{ asset('chat-module/js/chat/ui/conversation-actions.js') }}',
                '{{ asset('chat-module/js/chat/messaging/realtime-messaging.js') }}',
                '{{ asset('chat-module/js/chat/messaging/message-template.js') }}'
            ];

            // Load all scripts and then initialize
            Promise.all(scripts.map(loadScript)).then(function() {
                // All scripts loaded, initialize
                initializeChatModules();
            }).catch(function(err) {
                console.error('Error loading chat modules:', err);
            });
        }
    }

    function initializeChatModules() {
        // Scroll al último mensaje al cargar (compatible con SimpleBar)
        var chatBoxEl = document.querySelector('.chat-box-inner[data-simplebar]');
        if (chatBoxEl) {
            var sbScroll = chatBoxEl._simplebar
                ? chatBoxEl._simplebar.getScrollElement()
                : chatBoxEl;
            sbScroll.scrollTop = sbScroll.scrollHeight;
        }

        // Tooltips Bootstrap (opt-in en BS5)
        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });

        // Selects laterales y emoji picker
        if (window.ChatActions) {
            window.ChatActions.initSidebarSelects();
            window.ChatActions.initEmojiPicker();
        }

        // Suscripción en tiempo real (Reverb / Laravel Echo)
        if (window.ChatRealtime) {
            window.ChatRealtime.initEchoSubscription(window.ChatConfig.conversationId);
        }
    }

    loadChatModules();
})();
</script>

