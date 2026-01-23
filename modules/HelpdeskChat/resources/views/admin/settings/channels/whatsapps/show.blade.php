@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>{{ $whatsapp->inbox->name }}</h2>
            <div>
                <button class="btn btn-success me-2" onclick="syncTemplates()">
                    <i class="bi bi-arrow-clockwise"></i> Sync Templates
                </button>
                <a href="{{ route('admin.channels.whatsapps.edit', $whatsapp) }}" class="btn btn-warning me-2">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('admin.channels.whatsapps.index') }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-whatsapp"></i> WhatsApp Configuration</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <td class="fw-bold" width="200">Phone Number:</td>
                            <td><code>{{ $whatsapp->phone_number }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Provider:</td>
                            <td>
                                <span class="badge bg-info">
                                    @if($whatsapp->provider === 'whatsapp_cloud')
                                        WhatsApp Cloud API
                                    @elseif($whatsapp->provider === '360dialog')
                                        360Dialog
                                    @elseif($whatsapp->provider === 'evolution_api')
                                        Evolution API (Baileys)
                                    @else
                                        {{ $whatsapp->provider }}
                                    @endif
                                </span>
                            </td>
                        </tr>

                        @if($whatsapp->isEvolutionApi())
                        <tr>
                            <td class="fw-bold">Instance Name:</td>
                            <td><code>{{ $whatsapp->getInstanceName() }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">API URL:</td>
                            <td><code>{{ $whatsapp->getApiUrl() }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Connection Status:</td>
                            <td>
                                <span id="connection-status" class="badge bg-secondary">
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                    Checking...
                                </span>
                            </td>
                        </tr>
                        @elseif($whatsapp->is360Dialog())
                        <tr>
                            <td class="fw-bold">API Key:</td>
                            <td><code>{{ Str::mask($whatsapp->getApiCredential(), '*', 0, -4) }}</code></td>
                        </tr>
                        @elseif($whatsapp->isCloudApi())
                        <tr>
                            <td class="fw-bold">Phone Number ID:</td>
                            <td><code>{{ $whatsapp->getPhoneNumberId() }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Business Account ID:</td>
                            <td><code>{{ $whatsapp->getBusinessAccountId() }}</code></td>
                        </tr>
                        @endif
                        <tr>
                            <td class="fw-bold">Message Templates:</td>
                            <td>{{ count($whatsapp->message_templates ?? []) }} templates</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Created:</td>
                            <td>{{ $whatsapp->created_at->format('F d, Y \a\t H:i') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Webhook Configuration</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Configure this webhook URL in your WhatsApp Business settings:</p>

                <div class="input-group mb-3">
                    <input type="text" class="form-control"
                           value="{{ route('webhooks.whatsapp.handle', $whatsapp->phone_number) }}"
                           readonly id="webhook-url">
                    <button class="btn btn-outline-secondary"
                            onclick="copyToClipboard('{{ route('webhooks.whatsapp.handle', $whatsapp->phone_number) }}')">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>

                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i>
                    <strong>Verify Token:</strong> Use <code>{{ config('channels.whatsapp.verify_token') }}</code>
                </div>
            </div>
        </div>

        @if($whatsapp->isEvolutionApi())
        <div class="card mb-4" id="qr-code-card" style="display:none;">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-qr-code"></i> Connect WhatsApp</h5>
            </div>
            <div class="card-body text-center">
                <p class="mb-3">Scan this QR code with WhatsApp to connect your instance:</p>
                <div id="qr-code-container" class="mb-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading QR Code...</span>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Instructions:</strong>
                    <ol class="mb-0 text-start">
                        <li>Open WhatsApp on your phone</li>
                        <li>Go to Settings → Linked Devices</li>
                        <li>Tap "Link a Device"</li>
                        <li>Scan this QR code</li>
                    </ol>
                </div>
                <button class="btn btn-primary" onclick="refreshQrCode()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh QR Code
                </button>
            </div>
        </div>
        @endif

        @if($whatsapp->message_templates && count($whatsapp->message_templates) > 0)
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-file-text"></i> Message Templates</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Language</th>
                                <th>Status</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($whatsapp->message_templates as $template)
                            <tr>
                                <td><code>{{ $template['name'] ?? 'N/A' }}</code></td>
                                <td>{{ $template['language'] ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $template['status'] === 'APPROVED' ? 'success' : 'warning' }}">
                                        {{ $template['status'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $template['category'] ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Conversations:</span>
                    <strong>{{ $whatsapp->inbox->conversations()->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Open Conversations:</span>
                    <strong>{{ $whatsapp->inbox->conversations()->where('status', 'open')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total Messages:</span>
                    <strong>{{ $whatsapp->inbox->messages()->count() }}</strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.conversation.index', ['inbox_id' => $whatsapp->inbox->id]) }}"
                   class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-chat-left-text"></i> View Conversations
                </a>
                <a href="{{ route('admin.channels.whatsapps.edit', $whatsapp) }}"
                   class="btn btn-outline-warning w-100 mb-2">
                    <i class="fa fa-edit"></i> Edit Settings
                </a>
                @if($whatsapp->isEvolutionApi())
                <a href="{{ route('admin.channels.whatsapps.settings', $whatsapp) }}"
                   class="btn btn-outline-info w-100 mb-2">
                    <i class="bi bi-sliders"></i> Advanced Settings
                </a>
                @endif
                <button type="button" class="btn btn-outline-danger w-100" onclick="confirmDelete()">
                    <i class="fa fa-trash"></i> Delete WhatsApp Channel
                </button>

                <form id="delete-form" action="{{ route('admin.channels.whatsapps.destroy', $whatsapp) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>

                <form id="sync-templates-form" action="{{ route('admin.channels.whatsapps.sync-templates', $whatsapp) }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    });
}

function confirmDelete() {
    if (confirm('Are you sure you want to delete this WhatsApp channel? This action cannot be undone.')) {
        $('#delete-form').submit();
    }
}

function syncTemplates() {
    if (confirm('Sync message templates from WhatsApp?')) {
        $('#sync-templates-form').submit();
    }
}

@if($whatsapp->isEvolutionApi())
// Evolution API - Check connection status
function checkConnectionStatus() {
    $.ajax({
        url: '{{ route('admin.channels.whatsapps.connection-status', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const $statusElement = $('#connection-status');
            const $qrCodeCard = $('#qr-code-card');

            if (data.state === 'open') {
                $statusElement
                    .html('<i class="fa fa-check-circle me-1"></i> Connected')
                    .removeClass()
                    .addClass('badge bg-success');
                $qrCodeCard.hide();
            } else if (data.state === 'close' || data.state === 'connecting') {
                $statusElement
                    .html('<i class="bi bi-exclamation-circle me-1"></i> Disconnected')
                    .removeClass()
                    .addClass('badge bg-danger');
                $qrCodeCard.show();
                loadQrCode();
            } else {
                $statusElement
                    .html('<i class="bi bi-question-circle me-1"></i> ' + data.state)
                    .removeClass()
                    .addClass('badge bg-warning');
            }
        },
        error: function(error) {
            console.error('Error checking connection status:', error);
            $('#connection-status')
                .html('<i class="fa fa-times-circle me-1"></i> Error')
                .removeClass()
                .addClass('badge bg-danger');
        }
    });
}

function loadQrCode() {
    const $qrContainer = $('#qr-code-container');
    $qrContainer.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading QR Code...</span></div>');

    $.ajax({
        url: '{{ route('admin.channels.whatsapps.qr-code', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.qrcode && data.qrcode.base64) {
                $qrContainer.html('<img src="' + data.qrcode.base64 + '" alt="QR Code" class="img-fluid" style="max-width: 300px;">');
            } else if (data.error) {
                $qrContainer.html('<div class="alert alert-warning">' + data.error + '</div>');
            } else {
                $qrContainer.html('<div class="alert alert-info">QR Code not available. Instance may already be connected.</div>');
            }
        },
        error: function(error) {
            console.error('Error loading QR code:', error);
            $qrContainer.html('<div class="alert alert-danger">Failed to load QR code. Please try again.</div>');
        }
    });
}

function refreshQrCode() {
    loadQrCode();
}

// Check connection status on page load
$(document).ready(function() {
    checkConnectionStatus();

    // Refresh status every 10 seconds
    setInterval(checkConnectionStatus, 10000);
});
@endif
</script>
@endpush
