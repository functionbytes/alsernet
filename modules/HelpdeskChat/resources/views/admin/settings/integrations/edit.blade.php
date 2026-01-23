@extends('layouts.admin')

@section('title', 'Edit Integrations Hook')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Edit Integration Hook</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.integrations.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back to Integration Hooks
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Validation Error:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.integrations.update', $integrationsHook) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- App ID -->
                        <div class="mb-3">
                            <label for="app_id" class="form-label">Application ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('app_id') is-invalid @enderror"
                                   id="app_id" name="app_id" value="{{ old('app_id', $integrationsHook->app_id) }}"
                                   placeholder="e.g., slack, zapier, webhooks" required>
                            @error('app_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Unique identifier for the integration app</small>
                        </div>

                        <!-- Hook Type -->
                        <div class="mb-3">
                            <label for="hook_type" class="form-label">Hook Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('hook_type') is-invalid @enderror"
                                    id="hook_type" name="hook_type" required>
                                <option value="">Select hook type...</option>
                                <option value="0" {{ old('hook_type', $integrationsHook->hook_type) == '0' ? 'selected' : '' }}>Incoming</option>
                                <option value="1" {{ old('hook_type', $integrationsHook->hook_type) == '1' ? 'selected' : '' }}>Outgoing</option>
                            </select>
                            @error('hook_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Inbox -->
                        <div class="mb-3">
                            <label for="inbox_id" class="form-label">Inbox</label>
                            <select class="form-select @error('inbox_id') is-invalid @enderror"
                                    id="inbox_id" name="inbox_id">
                                <option value="">None (Account level)</option>
                                @foreach($inboxes as $inbox)
                                    <option value="{{ $inbox->id }}" {{ old('inbox_id', $integrationsHook->inbox_id) == $inbox->id ? 'selected' : '' }}>
                                        {{ $inbox->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('inbox_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Leave empty for account-level integration</small>
                        </div>

                        <!-- Reference ID -->
                        <div class="mb-3">
                            <label for="reference_id" class="form-label">Reference ID</label>
                            <input type="text" class="form-control @error('reference_id') is-invalid @enderror"
                                   id="reference_id" name="reference_id" value="{{ old('reference_id', $integrationsHook->reference_id) }}"
                                   placeholder="External reference ID">
                            @error('reference_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Optional external reference or channel ID</small>
                        </div>

                        <!-- Access Token -->
                        <div class="mb-3">
                            <label for="access_token" class="form-label">Access Token</label>
                            <input type="text" class="form-control @error('access_token') is-invalid @enderror"
                                   id="access_token" name="access_token" value="{{ old('access_token', $integrationsHook->access_token) }}"
                                   placeholder="API access token">
                            @error('access_token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">OAuth token or API key for authentication</small>
                        </div>

                        <!-- Settings (JSON) -->
                        <div class="mb-3">
                            <label for="settings_json" class="form-label">Settings (JSON)</label>
                            <textarea class="form-control @error('settings') is-invalid @enderror font-monospace"
                                      id="settings_json" name="settings_json" rows="4"
                                      placeholder='{"key": "value"}'>{{ old('settings_json', json_encode($integrationsHook->settings ?? [], JSON_PRETTY_PRINT)) }}</textarea>
                            @error('settings')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Additional configuration in JSON format</small>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                <option value="{{ $statusOptions['disabled'] }}" {{ old('status', $integrationsHook->status) == $statusOptions['disabled'] ? 'selected' : '' }}>
                                    Disabled
                                </option>
                                <option value="{{ $statusOptions['enabled'] }}" {{ old('status', $integrationsHook->status) == $statusOptions['enabled'] ? 'selected' : '' }}>
                                    Enabled
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i> Update Integration Hook
                            </button>
                            <a href="{{ route('admin.integrations.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">About Integration Hooks</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        Integration hooks connect your account with external applications like Slack, Zapier, or custom integrations.
                        They enable bidirectional data flow between systems.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');

    form.addEventListener('submit', function(e) {
        const settingsField = document.getElementById('settings_json');
        const value = settingsField.value.trim();

        if (value) {
            try {
                const parsed = JSON.parse(value);
                // Create hidden input for settings array
                const settingsInput = document.createElement('input');
                settingsInput.type = 'hidden';
                settingsInput.name = 'settings';
                settingsInput.value = JSON.stringify(parsed);
                form.appendChild(settingsInput);
            } catch (error) {
                e.preventDefault();
                alert('Invalid JSON in Settings field');
                return false;
            }
        }
    });
});
</script>
@endsection
