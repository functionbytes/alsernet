{{-- Variables: $conversation, $mimeIconMap --}}
<div class="chat-box-inner p-9 flex-grow-1 overflow-y-auto" data-simplebar>
    <div class="chat-list chat active-chat" data-conversation-id="{{ $conversation->id }}">
        @forelse($conversation->messages as $message)
            @if($message->message_type === 'incoming')
                <!-- Mensaje entrante (del cliente) -->
                <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                    @if($conversation->customer)
                        @if($conversation->customer->avatar_url)
                            <img src="{{ $conversation->customer->avatar_url }}"
                                 alt="{{ $conversation->customer->name }}"
                                 width="40"
                                 height="40"
                                 class="rounded-circle">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center chat-avatar-placeholder">
                                {{ strtoupper(substr($conversation->customer->name, 0, 1)) }}
                            </div>
                        @endif
                    @endif
                    <div>
                        <h6 class="fs-2 text-muted">
                            @if($conversation->customer)
                                {{ $conversation->customer->name }},
                            @else
                                Cliente desconocido,
                            @endif
                            {{ $message->created_at->diffForHumans() }}
                        </h6>
                        <div class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                            {!! nl2br(e($message->content)) !!}
                        </div>
                        @php $incomingAttachments = $message->getMedia('attachments'); @endphp
                        @if($incomingAttachments->isNotEmpty())
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                @include('Chat::chats.conversation.partials.messages.attachment-list', [
                                    'attachments' => $incomingAttachments,
                                    'mimeIconMap' => $mimeIconMap,
                                ])
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Mensaje saliente (del agente) -->
                <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                    <div class="text-end">
                        <h6 class="fs-2 text-muted">{{ $message->created_at->diffForHumans() }}</h6>
                        <div class="p-2 bg-info-subtle text-dark rounded-1 d-inline-block fs-3">
                            {!! nl2br(e($message->content)) !!}
                        </div>
                        @php $outgoingAttachments = $message->getMedia('attachments'); @endphp
                        @if($outgoingAttachments->isNotEmpty())
                            <div class="mt-2 d-flex flex-wrap gap-2 justify-content-end">
                                @include('Chat::chats.conversation.partials.messages.attachment-list', [
                                    'attachments' => $outgoingAttachments,
                                    'mimeIconMap' => $mimeIconMap,
                                ])
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @empty
            <div class="empty-state-container">
                <div class="empty-state-content">
                    <i class="fas fa-comments empty-state-icon text-muted"></i>
                    <p class="text-muted">No hay mensajes en esta conversacion</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
