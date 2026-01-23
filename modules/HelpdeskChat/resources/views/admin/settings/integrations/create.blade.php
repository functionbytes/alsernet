@extends('layouts.admin')

@section('title', 'Create Integrations Hook')

@section('content')

    @include('helpdeskchat::includes.card', [
        'title' => 'Create Integration Hook',
        'icon' => 'fa-plus-circle',
        'breadcrumbs' => [
            ['label' => 'Integration Hooks', 'url' => route('admin.integrations.index')],
            ['label' => 'Create']
        ]
    ])

    <div class="widget-content">
        @include('helpdeskchat::components.alerts')

        <div class="row">
            <div class="col-lg-8">
                <div class="card border">
                    <div class="card-body">
                        <form action="{{ route('admin.integrations.store') }}" method="POST">
                            @csrf

                            <!-- App ID -->
                            <div class="mb-3">
                                <label for="app_id" class="form-label fw-bold">Application ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('app_id') is-invalid @enderror"
                                       id="app_id" name="app_id" value="{{ old('app_id') }}"
                                       placeholder="e.g., slack, zapier, webhooks" required>
                                @error('app_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Unique identifier for the integration app
                                </small>
                            </div>

                            <!-- Hook Type -->
                            <div class="mb-3">
                                <label for="hook_type" class="form-label fw-bold">Hook Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('hook_type') is-invalid @enderror"
                                        id="hook_type" name="hook_type" required>
                                    <option value="">Select hook type...</option>
                                    <option value="0" {{ old('hook_type') == '0' ? 'selected' : '' }}>Incoming</option>
                                    <option value="1" {{ old('hook_type') == '1' ? 'selected' : '' }}>Outgoing</option>
                                </select>
                                @error('hook_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Inbox -->
                            <div class="mb-3">
                                <label for="inbox_id" class="form-label fw-bold">Inbox</label>
                                <select class="form-select @error('inbox_id') is-invalid @enderror"
                                        id="inbox_id" name="inbox_id">
                                    <option value="">None (Account level)</option>
                                    @foreach($inboxes as $inbox)
                                        <option value="{{ $inbox->id }}" {{ old('inbox_id') == $inbox->id ? 'selected' : '' }}>
                                            {{ $inbox->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('inbox_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Leave empty for account-level integration
                                </small>
                            </div>

                            <!-- Reference ID -->
                            <div class="mb-3">
                                <label for="reference_id" class="form-label fw-bold">Reference ID</label>
                                <input type="text" class="form-control @error('reference_id') is-invalid @enderror"
                                       id="reference_id" name="reference_id" value="{{ old('reference_id') }}"
                                       placeholder="External reference ID">
                                @error('reference_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Optional external reference or channel ID
                                </small>
                            </div>

                            <!-- Access Token -->
                            <div class="mb-3">
                                <label for="access_token" class="form-label fw-bold">Access Token</label>
                                <input type="text" class="form-control @error('access_token') is-invalid @enderror"
                                       id="access_token" name="access_token" value="{{ old('access_token') }}"
                                       placeholder="API access token">
                                @error('access_token')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    OAuth token or API key for authentication
                                </small>
                            </div>

                            <!-- Settings (JSON) -->
                            <div class="mb-3">
                                <label for="settings_json" class="form-label fw-bold">Settings (JSON)</label>
                                <textarea class="form-control @error('settings') is-invalid @enderror font-monospace"
                                          id="settings_json" name="settings_json" rows="4"
                                          placeholder='{"key": "value"}'>{{ old('settings_json', '{}') }}</textarea>
                                @error('settings')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Additional configuration in JSON format
                                </small>
                            </div>

                            <!-- Status -->
                            <div class="mb-4">
                                <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                        id="status" name="status" required>
                                    <option value="{{ $statusOptions['disabled'] }}" {{ old('status', $statusOptions['disabled']) == $statusOptions['disabled'] ? 'selected' : '' }}>
                                        Disabled
                                    </option>
                                    <option value="{{ $statusOptions['enabled'] }}" {{ old('status') == $statusOptions['enabled'] ? 'selected' : '' }}>
                                        Enabled
                                    </option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Submit -->
                            <div class="border-top pt-3">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('admin.integrations.index') }}" class="btn btn-secondary">
                                        <i class="fa fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-2"></i>Create Integration Hook
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-lightbulb me-2"></i>About Integration Hooks
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-0">
                            Integration hooks connect your account with external applications like Slack, Zapier, or custom integrations.
                            They enable bidirectional data flow between systems.
                        </p>
                    </div>
                </div>

                <div class="card border mt-3">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-plug me-2"></i>Hook Types
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong class="d-block mb-1">
                                <i class="fa fa-arrow-down text-success me-1"></i>
                                Incoming
                            </strong>
                            <small class="text-muted">
                                Receives data from external systems into your account
                            </small>
                        </div>
                        <div>
                            <strong class="d-block mb-1">
                                <i class="fa fa-arrow-up text-primary me-1"></i>
                                Outgoing
                            </strong>
                            <small class="text-muted">
                                Sends data from your account to external systems
                            </small>
                        </div>
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
