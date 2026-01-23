@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <!-- Conversation Header -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="mb-0">
                            <a href="{{ route('admin.conversation.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                            Conversation #{{ $conversation->id }}
                        </h5>
                    </div>
                    <div>
                        @if($conversation->status === 'open')
                            <span class="conversation-status-badge badge bg-warning text-dark">Open</span>
                        @elseif($conversation->status === 'resolved')
                            <span class="conversation-status-badge badge bg-success">Resolved</span>
                        @elseif($conversation->status === 'pending')
                            <span class="conversation-status-badge badge bg-primary">Pending</span>
                        @elseif($conversation->status === 'snoozed')
                            <span class="conversation-status-badge badge bg-info">Snoozed</span>
                        @else
                            <span class="conversation-status-badge badge bg-secondary">{{ ucfirst($conversation->status) }}</span>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="d-flex gap-2 flex-wrap">
                    @if($conversation->status !== 'open')
                        <form action="{{ route('admin.conversation.updateStatus', $conversation) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="open">
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-folder2-open"></i> Reabrir
                            </button>
                        </form>
                    @endif

                    @if($conversation->status !== 'resolved')
                        <form action="{{ route('admin.conversation.updateStatus', $conversation) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="resolved">
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-check-circle"></i> Resolver
                            </button>
                        </form>
                    @endif

                    @if($conversation->status !== 'pending')
                        <form action="{{ route('admin.conversation.updateStatus', $conversation) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="pending">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-hourglass-split"></i> Pendiente
                            </button>
                        </form>
                    @endif

                    @if(!$conversation->assignee_id || $conversation->assignee_id !== auth()->id())
                        <form action="{{ route('admin.conversation.assign', $conversation) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="assignee_id" value="{{ auth()->id() }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person-check"></i> Asignarme
                            </button>
                        </form>
                    @endif

                    <!-- Export Options -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.conversation.exportPdf', $conversation) }}" target="_blank">
                                    <i class="bi bi-file-pdf text-danger"></i> Exportar PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.conversation.print', $conversation) }}" target="_blank">
                                    <i class="bi bi-printer text-secondary"></i> Vista de impresión
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#emailTranscriptModal">
                                    <i class="bi bi-envelope text-primary"></i> Enviar por email
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="card">
            <div class="card-body p-0">
                <div id="messages-container" class="messages-container p-3"
                     data-conversation-id="{{ $conversation->id }}"
                     data-account-id="{{ $conversation->account_id }}">
                    <div id="messages-list">
                    @forelse($conversation->messages as $message)
                        @if($message->message_type === 'activity')
                            <!-- Activity Message -->
                            <div class="activity-message text-center my-3">
                                <div class="d-inline-block bg-light px-3 py-2 rounded-pill">
                                    <i class="bi bi-info-circle text-muted me-1"></i>
                                    <small class="text-muted">{{ $message->content }}</small>
                                    <small class="text-muted ms-2">• {{ $message->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @elseif($message->isFromUser())
                            <!-- Outgoing Message (from agent) -->
                            <div class="message-bubble outgoing mb-3 {{ $message->private ? 'private-note-bubble' : '' }}">
                                <div class="d-flex justify-content-end">
                                    <div class="message-content {{ $message->private ? 'bg-warning text-dark border border-warning' : 'bg-primary text-white' }} p-3 rounded">
                                        @if($message->private)
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-lock-fill me-2"></i>
                                                <strong>Private Note</strong>
                                                <span class="badge bg-dark ms-2" style="font-size: 0.7rem;">Only visible to agents</span>
                                            </div>
                                        @endif
                                        @if($message->hasAttachments())
                                            @foreach($message->getMedia('attachments') as $media)
                                                <div class="mb-2">
                                                    @if(str_starts_with($media->mime_type, 'image/'))
                                                        <img src="{{ $media->getUrl() }}" class="img-fluid rounded" style="max-width: 300px;" alt="{{ $media->name }}">
                                                    @elseif(str_starts_with($media->mime_type, 'audio/'))
                                                        <audio controls class="w-100">
                                                            <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
                                                        </audio>
                                                    @elseif(str_starts_with($media->mime_type, 'video/'))
                                                        <video controls class="w-100 rounded" style="max-width: 300px;">
                                                            <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
                                                        </video>
                                                    @else
                                                        <a href="{{ $media->getUrl() }}" target="_blank" class="{{ $message->private ? 'text-dark' : 'text-white' }} text-decoration-underline">
                                                            <i class="bi bi-file-earmark"></i> {{ $media->file_name }}
                                                        </a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                        @if($message->content)
                                            {{ $message->content }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end mt-1">
                                    <small class="text-muted">
                                        {{ $message->sender->name }} • {{ $message->created_at->format('M d, H:i') }}
                                    </small>
                                </div>
                            </div>
                        @else
                            <!-- Incoming Message (from contact) -->
                            <div class="message-bubble incoming mb-3">
                                <div class="d-flex justify-content-start">
                                    <div class="message-content bg-light p-3 rounded">
                                        @if($message->hasAttachments())
                                            @foreach($message->getMedia('attachments') as $media)
                                                <div class="mb-2">
                                                    @if(str_starts_with($media->mime_type, 'image/'))
                                                        <img src="{{ $media->getUrl() }}" class="img-fluid rounded" style="max-width: 300px;" alt="{{ $media->name }}">
                                                    @elseif(str_starts_with($media->mime_type, 'audio/'))
                                                        <audio controls class="w-100">
                                                            <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
                                                        </audio>
                                                    @elseif(str_starts_with($media->mime_type, 'video/'))
                                                        <video controls class="w-100 rounded" style="max-width: 300px;">
                                                            <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
                                                        </video>
                                                    @else
                                                        <a href="{{ $media->getUrl() }}" target="_blank" class="text-dark text-decoration-underline">
                                                            <i class="bi bi-file-earmark"></i> {{ $media->file_name }}
                                                        </a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                        @if($message->content)
                                            {{ $message->content }}
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        {{ $message->sender->name }} • {{ $message->created_at->format('M d, H:i') }}
                                    </small>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-chat-dots fs-1 d-block mb-2"></i>
                            <p>No messages yet</p>
                        </div>
                    @endforelse
                    </div>

                    <!-- Typing Indicator -->
                    <div id="typing-indicator" class="typing-indicator px-3 pb-2" style="display: none;">
                        <div class="d-flex align-items-center">
                            <div class="typing-dots">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <small class="text-muted ms-2" id="typing-user-name"></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="card-footer bg-white border-top p-3">
                <form id="message-form" action="{{ route('admin.conversation.messages.store', $conversation) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Canned Responses Dropdown -->
                    <div id="canned-responses-dropdown" class="dropdown-menu" style="display: none; position: absolute; bottom: 100%; max-height: 300px; overflow-y: auto; width: 100%; z-index: 1050;">
                        <!-- Will be populated dynamically via AJAX -->
                    </div>

                    <div class="mb-2 editor-container" style="position: relative;">
                        <textarea class="form-control rich-editor" id="message-content" name="content"
                                  rows="3" placeholder="Type your message... (use / for canned responses, @ for mentions, Ctrl+E for emojis)"
                                  data-conversation-id="{{ $conversation->id }}"
                                  style="resize: none; border-radius: 8px;"
                                  {{ $conversation->isResolved() ? 'disabled' : '' }}></textarea>
                    </div>

                    <!-- File input (hidden) - Single unified input for all file types -->
                    <input type="file" id="file-input" name="attachments[]" multiple style="display: none;"
                           accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z">

                    <!-- Selected files preview -->
                    <div id="files-preview" class="mb-2" style="display: none;">
                        <div class="d-flex flex-wrap gap-2" id="files-list"></div>
                    </div>

                    <!-- Validation errors display -->
                    @if($errors->has('attachments.*') || $errors->has('attachments'))
                        <div class="alert alert-danger alert-dismissible fade show mb-2" role="alert">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>File validation errors:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach($errors->get('attachments.*') as $error)
                                    <li>{{ $error[0] }}</li>
                                @endforeach
                                @foreach($errors->get('attachments') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Emoji Picker Button -->
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="emoji-btn" title="Add emoji" {{ $conversation->isResolved() ? 'disabled' : '' }}>
                                <i class="bi bi-emoji-smile"></i>
                            </button>

                            <!-- Attach File Button (Images, Documents, Videos) -->
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="attach-file-btn" title="Attach files (images, documents, videos, audio)" {{ $conversation->isResolved() ? 'disabled' : '' }}>
                                <i class="bi bi-paperclip"></i>
                            </button>

                            <!-- Record Audio Button -->
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="record-audio-btn" title="Record audio" {{ $conversation->isResolved() ? 'disabled' : '' }}>
                                <i class="bi bi-mic"></i>
                            </button>

                            <!-- Private Note Toggle Button -->
                            <button type="button" class="btn btn-sm btn-outline-warning" id="private-note-toggle" title="Toggle private note mode" {{ $conversation->isResolved() ? 'disabled' : '' }}>
                                <i class="bi bi-lock"></i>
                                <span class="ms-1 d-none d-md-inline">Private</span>
                            </button>

                            <!-- Hidden checkbox for private note (controlled by toggle button) -->
                            <input type="checkbox" id="private" name="private" value="1" style="display: none;">
                        </div>

                        <button type="submit" class="btn btn-primary" id="send-btn" {{ $conversation->isResolved() ? 'disabled' : '' }}>
                            <i class="bi bi-send-fill"></i> <span id="send-btn-text">Send</span>
                        </button>
                    </div>
                </form>

                @if($conversation->isResolved())
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle"></i>
                        This conversation is resolved. Change the status to "Open" to send new messages.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Contact Information -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-person"></i> Contact Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>Name:</strong><br>
                    {{ $conversation->contact->name }}
                </div>
                @if($conversation->contact->email)
                    <div class="mb-2">
                        <strong>Email:</strong><br>
                        <a href="mailto:{{ $conversation->contact->email }}">{{ $conversation->contact->email }}</a>
                    </div>
                @endif
                @if($conversation->contact->phone_number)
                    <div class="mb-2">
                        <strong>Phone:</strong><br>
                        {{ $conversation->contact->phone_number }}
                    </div>
                @endif
                <div class="mb-0">
                    <strong>First Seen:</strong><br>
                    {{ $conversation->contact->created_at->format('M d, Y') }}
                </div>
            </div>
        </div>

        <!-- Conversation Details -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Conversation Details</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>Inbox:</strong><br>
                    <span class="badge bg-info">{{ $conversation->inbox->name }}</span>
                </div>
                <div class="mb-2">
                    <strong>Priority:</strong><br>
                    @if($conversation->priority === 'urgent')
                        <span class="badge bg-danger">Urgent</span>
                    @elseif($conversation->priority === 'high')
                        <span class="badge bg-warning">High</span>
                    @elseif($conversation->priority === 'medium')
                        <span class="badge bg-info">Medium</span>
                    @else
                        <span class="badge bg-secondary">Low</span>
                    @endif
                </div>
                <div class="mb-2">
                    <strong>Created:</strong><br>
                    {{ $conversation->created_at->format('M d, Y H:i') }}
                </div>
                <div class="mb-2">
                    <strong>Last Activity:</strong><br>
                    {{ $conversation->last_activity_at->diffForHumans() }}
                </div>
                @if($conversation->isSnoozed())
                    <div class="mb-0">
                        <strong>Snoozed Until:</strong><br>
                        <span class="badge bg-warning">{{ $conversation->snoozed_until->format('M d, Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- SLA Tracking -->
        @if($conversation->slaTracking)
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-stopwatch"></i> SLA Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Policy:</strong><br>
                        <a href="{{ route('admin.sla-policies.show', $conversation->slaPolicy) }}">
                            {{ $conversation->slaPolicy->name }}
                        </a>
                    </div>

                    @if($conversation->slaTracking->first_response_due_at)
                        <div class="mb-2">
                            <strong>First Response:</strong><br>
                            <span class="badge {{ $conversation->slaTracking->getStatusBadgeClass() }}">
                                {{ $conversation->slaTracking->first_response_at ? 'Responded' : ($conversation->slaTracking->first_response_breached ? 'Breached' : 'Due ' . $conversation->slaTracking->first_response_due_at->diffForHumans()) }}
                            </span>
                        </div>
                    @endif

                    @if($conversation->slaTracking->resolution_due_at)
                        <div class="mb-0">
                            <strong>Resolution:</strong><br>
                            <span class="badge {{ $conversation->slaTracking->getStatusBadgeClass() }}">
                                {{ $conversation->slaTracking->resolved_at ? 'Resolved' : ($conversation->slaTracking->resolution_breached ? 'Breached' : 'Due ' . $conversation->slaTracking->resolution_due_at->diffForHumans()) }}
                            </span>
                        </div>
                    @endif

                    <hr class="my-2">

                    <div>
                        <span class="badge {{ $conversation->slaTracking->getStatusBadgeClass() }} w-100">
                            {{ $conversation->slaTracking->getStatusText() }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Labels -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-tags"></i> Labels</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    @php
                        $conversationLabels = $conversation->getLabels();
                    @endphp
                    @forelse($conversationLabels as $labelTitle)
                        @php
                            $label = $labelsByTitle[$labelTitle] ?? null;
                        @endphp
                        @if($label)
                            <form action="{{ route('admin.conversation.removeLabel', $conversation) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="label" value="{{ $label->title }}">
                                <span class="badge me-1 mb-1" style="background-color: {{ $label->color }}">
                                    {{ $label->title }}
                                    <button type="submit" class="btn-close btn-close-white" style="font-size: 0.5rem; padding: 0; margin-left: 4px;" aria-label="Remove"></button>
                                </span>
                            </form>
                        @endif
                    @empty
                        <small class="text-muted">No labels</small>
                    @endforelse
                </div>
                <form action="{{ route('admin.conversation.addLabels', $conversation) }}" method="POST">
                    @csrf
                    <div class="input-group input-group-sm">
                        <select name="labels[]" class="form-select form-select-sm" multiple size="1">
                            @foreach(auth()->user()->account->labels as $label)
                                @if(!in_array($label->title, $conversationLabels))
                                    <option value="{{ $label->title }}">{{ $label->title }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contact Attributes -->
        @if($conversation->contact->additional_attributes && count($conversation->contact->additional_attributes) > 0)
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person-badge"></i> Contact Attributes</h6>
                </div>
                <div class="card-body">
                    @foreach($conversation->contact->additional_attributes as $key => $value)
                        <div class="mb-2">
                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong><br>
                            <span class="text-muted">
                                @if(is_array($value) || is_object($value))
                                    <code>{{ json_encode($value) }}</code>
                                @else
                                    {{ $value }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Previous Conversations -->
        @if($previousConversations->count() > 0)
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Previous Conversations</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($previousConversations as $prevConv)
                            <a href="{{ route('admin.conversation.show', $prevConv) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <small class="mb-1">{{ $prevConv->inbox->name }}</small>
                                    <small class="text-muted">{{ $prevConv->last_activity_at->diffForHumans() }}</small>
                                </div>
                                <small class="text-muted">
                                    @if($prevConv->status === 'open')
                                        <span class="badge bg-warning text-dark">Open</span>
                                    @elseif($prevConv->status === 'resolved')
                                        <span class="badge bg-success">Resolved</span>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                </small>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Macros -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-play-circle"></i> Macros</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.macros.execute', ['macro' => '__MACRO_ID__']) }}" method="POST" id="macro-form">
                    @csrf
                    <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                    <select class="form-select form-select-sm mb-2" id="macro-selector">
                        <option value="">Select a macro...</option>
                        @foreach($macros as $macro)
                            <option value="{{ $macro->id }}">{{ $macro->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-primary btn-sm w-100" id="execute-macro" disabled>
                        <i class="bi bi-play-fill"></i> Execute Macro
                    </button>
                </form>
            </div>
        </div>

        <!-- Actions -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-lightning"></i> Actions</h6>
            </div>
            <div class="card-body">
                <!-- Change Status -->
                <form action="{{ route('admin.conversation.updateStatus', $conversation) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <label class="form-label small"><strong>Status:</strong></label>
                    <div class="input-group input-group-sm">
                        <select name="status" class="form-select form-select-sm">
                            <option value="open" {{ $conversation->status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="pending" {{ $conversation->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="resolved" {{ $conversation->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-check"></i>
                        </button>
                    </div>
                </form>

                <!-- Assign Team -->
                <form action="{{ route('admin.conversation.updateTeam', $conversation) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <label class="form-label small"><strong>Team:</strong></label>
                    <div class="input-group input-group-sm">
                        <select name="team_id" class="form-select form-select-sm">
                            <option value="">No Team</option>
                            @foreach(auth()->user()->account->teams as $team)
                                <option value="{{ $team->id }}" {{ $conversation->team_id == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-check"></i>
                        </button>
                    </div>
                </form>

                <!-- Assign Agent -->
                <form action="{{ route('admin.conversation.assign', $conversation) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <label class="form-label small"><strong>Assignee:</strong></label>
                    <div class="input-group input-group-sm">
                        <select name="assignee_id" class="form-select form-select-sm">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $conversation->assignee_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-check"></i>
                        </button>
                    </div>
                </form>

                <!-- Update Priority -->
                <form action="{{ route('admin.conversation.updatePriority', $conversation) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <label class="form-label small"><strong>Priority:</strong></label>
                    <div class="input-group input-group-sm">
                        <select name="priority" class="form-select form-select-sm">
                            <option value="low" {{ $conversation->priority === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ $conversation->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ $conversation->priority === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ $conversation->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-check"></i>
                        </button>
                    </div>
                </form>

                <!-- Snooze -->
                @if($conversation->isSnoozed())
                    <form action="{{ route('admin.conversation.unsnooze', $conversation) }}" method="POST" class="mb-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                            <i class="bi bi-alarm"></i> Unsnooze
                        </button>
                    </form>
                @else
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#snoozeModal">
                        <i class="bi bi-alarm"></i> Snooze
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Snooze Modal -->
<div class="modal fade" id="snoozeModal" tabindex="-1" aria-labelledby="snoozeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.conversation.snooze', $conversation) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="snoozeModalLabel">Snooze Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="snooze_option" class="form-label">Snooze until:</label>
                        <select class="form-select" id="snooze_option" name="snooze_option">
                            <option value="1_hour">1 hour</option>
                            <option value="2_hours">2 hours</option>
                            <option value="tomorrow">Tomorrow (9 AM)</option>
                            <option value="next_week">Next week</option>
                            <option value="custom">Custom date/time</option>
                        </select>
                    </div>
                    <div class="mb-3" id="custom_snooze" style="display: none;">
                        <label for="snoozed_until" class="form-label">Custom Date/Time:</label>
                        <input type="datetime-local" class="form-control" id="snoozed_until" name="snoozed_until">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Snooze</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Email Transcript Modal -->
<div class="modal fade" id="emailTranscriptModal" tabindex="-1" aria-labelledby="emailTranscriptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.conversation.emailTranscript', $conversation) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="emailTranscriptModalLabel">
                        <i class="bi bi-envelope"></i> Enviar transcripción por email
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle"></i>
                        Se enviará un PDF con la transcripción completa de la conversación al email especificado.
                    </div>

                    <div class="mb-3">
                        <label for="recipient_email" class="form-label">Email del destinatario <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="recipient_email" name="email" placeholder="destinatario@ejemplo.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email_message" class="form-label">Mensaje personalizado (opcional)</label>
                        <textarea class="form-control @error('message') is-invalid @enderror"
                                  id="email_message" name="message" rows="3"
                                  placeholder="Agrega un mensaje personalizado que se incluirá en el email..."></textarea>
                        <div class="form-text">Este mensaje aparecerá en el cuerpo del email junto con la transcripción adjunta.</div>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Enviar transcripción
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Macro Execution Modal -->
<div class="modal fade" id="executeMacroModal" tabindex="-1" aria-labelledby="executeMacroModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="executeMacroModalLabel">
                    <i class="bi bi-play-circle"></i> Execute Macro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="macroConfirmMessage"></p>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle"></i> This action will apply the macro to this conversation and cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmMacroBtn">
                    <i class="bi bi-play-fill"></i> Execute
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.messages-container {
    max-height: 500px;
    overflow-y: auto;
}

/* Typing Indicator */
.typing-dots {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.typing-dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #6c757d;
    animation: typingDot 1.4s infinite ease-in-out;
}

.typing-dots span:nth-child(1) {
    animation-delay: 0s;
}

.typing-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typingDot {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.7;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

/* Private Note Styles */
.private-note-bubble {
    position: relative;
}

.private-note-bubble::before {
    content: '🔒';
    position: absolute;
    left: -25px;
    top: 10px;
    font-size: 14px;
    opacity: 0.6;
}

#message-content.private-note-mode {
    background-color: #fff3cd !important;
    border-color: #ffc107 !important;
    transition: all 0.2s ease;
}
</style>
@endpush

@push('scripts')
@vite(['resources/js/app.js'])

<script>
// Pass conversation data to JavaScript
window.conversationData = {
    id: {{ $conversation->id }},
    currentUserId: {{ auth()->id() }},
    isResolved: {{ $conversation->isResolved() ? 'true' : 'false' }},
    context: {
        'contact.name': '{{ $conversation->contact->name }}',
        'contact.email': '{{ $conversation->contact->email }}',
        'contact.phone': '{{ $conversation->contact->phone_number ?? "" }}',
        'agent.name': '{{ auth()->user()->name }}',
        'agent.email': '{{ auth()->user()->email }}',
        'inbox.name': '{{ $conversation->inbox->name }}',
        'conversation.id': '{{ $conversation->id }}'
    }
};

// Private Note Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const privateNoteToggle = document.getElementById('private-note-toggle');
    const privateCheckbox = document.getElementById('private');
    const messageContent = document.getElementById('message-content');
    const sendBtn = document.getElementById('send-btn');
    const sendBtnText = document.getElementById('send-btn-text');

    if (privateNoteToggle && privateCheckbox && messageContent) {
        privateNoteToggle.addEventListener('click', function() {
            // Toggle checkbox
            privateCheckbox.checked = !privateCheckbox.checked;

            // Update UI based on state
            if (privateCheckbox.checked) {
                // Private note mode ON
                privateNoteToggle.classList.remove('btn-outline-warning');
                privateNoteToggle.classList.add('btn-warning');
                messageContent.style.backgroundColor = '#fff3cd';
                messageContent.style.borderColor = '#ffc107';
                messageContent.placeholder = 'Type a private note (only visible to agents)...';
                sendBtnText.textContent = 'Add Note';
                sendBtn.classList.remove('btn-primary');
                sendBtn.classList.add('btn-warning');
            } else {
                // Private note mode OFF
                privateNoteToggle.classList.remove('btn-warning');
                privateNoteToggle.classList.add('btn-outline-warning');
                messageContent.style.backgroundColor = '';
                messageContent.style.borderColor = '';
                messageContent.placeholder = 'Type your message... (use / for canned responses, @ for mentions, Ctrl+E for emojis)';
                sendBtnText.textContent = 'Send';
                sendBtn.classList.remove('btn-warning');
                sendBtn.classList.add('btn-primary');
            }
        });

        // Reset private note mode after message is sent
        const messageForm = document.getElementById('message-form');
        if (messageForm) {
            messageForm.addEventListener('submit', function() {
                setTimeout(function() {
                    if (privateCheckbox.checked) {
                        privateNoteToggle.click(); // Turn off private mode
                    }
                }, 500);
            });
        }
    }
});
</script>


<style>
/* Canned Responses Dropdown Styles */
#canned-responses-dropdown {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 8px;
}

#canned-responses-dropdown .dropdown-item {
    padding: 10px 15px;
    border-bottom: 1px solid #f0f0f0;
}

#canned-responses-dropdown .dropdown-item:hover,
#canned-responses-dropdown .dropdown-item.active {
    background-color: #f8f9fa;
}

#canned-responses-dropdown .dropdown-item:last-child {
    border-bottom: none;
}

/* Message Form Improvements */
#message-content {
    font-size: 14px;
    line-height: 1.5;
}

#message-content:focus {
    border-color: #1f93ff;
    box-shadow: 0 0 0 0.2rem rgba(31, 147, 255, 0.25);
}

.gap-2 {
    gap: 0.5rem !important;
}
</style>
@endpush
