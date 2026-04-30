{{-- Variables: $conversation, $acceptAttribute --}}
<div class="chat-send-message-footer chat-active flex-shrink-0 border-top">
    {{-- Zona de previsualizacion de adjuntos --}}
    <div id="attachment-previews-{{ $conversation->id }}" class="attachment-previews d-none px-9 pt-3 d-flex flex-wrap gap-2"></div>

    {{-- Input de archivo oculto --}}
    <input type="file"
           id="file-input-{{ $conversation->id }}"
           class="d-none"
           multiple
           accept="{{ $acceptAttribute }}">

    {{-- Hint de plantillas --}}
    <div class="px-9 pt-2 pb-0">
        <small class="text-muted" style="font-size: 0.72rem;">
            <i class="fas fa-bolt me-1" style="color:#b10100;"></i>
            Escribe <kbd class="py-0 px-1" style="font-size:0.7rem;">/</kbd> para insertar una plantilla &middot; <kbd class="py-0 px-1" style="font-size:0.7rem;">Shift+Enter</kbd> para nueva linea
        </small>
    </div>

    <div class="d-flex align-items-end gap-2 px-9 py-3">
        <textarea class="form-control message-type-box border-0 p-0 ms-1 flex-grow-1"
                  placeholder="Escribe un mensaje..."
                  id="message-input-{{ $conversation->id }}"
                  rows="1"
                  style="resize: none; overflow-y: hidden; min-height: 24px; max-height: 120px; line-height: 1.5; box-shadow: none; background: transparent;"></textarea>

        <ul class="list-unstyled mb-0 d-flex align-items-center gap-1 flex-shrink-0">
            <li class="position-relative">
                <a class="chat-action-btn emoji-picker-toggle"
                   href="javascript:void(0)"
                   data-input="message-input-{{ $conversation->id }}"
                   data-bs-toggle="tooltip"
                   title="Emojis">
                    <i class="fas fa-smile"></i>
                </a>
                <div class="emoji-picker d-none" id="emoji-picker-{{ $conversation->id }}">
                    <div class="emoji-picker-header border-bottom pb-2 mb-2">
                        <small class="text-muted fw-semibold">Sonrisas</small>
                    </div>
                    <div class="emoji-grid mb-2">
                        @foreach(['😀','😁','😂','😃','😄','😅','😆','😇','😈','😉','😊','😋','😌','😍','😎','😏','😐'] as $emoji)
                            <span class="emoji-item" role="button" tabindex="0">{{ $emoji }}</span>
                        @endforeach
                    </div>
                    <div class="emoji-picker-header border-bottom pb-2 mb-2">
                        <small class="text-muted fw-semibold">Manos</small>
                    </div>
                    <div class="emoji-grid mb-2">
                        @foreach(['👋','👍','👎','👌','👏','🤝','💪'] as $emoji)
                            <span class="emoji-item" role="button" tabindex="0">{{ $emoji }}</span>
                        @endforeach
                    </div>
                    <div class="emoji-picker-header border-bottom pb-2 mb-2">
                        <small class="text-muted fw-semibold">Corazones</small>
                    </div>
                    <div class="emoji-grid">
                        @foreach(['❤️','🧡','💛','💚','💙','💜','🖤'] as $emoji)
                            <span class="emoji-item" role="button" tabindex="0">{{ $emoji }}</span>
                        @endforeach
                    </div>
                </div>
            </li>
            <li>
                <a class="chat-action-btn"
                   href="javascript:void(0)"
                   onclick="$('#file-input-{{ $conversation->id }}').trigger('click')"
                   data-bs-toggle="tooltip"
                   title="Adjuntar archivo">
                    <i class="fas fa-paperclip"></i>
                </a>
            </li>
            <li>
                <a class="chat-send-btn"
                   href="javascript:void(0)"
                   id="send-btn-{{ $conversation->id }}"
                   onclick="sendMessage({{ $conversation->id }})"
                   data-bs-toggle="tooltip"
                   title="Enviar mensaje"
                   aria-label="Enviar mensaje">
                    <i class="fas fa-paper-plane"></i>
                </a>
            </li>
        </ul>
    </div>
</div>
