@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Add SMS Channel</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.channels.sms.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="inbox_name" class="form-label">Inbox Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                            id="inbox_name" name="inbox_name" value="{{ old('inbox_name') }}" required>
                        @error('inbox_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="provider" class="form-label">Provider <span class="text-danger">*</span></label>
                        <select class="form-select @error('provider') is-invalid @enderror" id="provider" name="provider" required>
                            <option value="">Select provider...</option>
                            <option value="bandwidth" {{ old('provider') === 'bandwidth' ? 'selected' : '' }}>Bandwidth</option>
                            <option value="twilio" {{ old('provider') === 'twilio' ? 'selected' : '' }}>Twilio</option>
                            <option value="telnyx" {{ old('provider') === 'telnyx' ? 'selected' : '' }}>Telnyx</option>
                        </select>
                        @error('provider')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number (E.164 format) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                            id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                            placeholder="+1234567890" required>
                        <small class="form-text text-muted">Format: +[country code][number]</small>
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="api_key" class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('api_key') is-invalid @enderror"
                            id="api_key" name="api_key" value="{{ old('api_key') }}" required>
                        @error('api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="api_secret" class="form-label">API Secret <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('api_secret') is-invalid @enderror"
                            id="api_secret" name="api_secret" required>
                        @error('api_secret')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="application_id" class="form-label">Application ID</label>
                        <input type="text" class="form-control @error('application_id') is-invalid @enderror"
                            id="application_id" name="application_id" value="{{ old('application_id') }}">
                        <small class="form-text text-muted">Required for Bandwidth</small>
                        @error('application_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Create SMS Channel</button>
                        <a href="{{ route('admin.channels.sms.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
