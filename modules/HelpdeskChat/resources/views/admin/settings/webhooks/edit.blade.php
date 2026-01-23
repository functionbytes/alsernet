@extends('layouts.admin')

@section('title', 'Edit Webhook')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Edit Webhook</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.webhooks.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back to Webhooks
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
                    <form action="{{ route('admin.webhooks.update', $webhook) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Webhook Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $webhook->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- URL -->
                        <div class="mb-3">
                            <label for="url" class="form-label">Webhook URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control @error('url') is-invalid @enderror"
                                   id="url" name="url" value="{{ old('url', $webhook->url) }}"
                                   placeholder="https://example.com/webhook" required>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">The URL where webhook events will be sent</small>
                        </div>

                        <!-- Webhook Type -->
                        <div class="mb-3">
                            <label for="webhook_type" class="form-label">Webhook Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('webhook_type') is-invalid @enderror"
                                    id="webhook_type" name="webhook_type" required>
                                <option value="{{ $webhookTypes['account'] }}" {{ old('webhook_type', $webhook->webhook_type) == $webhookTypes['account'] ? 'selected' : '' }}>
                                    Account Level (all inboxes)
                                </option>
                                <option value="{{ $webhookTypes['inbox'] }}" {{ old('webhook_type', $webhook->webhook_type) == $webhookTypes['inbox'] ? 'selected' : '' }}>
                                    Inbox Level (specific inbox only)
                                </option>
                            </select>
                            @error('webhook_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Inbox (conditional) -->
                        <div class="mb-3" id="inbox-field">
                            <label for="inbox_id" class="form-label">Inbox</label>
                            <select class="form-select @error('inbox_id') is-invalid @enderror"
                                    id="inbox_id" name="inbox_id">
                                <option value="">Select inbox...</option>
                                @foreach($inboxes as $inbox)
                                    <option value="{{ $inbox->id }}" {{ old('inbox_id', $webhook->inbox_id) == $inbox->id ? 'selected' : '' }}>
                                        {{ $inbox->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('inbox_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Event Subscriptions -->
                        <div class="mb-3">
                            <label class="form-label">Event Subscriptions <span class="text-danger">*</span></label>
                            <small class="text-muted d-block mb-2">Select which events will trigger this webhook</small>

                            @php
                                $events = [
                                    'conversation_created' => 'Conversation Created',
                                    'conversation_updated' => 'Conversation Updated',
                                    'conversation_status_changed' => 'Conversation Status Changed',
                                    'message_created' => 'Message Created',
                                    'message_updated' => 'Message Updated',
                                    'conversation_opened' => 'Conversation Opened',
                                    'conversation_resolved' => 'Conversation Resolved',
                                ];
                                $selectedEvents = old('subscriptions', $webhook->subscriptions ?? []);
                            @endphp

                            @foreach($events as $value => $label)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="subscriptions[]"
                                           id="event_{{ $value }}" value="{{ $value }}"
                                           {{ in_array($value, $selectedEvents) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="event_{{ $value }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach

                            @error('subscriptions')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i> Update Webhook
                            </button>
                            <a href="{{ route('admin.webhooks.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">About Webhooks</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        Webhooks allow you to receive real-time notifications about events in your account.
                        When an event occurs, we'll send a POST request to your webhook URL with the event data.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    const webhookType = document.getElementById('webhook_type');
    const inboxField = document.getElementById('inbox-field');
    const inboxSelect = document.getElementById('inbox_id');

    function toggleInboxField() {
        if (webhookType.value == '{{ $webhookTypes["inbox"] }}') {
            inboxField.style.display = 'block';
            inboxSelect.required = true;
        } else {
            inboxField.style.display = 'none';
            inboxSelect.required = false;
            inboxSelect.value = '';
        }
    }

    webhookType.addEventListener('change', toggleInboxField);
    toggleInboxField(); // Initialize on load
});
</script>
@endsection
