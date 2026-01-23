@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Edit API Channel</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.channels.api.update', $api) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="inbox_name" class="form-label">Inbox Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                            id="inbox_name" name="inbox_name" value="{{ old('inbox_name', $api->inbox->name ?? '') }}" required>
                        @error('inbox_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="identifier" class="form-label">Channel Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('identifier') is-invalid @enderror"
                            id="identifier" name="identifier" value="{{ old('identifier', $api->identifier) }}" required>
                        <small class="form-text text-muted">Unique identifier for API calls</small>
                        @error('identifier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="webhook_url" class="form-label">Webhook URL</label>
                        <input type="url" class="form-control @error('webhook_url') is-invalid @enderror"
                            id="webhook_url" name="webhook_url" value="{{ old('webhook_url', $api->webhook_url) }}"
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
                            placeholder='{"custom_header": "value", "timeout": 30}'>{{ old('additional_settings', $api->additional_settings) }}</textarea>
                        <small class="form-text text-muted">Optional: Custom headers, authentication, or other configuration in JSON format</small>
                        @error('additional_settings')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                                {{ old('active', $api->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active (channel will receive messages)
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Note:</strong> HMAC token cannot be changed here. Use the "Regenerate" button in the channel details view.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Update API Channel</button>
                        <a href="{{ route('admin.channels.api.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
