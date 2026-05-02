@extends('helpdesk::portal.layouts.base')

@section('title', $conversation->subject ?? 'Conversación #'.$conversation->id)

@section('content')
    {{-- Back link + header --}}
    <div class="mb-3">
        <a href="{{ route('helpdesk.portal.dashboard') }}" class="text-decoration-none text-muted small">
            <i class="fas fa-arrow-left me-1"></i>Volver a mis conversaciones
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-start gap-3">
                {{-- Channel icon --}}
                @php $channelInfo = $conversation->channel_info @endphp
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:44px;height:44px;background:#f0f0f0;">
                    <i class="{{ $channelInfo['icon'] }} fa-lg" style="color:{{ $channelInfo['color'] }};"></i>
                </div>

                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1">
                        {{ $conversation->subject ?? 'Conversación #'.$conversation->id }}
                    </h5>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($conversation->status)
                            <span class="badge rounded-pill"
                                  style="background-color: {{ $conversation->status->is_open ? '#13C672' : '#6c757d' }}">
                                {{ $conversation->status->name }}
                            </span>
                        @endif
                        <small class="text-muted">
                            <i class="far fa-clock me-1"></i>
                            Iniciada {{ $conversation->created_at->diffForHumans() }}
                        </small>
                        @if ($conversation->inbox)
                            <small class="text-muted">
                                <i class="fas fa-inbox me-1"></i>{{ $conversation->inbox->name }}
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Message thread --}}
        <div class="card-body p-0">
            <div class="message-thread p-3 d-flex flex-column gap-3" id="messageThread">
                @forelse ($conversation->items as $item)
                    @if ($item->isEvent())
                        {{-- System event --}}
                        <div class="d-flex justify-content-center">
                            <div class="message-bubble event-item">
                                <i class="fas fa-circle-info me-1"></i>
                                {{ $item->event_label }} — {{ $item->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @elseif ($item->is_internal)
                        {{-- Internal note: hidden from customer --}}
                        @continue
                    @elseif ($item->isFromCustomer())
                        {{-- Customer message (right-aligned) --}}
                        <div class="d-flex justify-content-end align-items-end gap-2">
                            <div class="d-flex flex-column align-items-end gap-1">
                                <div class="message-bubble from-customer">
                                    {!! nl2br(e($item->body)) !!}
                                    @if ($item->hasAttachments())
                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                            @foreach ($item->attachment_urls as $url)
                                                <a href="{{ $url }}" target="_blank"
                                                   class="btn btn-sm btn-light text-dark">
                                                    <i class="fas fa-paperclip me-1"></i>Adjunto
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <small class="text-muted px-1">
                                    Tú · {{ $item->created_at->format('d/m H:i') }}
                                </small>
                            </div>
                            <div class="avatar-initials flex-shrink-0"
                                 style="background:#b10100;color:#fff;">
                                {{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}
                            </div>
                        </div>
                    @else
                        {{-- Agent message (left-aligned) --}}
                        <div class="d-flex justify-content-start align-items-end gap-2">
                            <div class="avatar-initials flex-shrink-0"
                                 style="background:#e9ecef;color:#495057;">
                                <i class="fas fa-headset" style="font-size:.8rem;"></i>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <div class="message-bubble from-agent">
                                    {!! nl2br(e($item->body)) !!}
                                    @if ($item->hasAttachments())
                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                            @foreach ($item->attachment_urls as $url)
                                                <a href="{{ $url }}" target="_blank"
                                                   class="btn btn-sm btn-outline-secondary btn-sm">
                                                    <i class="fas fa-paperclip me-1"></i>Adjunto
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <small class="text-muted px-1">
                                    Soporte · {{ $item->created_at->format('d/m H:i') }}
                                </small>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="far fa-comment-dots fa-2x mb-2 d-block"></i>
                        Aún no hay mensajes en esta conversación.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Reply form --}}
        <div class="card-footer bg-white border-top p-3">
            @if ($conversation->status && $conversation->status->is_open)
                <form method="POST"
                      action="{{ route('helpdesk.portal.conversation.message', $conversation->id) }}"
                      id="replyForm">
                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-danger alert-sm py-2 mb-2 small">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $errors->first() }}
                        </div>
                    @endif
                    <div class="mb-2">
                        <textarea
                            name="message"
                            id="messageInput"
                            class="form-control @error('message') is-invalid @enderror"
                            rows="3"
                            placeholder="Escribe tu respuesta aquí..."
                            required
                            maxlength="5000"
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted" id="charCount">0 / 5000</small>
                        <button type="submit" class="btn btn-primary-portal px-4">
                            <i class="far fa-paper-plane me-2"></i>Enviar respuesta
                        </button>
                    </div>
                </form>
            @else
                <div class="text-center text-muted py-2 small">
                    <i class="fas fa-lock me-1"></i>
                    Esta conversación está cerrada. No puedes enviar más mensajes.
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Auto-scroll thread to bottom
    const thread = document.getElementById('messageThread');
    if (thread) {
        thread.scrollTop = thread.scrollHeight;
    }

    // Character counter
    const input = document.getElementById('messageInput');
    const counter = document.getElementById('charCount');
    if (input && counter) {
        input.addEventListener('input', function () {
            counter.textContent = this.value.length + ' / 5000';
        });
    }
</script>
@endsection
