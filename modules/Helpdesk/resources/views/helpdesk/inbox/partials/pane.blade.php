{{--
    SPA pane: thread + right panel for the selected conversation.

    Rendered by ConversationsController@pane and swapped client-side (conversations.js
    -> loadConversationPane) when the agent opens or switches conversations, avoiding a
    full page reload. The same partials index.blade.php includes, fed the exact same
    variables (selectedConversation / selectedConversationId / composerDraft) so the
    markup is identical to a full load.

    This view does NOT extend a layout, so the partials' @push('scripts') blocks are
    collected but never emitted — the response is markup only. The client injects it
    with $.parseHTML (scripts stripped) and re-initialises the dynamic bits explicitly
    (window.bvBindConversation + the 'pane:loaded' event).
--}}
@include('helpdesk::helpdesk.inbox.partials.thread')
@include('helpdesk::helpdesk.inbox.partials.right-panel')
