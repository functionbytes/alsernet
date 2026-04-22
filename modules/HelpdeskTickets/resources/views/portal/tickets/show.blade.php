@extends('helpdesk::portal.layout')

@section('content')
    <div class="mb-3">
        <a href="{{ route('portal.tickets') }}" class="text-muted">
            <i class="fas fa-arrow-left me-1"></i>Back to my tickets
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="card-title mb-1">{{ $ticket->subject }}</h5>
                    <p class="text-muted mb-0">
                        Ticket <strong>{{ $ticket->ticket_number }}</strong>
                        &middot; Opened {{ $ticket->created_at->format('d M Y H:i') }}
                    </p>
                </div>
                <div>
                    @if ($ticket->status)
                        <span
                            class="badge fs-6"
                            style="background-color: {{ $ticket->status->color ?? '#6c757d' }}"
                        >
                            {{ $ticket->status->name }}
                        </span>
                    @endif
                </div>
            </div>
            @if ($ticket->category)
                <p class="text-muted mt-2 mb-0">
                    <i class="fas fa-tag me-1"></i>{{ $ticket->category->name }}
                </p>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-1"></i>{{ session('status') }}
        </div>
    @endif

    @if ($attachments->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>Attachments</h6></div>
            <ul class="list-group list-group-flush">
                @foreach ($attachments as $attachment)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file me-2"></i>{{ $attachment->original_filename ?? $attachment->filename }}</span>
                        <small class="text-muted">{{ number_format($attachment->size / 1024, 1) }} KB</small>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Messages thread --}}
    <h6 class="text-muted mb-3">
        <i class="fas fa-comments me-1"></i>Conversation
    </h6>

    @forelse ($messages as $message)
        @php $isCustomer = $message->isFromCustomer(); @endphp
        <div class="ticket-message {{ $isCustomer ? 'from-customer' : 'from-agent' }} mb-3">
            <div class="d-flex justify-content-between mb-1">
                <strong class="small">
                    @if ($isCustomer)
                        <i class="fas fa-user me-1 text-success"></i>{{ $customer->name }} (you)
                    @else
                        <i class="fas fa-headset me-1 text-primary"></i>{{ $message->user?->name ?? 'Support agent' }}
                    @endif
                </strong>
                <span class="text-muted">{{ $message->created_at->format('d M Y H:i') }}</span>
            </div>
            <p class="mb-0" style="white-space: pre-line;">{{ $message->body }}</p>
        </div>
    @empty
        <p class="text-muted">No messages yet.</p>
    @endforelse

    {{-- Reply form --}}
    @if ($ticket->isOpen())
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-reply me-1"></i>Send a reply
                </h6>
                <form action="{{ route('portal.tickets.reply', $ticket->ticket_number) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <textarea
                            name="message"
                            class="form-control @error('message') is-invalid @enderror"
                            rows="5"
                            placeholder="Describe your issue or add more details..."
                            required
                            maxlength="5000"
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attachments <span class="text-muted">(optional, max 5MB each)</span></label>
                        <input type="file" name="attachments[]" class="form-control @error('attachments.*') is-invalid @enderror" multiple accept="image/*,.pdf,.doc,.docx,.txt,.zip">
                        <div class="form-text">Allowed: images, PDF, Word, text, ZIP. Max 5MB per file.</div>
                        @error('attachments.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send reply
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-secondary mt-4">
            <i class="fas fa-lock me-1"></i>This ticket is closed. <a href="{{ route('portal.tickets.create') }}">Open a new ticket</a> if you need further help.
        </div>
    @endif

    @if ($ticket->closed_at && !$ticket->rated_at)
        <div class="card mt-3">
            <div class="card-body">
                <h6>Rate this support experience</h6>
                <form method="POST" action="{{ route('portal.tickets.rate', $ticket->ticket_number) }}">
                    @csrf
                    <div class="mb-3">
                        @for ($i = 1; $i <= 5; $i++)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" value="{{ $i }}" id="star{{ $i }}">
                                <label class="form-check-label" for="star{{ $i }}">{{ $i }} &#9733;</label>
                            </div>
                        @endfor
                    </div>
                    <textarea name="rating_comment" class="form-control mb-2" rows="2" placeholder="Optional comment..." maxlength="500"></textarea>
                    <button type="submit" class="btn btn-sm btn-primary">Submit rating</button>
                </form>
            </div>
        </div>
    @elseif ($ticket->rated_at)
        <div class="alert alert-success mt-3">You rated this ticket {{ $ticket->rating }}/5. Thank you!</div>
    @endif
@endsection
