@extends('layouts.theme')

@section('title', 'Create Campaign')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
    .form-section {
        background: #fff;
        border-radius: 8px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: #333;
        border-bottom: 2px solid #007bff;
        padding-bottom: 0.5rem;
    }
    #editor {
        height: 400px;
        background: #fff;
    }
    .ql-editor {
        min-height: 350px;
    }
    .recipient-tag {
        display: inline-block;
        background: #e9ecef;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        margin: 0.25rem;
    }
    .variable-tag {
        cursor: pointer;
        transition: all 0.2s;
    }
    .variable-tag:hover {
        background: #007bff;
        color: white;
    }
    .preview-section {
        position: sticky;
        top: 20px;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <a href="{{ route('mailrelay.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h1 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Campaign</h1>
        </div>
        <p class="text-muted">Build and schedule your email campaign</p>
    </div>

    <form action="{{ route('mailrelay.campaigns.store') }}" method="POST" id="campaignForm">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <!-- Campaign Details -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-info-circle"></i> Campaign Details
                    </h3>

                    <div class="mb-3">
                        <label for="name" class="form-label">Campaign Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}"
                               placeholder="e.g., Monthly Newsletter - January 2026" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Internal name for your reference</small>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Email Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror"
                               id="subject" name="subject" value="{{ old('subject') }}"
                               placeholder="e.g., Exclusive January Offers Just For You!" required>
                        @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">This will appear in recipients' inboxes</small>
                    </div>

                    <div class="mb-3">
                        <label for="from_name" class="form-label">From Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('from_name') is-invalid @enderror"
                               id="from_name" name="from_name" value="{{ old('from_name', config('mail.from.name')) }}" required>
                        @error('from_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="from_email" class="form-label">From Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('from_email') is-invalid @enderror"
                               id="from_email" name="from_email" value="{{ old('from_email', config('mail.from.address')) }}" required>
                        @error('from_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="reply_to" class="form-label">Reply-To Email</label>
                        <input type="email" class="form-control @error('reply_to') is-invalid @enderror"
                               id="reply_to" name="reply_to" value="{{ old('reply_to') }}"
                               placeholder="Optional">
                        @error('reply_to')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Recipients -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-people"></i> Recipients
                    </h3>

                    <div class="mb-3">
                        <label class="form-label">Select Lists/Groups <span class="text-danger">*</span></label>
                        @if(isset($lists) && $lists->count() > 0)
                        @foreach($lists as $list)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="lists[]"
                                   value="{{ $list->id }}" id="list{{ $list->id }}"
                                   {{ is_array(old('lists')) && in_array($list->id, old('lists')) ? 'checked' : '' }}>
                            <label class="form-check-label" for="list{{ $list->id }}">
                                {{ $list->name }}
                                <span class="badge bg-secondary">{{ $list->subscribers_count ?? 0 }} subscribers</span>
                            </label>
                        </div>
                        @endforeach
                        @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> No subscriber lists available.
                            <a href="{{ route('mailrelay.lists.create') }}">Create a list first</a>
                        </div>
                        @endif
                        @error('lists')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Email Content -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-envelope-open"></i> Email Content
                    </h3>

                    <div class="mb-3">
                        <label class="form-label">Personalization Variables</label>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{name}}')">
                                <i class="bi bi-plus"></i> {{name}}
                            </span>
                            <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{email}}')">
                                <i class="bi bi-plus"></i> {{email}}
                            </span>
                            <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{first_name}}')">
                                <i class="bi bi-plus"></i> {{first_name}}
                            </span>
                            <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{last_name}}')">
                                <i class="bi bi-plus"></i> {{last_name}}
                            </span>
                            <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{unsubscribe_url}}')">
                                <i class="bi bi-plus"></i> {{unsubscribe_url}}
                            </span>
                        </div>
                        <small class="text-muted">Click to insert into content</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Body <span class="text-danger">*</span></label>
                        <div id="editor"></div>
                        <input type="hidden" name="content" id="content">
                        @error('content')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-calendar-event"></i> Schedule
                    </h3>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_type"
                                   id="send_now" value="now" checked>
                            <label class="form-check-label" for="send_now">
                                <strong>Send Now</strong>
                                <br><small class="text-muted">Campaign will be sent immediately</small>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_type"
                                   id="send_later" value="scheduled">
                            <label class="form-check-label" for="send_later">
                                <strong>Schedule for Later</strong>
                            </label>
                        </div>
                    </div>

                    <div id="schedule_fields" style="display: none;" class="ms-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="scheduled_date"
                                       name="scheduled_date" value="{{ old('scheduled_date') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_time" class="form-label">Time</label>
                                <input type="time" class="form-control" id="scheduled_time"
                                       name="scheduled_time" value="{{ old('scheduled_time') }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_type"
                                   id="save_draft" value="draft">
                            <label class="form-check-label" for="save_draft">
                                <strong>Save as Draft</strong>
                                <br><small class="text-muted">Save without sending</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="preview-section">
                    <!-- Tips -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-lightbulb"></i> Tips for Success
                        </div>
                        <div class="card-body">
                            <ul class="small mb-0 ps-3">
                                <li class="mb-2">Use a clear, compelling subject line</li>
                                <li class="mb-2">Personalize with variables like {{name}}</li>
                                <li class="mb-2">Always include an unsubscribe link</li>
                                <li class="mb-2">Test your campaign before sending</li>
                                <li class="mb-2">Optimize for mobile devices</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Create Campaign
                                </button>
                                <a href="{{ route('mailrelay.campaigns.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    // Initialize Quill editor
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean']
            ]
        },
        placeholder: 'Compose your email content here...'
    });

    // Load old content if validation failed
    @if(old('content'))
    quill.root.innerHTML = {!! json_encode(old('content')) !!};
    @endif

    // Insert variable function
    function insertVariable(variable) {
        var range = quill.getSelection(true);
        quill.insertText(range.index, variable);
        quill.setSelection(range.index + variable.length);
    }

    // Form submission
    document.getElementById('campaignForm').addEventListener('submit', function(e) {
        // Set hidden input with Quill content
        document.getElementById('content').value = quill.root.innerHTML;
    });

    // Schedule toggle
    document.getElementById('send_later').addEventListener('change', function() {
        document.getElementById('schedule_fields').style.display = this.checked ? 'block' : 'none';
    });

    document.getElementById('send_now').addEventListener('change', function() {
        if(this.checked) {
            document.getElementById('schedule_fields').style.display = 'none';
        }
    });

    document.getElementById('save_draft').addEventListener('change', function() {
        if(this.checked) {
            document.getElementById('schedule_fields').style.display = 'none';
        }
    });

    // Set minimum date to today
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('scheduled_date').setAttribute('min', today);
</script>
@endsection
