@extends('layouts.theme')

@section('title', 'Add New Subscriber')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    .form-card {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        padding: 2rem;
    }
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
    .info-box {
        background-color: #e7f3ff;
        border-left: 4px solid #0d6efd;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-bottom: 1.5rem;
    }
    .custom-field-row {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2">
                        <i class="bi bi-person-plus-fill"></i> Add New Subscriber
                    </h1>
                    <p class="text-muted">Create a new subscriber record</p>
                </div>
                <a href="{{ route('mailrelay.subscribers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Note:</strong> Email validation will be performed automatically after creation.
                The subscriber will be synced with Mailrelay if auto-sync is enabled.
            </div>

            <!-- Error Messages -->
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6 class="alert-heading">
                    <i class="bi bi-exclamation-triangle-fill"></i> Please correct the following errors:
                </h6>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Form Card -->
            <div class="form-card">
                <form action="{{ route('mailrelay.subscribers.store') }}" method="POST" id="subscriberForm">
                    @csrf

                    <!-- Basic Information -->
                    <h5 class="mb-3 pb-2 border-bottom">
                        <i class="bi bi-person"></i> Basic Information
                    </h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label required-field">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    placeholder="subscriber@example.com"
                                >
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Must be a valid email address</small>
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="John Doe"
                                >
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label required-field">Status</label>
                            <select class="select2"                                 class="form-select @error('status') is-invalid @enderror"
                                id="status"
                                name="status"
                                required
                            >
                                <option value="">Select status...</option>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="unsubscribed" {{ old('status') == 'unsubscribed' ? 'selected' : '' }}>
                                    Unsubscribed
                                </option>
                                <option value="bounced" {{ old('status') == 'bounced' ? 'selected' : '' }}>
                                    Bounced
                                </option>
                                <option value="banned" {{ old('status') == 'banned' ? 'selected' : '' }}>
                                    Banned
                                </option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="list_id" class="form-label">Mailing List</label>
                            <select class="select2"                                 class="form-select @error('list_id') is-invalid @enderror"
                                id="list_id"
                                name="list_id"
                            >
                                <option value="">Select a list (optional)...</option>
                                @foreach($lists ?? [] as $list)
                                    <option value="{{ $list->id }}" {{ old('list_id') == $list->id ? 'selected' : '' }}>
                                        {{ $list->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('list_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Custom Fields Section -->
                    <h5 class="mb-3 pb-2 border-bottom mt-4">
                        <i class="bi bi-sliders"></i> Custom Fields
                        <small class="text-muted">(Optional)</small>
                    </h5>

                    <div id="customFieldsContainer">
                        @if(old('custom_fields'))
                            @foreach(old('custom_fields') as $key => $value)
                                <div class="custom-field-row">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="custom_fields[{{ $loop->index }}][key]"
                                                placeholder="Field name"
                                                value="{{ $key }}"
                                            >
                                        </div>
                                        <div class="col-md-6">
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="custom_fields[{{ $loop->index }}][value]"
                                                placeholder="Field value"
                                                value="{{ $value }}"
                                            >
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeCustomField(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addCustomField()">
                        <i class="bi bi-plus-circle"></i> Add Custom Field
                    </button>

                    <!-- Metadata Section -->
                    <h5 class="mb-3 pb-2 border-bottom mt-4">
                        <i class="bi bi-file-earmark-text"></i> Additional Metadata
                        <small class="text-muted">(Optional)</small>
                    </h5>

                    <div class="mb-3">
                        <label for="metadata" class="form-label">Metadata (JSON format)</label>
                        <textarea
                            class="form-control @error('metadata') is-invalid @enderror"
                            id="metadata"
                            name="metadata"
                            rows="4"
                            placeholder='{"source": "website", "campaign": "summer2024"}'
                        >{{ old('metadata') }}</textarea>
                        @error('metadata')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Enter valid JSON format for additional metadata</small>
                    </div>

                    <!-- Mailrelay Integration -->
                    <h5 class="mb-3 pb-2 border-bottom mt-4">
                        <i class="bi bi-cloud"></i> Mailrelay Integration
                    </h5>

                    <div class="mb-3">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="sync_to_mailrelay"
                                name="sync_to_mailrelay"
                                value="1"
                                {{ old('sync_to_mailrelay', true) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="sync_to_mailrelay">
                                <strong>Sync to Mailrelay immediately after creation</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            If checked, this subscriber will be synced to Mailrelay as soon as the record is created
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="mailrelay_groups" class="form-label">Mailrelay Groups</label>
                        <select class="select2"                             class="form-select @error('mailrelay_groups') is-invalid @enderror"
                            id="mailrelay_groups"
                            name="mailrelay_groups[]"
                            multiple
                            size="5"
                        >
                            @foreach($mailrelayGroups ?? [] as $group)
                                <option value="{{ $group->id }}" {{ in_array($group->id, old('mailrelay_groups', [])) ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('mailrelay_groups')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple groups</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <a href="{{ route('mailrelay.subscribers.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Subscriber
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let customFieldIndex = {{ old('custom_fields') ? count(old('custom_fields')) : 0 }};

    function addCustomField() {
        const container = document.getElementById('customFieldsContainer');
        const fieldRow = document.createElement('div');
        fieldRow.className = 'custom-field-row';
        fieldRow.innerHTML = `
            <div class="row">
                <div class="col-md-5">
                    <input
                        type="text"
                        class="form-control"
                        name="custom_fields[${customFieldIndex}][key]"
                        placeholder="Field name"
                        required
                    >
                </div>
                <div class="col-md-6">
                    <input
                        type="text"
                        class="form-control"
                        name="custom_fields[${customFieldIndex}][value]"
                        placeholder="Field value"
                        required
                    >
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeCustomField(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(fieldRow);
        customFieldIndex++;
    }

    function removeCustomField(button) {
        button.closest('.custom-field-row').remove();
    }

    // Validate JSON in metadata field
    document.getElementById('subscriberForm').addEventListener('submit', function(e) {
        const metadataField = document.getElementById('metadata');
        if (metadataField.value.trim() !== '') {
            try {
                JSON.parse(metadataField.value);
            } catch (error) {
                e.preventDefault();
                alert('Invalid JSON format in metadata field. Please check your input.');
                metadataField.focus();
                return false;
            }
        }
    });
</script>
@endsection
