@extends('layouts.admin')

@section('title', 'Create Email Template')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Create Email Template</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.email-templates.index') }}">Email Templates</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.email-templates.store') }}">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Template Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="event_type" class="form-label">Event Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('event_type') is-invalid @enderror"
                                id="event_type" name="event_type" required>
                                <option value="">Select event type...</option>
                                @foreach($eventTypes as $key => $label)
                                    <option value="{{ $key }}" {{ old('event_type', $selectedEventType) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('event_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="description" name="description" rows="2">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Email Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                id="subject" name="subject" value="{{ old('subject') }}" required
                                placeholder="Use variables like @{{contact_name}} or @{{conversation_id}}">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="body_html" class="form-label">Email Body (HTML) <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('body_html') is-invalid @enderror"
                                id="body_html" name="body_html" rows="15" required>{{ old('body_html') }}</textarea>
                            @error('body_html')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">You can use HTML tags for formatting</small>
                        </div>

                        <div class="mb-3">
                            <label for="body_text" class="form-label">Email Body (Plain Text)</label>
                            <textarea class="form-control @error('body_text') is-invalid @enderror"
                                id="body_text" name="body_text" rows="10">{{ old('body_text') }}</textarea>
                            @error('body_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Fallback for email clients that don't support HTML</small>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                                {{ old('active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active (template will be used for notifications)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3 sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="mb-0">Available Variables</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Click to copy a variable to your clipboard:</p>
                        <div id="variables-list">
                            @foreach($variables as $key => $description)
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 text-start variable-btn"
                                        data-variable="{{ $key }}" title="{{ $description }}">
                                        <code class="text-primary">@{{ {{ $key }} }}</code>
                                    </button>
                                    <small class="text-muted d-block mt-1">{{ $description }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle me-1"></i> Create Template
                            </button>
                            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-times-circle me-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
{{-- @vite(['resources/js/admin/email-templates.js']) --}}

<script>
// Pass route to JavaScript
window.emailTemplateData = {
    getVariablesUrl: '{{ route("admin.email-templates.getVariables") }}'
};
</script>
@endpush
@endsection
