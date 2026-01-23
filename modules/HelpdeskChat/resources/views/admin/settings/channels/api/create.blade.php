@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Add API Channel</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.channels.api.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="inbox_name" class="form-label">Inbox Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                            id="inbox_name" name="inbox_name" value="{{ old('inbox_name') }}" required>
                        <small class="form-text text-muted">This name will appear in the conversations list</small>
                        @error('inbox_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="identifier" class="form-label">Channel Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('identifier') is-invalid @enderror"
                            id="identifier" name="identifier" value="{{ old('identifier') }}"
                            placeholder="my-api-channel" required>
                        <small class="form-text text-muted">Unique identifier for API calls (alphanumeric, dashes, underscores)</small>
                        @error('identifier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="webhook_url" class="form-label">Webhook URL</label>
                        <input type="url" class="form-control @error('webhook_url') is-invalid @enderror"
                            id="webhook_url" name="webhook_url" value="{{ old('webhook_url') }}"
                            placeholder="https://your-api.com/webhook">
                        <small class="form-text text-muted">Optional: URL to send outgoing messages to</small>
                        @error('webhook_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="additional_settings" class="form-label">Additional Settings (JSON)</label>
                        <textarea class="form-control @error('additional_settings') is-invalid @enderror"
                            id="additional_settings" name="additional_settings" rows="4"
                            placeholder='{"custom_header": "value", "timeout": 30}'>{{ old('additional_settings') }}</textarea>
                        <small class="form-text text-muted">Optional: Custom headers, authentication, or other configuration in JSON format</small>
                        @error('additional_settings')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>Note:</strong> HMAC token and webhook verify token will be automatically generated for security.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Create API Channel</button>
                        <a href="{{ route('admin.channels.api.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
