@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Edit SMS Channel</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.channels.sms.update', $sms) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="inbox_name" class="form-label">Inbox Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                            id="inbox_name" name="inbox_name" value="{{ old('inbox_name', $sms->inbox->name ?? '') }}" required>
                        @error('inbox_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number (E.164 format) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                            id="phone_number" name="phone_number" value="{{ old('phone_number', $sms->phone_number) }}"
                            placeholder="+1234567890" required>
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="api_key" class="form-label">API Key</label>
                        <input type="text" class="form-control @error('api_key') is-invalid @enderror"
                            id="api_key" name="api_key" value="{{ old('api_key') }}"
                            placeholder="Leave blank to keep current value">
                        <small class="form-text text-muted">Only enter if you want to change it</small>
                        @error('api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="api_secret" class="form-label">API Secret</label>
                        <input type="password" class="form-control @error('api_secret') is-invalid @enderror"
                            id="api_secret" name="api_secret" placeholder="Leave blank to keep current value">
                        <small class="form-text text-muted">Only enter if you want to change it</small>
                        @error('api_secret')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="application_id" class="form-label">Application ID</label>
                        <input type="text" class="form-control @error('application_id') is-invalid @enderror"
                            id="application_id" name="application_id" value="{{ old('application_id', $sms->application_id) }}">
                        @error('application_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                                {{ old('active', $sms->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active (channel will receive messages)
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Update SMS Channel</button>
                        <a href="{{ route('admin.channels.sms.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
