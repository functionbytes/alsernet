@extends('layouts.admin')

@section('title', 'Contact Details')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb and Actions -->
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.contacts.index') }}">Contacts</a></li>
                    <li class="breadcrumb-item active">{{ $contact->full_name }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            @if($contact->blocked)
                <form action="{{ route('admin.contacts.unblock', $contact) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-unlock"></i> Unblock Contact
                    </button>
                </form>
            @else
                <form action="{{ route('admin.contacts.block', $contact) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-lock"></i> Block Contact
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.conversation.index') }}?contact_id={{ $contact->id }}" class="btn btn-primary">
                <i class="fa fa-send"></i> Send Message
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar: Contact Details -->
        <div class="col-md-4">
            <!-- Contact Header -->
            <div class="card mb-3">
                <div class="card-body text-center">
                    <!-- Avatar -->
                    <div class="position-relative d-inline-block mb-3">
                        @if($contact->avatar)
                            <img src="{{ $contact->avatar_url }}"
                                 alt="{{ $contact->full_name }}"
                                 class="rounded-circle"
                                 style="width: 100px; height: 100px; object-fit: cover;">
                        @else
                            <img src="{{ $contact->gravatar_url }}"
                                 alt="{{ $contact->full_name }}"
                                 class="rounded-circle"
                                 style="width: 100px; height: 100px; object-fit: cover;">
                        @endif

                        <!-- Avatar Actions -->
                        <div class="position-absolute bottom-0 end-0">
                            <button type="button" class="btn btn-sm btn-primary rounded-circle" data-bs-toggle="modal" data-bs-target="#uploadAvatarModal">
                                <i class="fa fa-camera"></i>
                            </button>
                        </div>
                    </div>

                    <h3>{{ $contact->full_name }}</h3>
                    @if($contact->blocked)
                        <span class="badge bg-danger">BLOCKED</span>
                    @endif
                    <p class="text-muted mb-1">{{ $contact->email ?? '-' }}</p>
                    <p class="text-muted">
                        Created {{ $contact->created_at->diffForHumans() }} •
                        Last activity {{ $contact->last_activity_at ? $contact->last_activity_at->diffForHumans() : 'Never' }}
                    </p>

                    @if($contact->avatar)
                        <form action="{{ route('admin.contacts.delete-avatar', $contact) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete avatar?')">
                                <i class="fa fa-trash"></i> Remove Avatar
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Labels -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Labels</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLabelModal">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
                <div class="card-body">
                    @forelse($contact->labels as $label)
                        <span class="badge me-1 mb-1" style="background-color: {{ $label->color ?? '#6c757d' }}">
                            {{ $label->title }}
                            <form action="{{ route('admin.contacts.remove-label', [$contact, $label]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-close btn-close-white" style="font-size: 0.6rem;" aria-label="Remove"></button>
                            </form>
                        </span>
                    @empty
                        <p class="text-muted mb-0">No labels</p>
                    @endforelse
                </div>
            </div>

            <!-- Edit Contact Details -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Edit Contact Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.contacts.update', $contact) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label small">First Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $contact->name }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Last Name</label>
                            <input type="text" name="last_name" class="form-control form-control-sm" value="{{ $contact->last_name }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ $contact->email }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Phone Number</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="country_code" class="form-control" style="max-width: 70px" placeholder="+57" value="{{ $contact->country_code }}">
                                <input type="text" name="phone_number" class="form-control" value="{{ $contact->phone_number }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">City</label>
                            <input type="text" name="city" class="form-control form-control-sm" value="{{ $contact->city }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Country</label>
                            <input type="text" name="country" class="form-control form-control-sm" value="{{ $contact->country }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Biography</label>
                            <textarea name="bio" class="form-control form-control-sm" rows="3">{{ $contact->bio }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Company Name</label>
                            <input type="text" name="company_name" class="form-control form-control-sm" value="{{ $contact->company_name }}">
                        </div>

                        <!-- Social Profiles -->
                        <h6 class="border-top pt-3">Edit Social Links</h6>

                        @php
                            $socialProfiles = $contact->social_profiles ?? [];
                        @endphp

                        <div class="mb-3">
                            <label class="form-label small">LinkedIn</label>
                            <input type="url" name="social_profiles[linkedin]" class="form-control form-control-sm"
                                   placeholder="https://linkedin.com/in/username" value="{{ $socialProfiles['linkedin'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Facebook</label>
                            <input type="url" name="social_profiles[facebook]" class="form-control form-control-sm"
                                   placeholder="https://facebook.com/username" value="{{ $socialProfiles['facebook'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Instagram</label>
                            <input type="url" name="social_profiles[instagram]" class="form-control form-control-sm"
                                   placeholder="https://instagram.com/username" value="{{ $socialProfiles['instagram'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Twitter/X</label>
                            <input type="url" name="social_profiles[twitter]" class="form-control form-control-sm"
                                   placeholder="https://twitter.com/username" value="{{ $socialProfiles['twitter'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Github</label>
                            <input type="url" name="social_profiles[github]" class="form-control form-control-sm"
                                   placeholder="https://github.com/username" value="{{ $socialProfiles['github'] ?? '' }}">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fa fa-save"></i> Update Contact
                        </button>
                    </form>

                    <!-- Delete Contact -->
                    <hr>
                    <h6 class="text-danger">Delete Contact</h6>
                    <p class="small text-muted">Permanently delete this contact. This action is irreversible.</p>
                    <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this contact?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm w-100">
                            <i class="fa fa-trash"></i> Delete Contact
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content: Tabs -->
        <div class="col-md-8">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#timeline-tab">
                        <i class="fa fa-diagram-3"></i> Timeline
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#history-tab">
                        <i class="fa fa-clock-history"></i> Conversations ({{ $contact->conversations->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes-tab">
                        <i class="fa fa-journal-text"></i> Notes ({{ $contact->notes->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#attributes-tab">
                        <i class="fa fa-list-ul"></i> Attributes
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 p-3">
                <!-- Timeline Tab -->
                <div class="tab-pane fade show active" id="timeline-tab">
                    <h5 class="mb-3">
                        <i class="fa fa-diagram-3 me-2"></i>Activity Timeline
                    </h5>
                    <p class="text-muted small">Chronological view of all activities related to this contact</p>

                    @php
                        // Combine all activities into timeline
                        $timeline = collect();

                        // Add conversation
                        foreach($contact->conversations as $conv) {
                            $timeline->push([
                                'type' => 'conversation',
                                'date' => $conv->created_at,
                                'icon' => 'chat-dots',
                                'color' => 'primary',
                                'title' => 'Conversation Created',
                                'description' => "#{$conv->id} via {$conv->inbox->name}",
                                'status' => $conv->status,
                                'link' => route('admin.conversation.show', $conv),
                                'assignee' => $conv->assignee->name ?? null,
                            ]);

                            // Add status changes (if conversation was resolved)
                            if($conv->status === 'resolved' && $conv->resolved_at) {
                                $timeline->push([
                                    'type' => 'status_change',
                                    'date' => $conv->resolved_at,
                                    'icon' => 'check-circle',
                                    'color' => 'success',
                                    'title' => 'Conversation Resolved',
                                    'description' => "Conversation #{$conv->id} was marked as resolved",
                                    'link' => route('admin.conversation.show', $conv),
                                ]);
                            }
                        }

                        // Add notes
                        foreach($contact->notes as $note) {
                            $timeline->push([
                                'type' => 'note',
                                'date' => $note->created_at,
                                'icon' => 'journal-text',
                                'color' => 'info',
                                'title' => 'Note Added',
                                'description' => \Illuminate\Support\Str::limit($note->content, 100),
                                'author' => $note->user->name ?? 'System',
                            ]);
                        }

                        // Add contact created event
                        $timeline->push([
                            'type' => 'created',
                            'date' => $contact->created_at,
                            'icon' => 'person-plus',
                            'color' => 'secondary',
                            'title' => 'Contact Created',
                            'description' => 'Contact profile was created in the system',
                        ]);

                        // Sort by date descending
                        $timeline = $timeline->sortByDesc('date');
                    @endphp

                    <div class="timeline">
                        @foreach($timeline as $item)
                            <div class="timeline-item mb-4">
                                <div class="row">
                                    <div class="col-auto">
                                        <div class="timeline-icon bg-{{ $item['color'] }} text-white">
                                            <i class="fa fa-{{ $item['icon'] }}"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-1">{{ $item['title'] }}</h6>
                                                        <small class="text-muted">
                                                            <i class="fa fa-clock me-1"></i>
                                                            {{ $item['date']->format('M d, Y H:i') }}
                                                            ({{ $item['date']->diffForHumans() }})
                                                        </small>
                                                    </div>
                                                    @if(isset($item['status']))
                                                        <span class="badge bg-{{ $item['status'] === 'open' ? 'success' : ($item['status'] === 'pending' ? 'warning' : 'secondary') }}">
                                                            {{ ucfirst($item['status']) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="mb-0">{{ $item['description'] }}</p>
                                                @if(isset($item['assignee']))
                                                    <small class="text-muted">
                                                        <i class="fa fa-person me-1"></i>Assigned to: {{ $item['assignee'] }}
                                                    </small>
                                                @endif
                                                @if(isset($item['author']))
                                                    <small class="text-muted">
                                                        <i class="fa fa-person me-1"></i>By: {{ $item['author'] }}
                                                    </small>
                                                @endif
                                                @if(isset($item['link']))
                                                    <div class="mt-2">
                                                        <a href="{{ $item['link'] }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-eye me-1"></i>View Details
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($timeline->isEmpty())
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle me-2"></i>
                                No activity recorded yet for this contact
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Conversations Tab -->
                <div class="tab-pane fade" id="history-tab">
                    <h5 class="mb-3">Conversation History ({{ $contact->conversations->count() }})</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Inbox</th>
                                    <th>Status</th>
                                    <th>Assignee</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contact->conversations as $conversation)
                                    <tr>
                                        <td>#{{ $conversation->id }}</td>
                                        <td>{{ $conversation->inbox->name }}</td>
                                        <td>
                                            <span class="badge {{ $conversation->status === 'open' ? 'bg-success' : ($conversation->status === 'pending' ? 'bg-warning' : 'bg-secondary') }}">
                                                {{ ucfirst($conversation->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $conversation->assignee->name ?? '-' }}</td>
                                        <td>
                                            <small class="text-muted">{{ $conversation->last_activity_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.conversation.show', $conversation) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            No conversations yet
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Notes Tab -->
                <div class="tab-pane fade" id="notes-tab">
                    <h5 class="mb-3">Notes</h5>

                    <!-- Add Note Form -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form action="{{ route('admin.contacts.store-note', $contact) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <textarea name="content" class="form-control" rows="3" placeholder="Add a note..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus-circle"></i> Add Note
                                </button>
                                <small class="text-muted ms-2">Tip: Press Cmd + Enter to submit</small>
                            </form>
                        </div>
                    </div>

                    <!-- Notes List -->
                    @forelse($contact->notes as $note)
                        <div class="card mb-2">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>{{ $note->user->name ?? 'System' }}</strong>
                                        <small class="text-muted ms-2">{{ $note->created_at->diffForHumans() }}</small>
                                    </div>
                                    <form action="{{ route('admin.contacts.destroy-note', [$contact, $note]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this note?')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <p class="mb-0">{{ $note->content }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> No notes yet. Add your first note above!
                        </div>
                    @endforelse
                </div>

                <!-- Attributes Tab -->
                <div class="tab-pane fade" id="attributes-tab">
                    @if($customAttributes->count() > 0)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Custom Attributes</h5>
                            <a href="{{ route('admin.custom-attributes.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-gear"></i> Manage Attributes
                            </a>
                        </div>

                        <form action="{{ route('admin.contacts.update-custom-attributes', $contact) }}" method="POST">
                            @csrf
                            @method('PUT')

                            @php
                                $contactAttributes = $contact->additional_attributes ?? [];
                            @endphp

                            @foreach($customAttributes as $attribute)
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ $attribute->attribute_display_name }}
                                        @if($attribute->attribute_description)
                                            <small class="text-muted">({{ $attribute->attribute_description }})</small>
                                        @endif
                                    </label>

                                    @if($attribute->attribute_display_type == \App\Models\CustomAttributeDefinition::DISPLAY_TYPE_TEXT)
                                        <input type="text"
                                               name="custom_attributes[{{ $attribute->attribute_key }}]"
                                               class="form-control"
                                               value="{{ $contactAttributes[$attribute->attribute_key] ?? $attribute->default_value }}"
                                               placeholder="Enter {{ strtolower($attribute->attribute_display_name) }}">

                                    @elseif($attribute->attribute_display_type == \App\Models\CustomAttributeDefinition::DISPLAY_TYPE_NUMBER)
                                        <input type="number"
                                               name="custom_attributes[{{ $attribute->attribute_key }}]"
                                               class="form-control"
                                               value="{{ $contactAttributes[$attribute->attribute_key] ?? $attribute->default_value }}"
                                               step="any">

                                    @elseif($attribute->attribute_display_type == \App\Models\CustomAttributeDefinition::DISPLAY_TYPE_LINK)
                                        <input type="url"
                                               name="custom_attributes[{{ $attribute->attribute_key }}]"
                                               class="form-control"
                                               value="{{ $contactAttributes[$attribute->attribute_key] ?? $attribute->default_value }}"
                                               placeholder="https://example.com">

                                    @elseif($attribute->attribute_display_type == \App\Models\CustomAttributeDefinition::DISPLAY_TYPE_DATE)
                                        <input type="date"
                                               name="custom_attributes[{{ $attribute->attribute_key }}]"
                                               class="form-control"
                                               value="{{ $contactAttributes[$attribute->attribute_key] ?? $attribute->default_value }}">

                                    @elseif($attribute->attribute_display_type == \App\Models\CustomAttributeDefinition::DISPLAY_TYPE_LIST)
                                        <select name="custom_attributes[{{ $attribute->attribute_key }}]" class="form-select">
                                            <option value="">-- Select {{ $attribute->attribute_display_name }} --</option>
                                            @if($attribute->attribute_values)
                                                @foreach($attribute->attribute_values as $option)
                                                    <option value="{{ $option }}"
                                                            {{ ($contactAttributes[$attribute->attribute_key] ?? $attribute->default_value) == $option ? 'selected' : '' }}>
                                                        {{ $option }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>

                                    @elseif($attribute->attribute_display_type == \App\Models\CustomAttributeDefinition::DISPLAY_TYPE_CHECKBOX)
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="custom_attributes[{{ $attribute->attribute_key }}]"
                                                   class="form-check-input"
                                                   value="1"
                                                   {{ ($contactAttributes[$attribute->attribute_key] ?? $attribute->default_value) ? 'checked' : '' }}>
                                            <label class="form-check-label">
                                                Yes
                                            </label>
                                        </div>
                                    @endif

                                    @if($attribute->regex_cue)
                                        <small class="form-text text-muted">{{ $attribute->regex_cue }}</small>
                                    @endif
                                </div>
                            @endforeach

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Save Custom Attributes
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No custom attributes are available for contacts in this account.
                            <a href="{{ route('admin.custom-attributes.create') }}" class="alert-link">Create your first custom attribute</a> to start collecting additional contact information.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Label Modal -->
<div class="modal fade" id="addLabelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.contacts.add-label', $contact) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <select name="label_id" class="form-select" required>
                        <option value="">Select a label...</option>
                        @foreach($accountLabels as $label)
                            @if(!$contact->labels->contains($label->id))
                                <option value="{{ $label->id }}">{{ $label->title }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Label</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Avatar Modal -->
<div class="modal fade" id="uploadAvatarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.contacts.upload-avatar', $contact) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Choose Image</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*" required>
                        <small class="form-text text-muted">Maximum size: 5MB. Accepted formats: JPG, PNG, GIF</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        The image will be automatically resized to 200x200 pixels.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Avatar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Timeline Styles */
.timeline {
    position: relative;
}

.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: 25px;
    top: 50px;
    bottom: -30px;
    width: 2px;
    background: linear-gradient(180deg, #dee2e6 0%, #dee2e6 100%);
}

.timeline-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: relative;
    z-index: 1;
}

.timeline .card {
    border-left: 3px solid #dee2e6;
    transition: all 0.3s ease;
}

.timeline .card:hover {
    border-left-color: #0d6efd;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateX(2px);
}
</style>
@endpush
