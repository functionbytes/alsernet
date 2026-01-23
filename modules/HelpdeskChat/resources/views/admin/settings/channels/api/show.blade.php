@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4>API Channel Details</h4>
                    <div>
                        <a href="{{ route('admin.channels.api.edit', $api) }}" class="btn btn-warning btn-sm">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.channels.api.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <h5 class="mb-3">Channel Information</h5>
                <table class="table">
                    <tr>
                        <th style="width: 200px;">Inbox Name:</th>
                        <td>{{ $api->inbox->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Identifier:</th>
                        <td><code>{{ $api->identifier }}</code></td>
                    </tr>
                    <tr>
                        <th>Webhook URL:</th>
                        <td>{{ $api->webhook_url ?? 'Not configured' }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            @if($api->active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Created:</th>
                        <td>{{ $api->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Last Updated:</th>
                        <td>{{ $api->updated_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                </table>

                @if($api->additional_settings)
                    <div class="mt-3">
                        <h6>Additional Settings:</h6>
                        <pre class="bg-light p-3 rounded"><code>{{ json_encode(json_decode($api->additional_settings), JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                @endif

                <hr class="my-4">

                <h5 class="mb-3">API Integration</h5>

                <div class="mb-4">
                    <label class="form-label fw-bold">Incoming Messages Webhook URL</label>
                    <p class="text-muted small">Configure your system to send messages to this URL:</p>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="incomingWebhookUrl" value="{{ $incomingWebhookUrl }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('incomingWebhookUrl')">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">HMAC Token</label>
                    <p class="text-muted small">Use this token to sign your API requests for authentication:</p>
                    <div class="input-group mb-2">
                        <input type="password" class="form-control font-monospace" id="hmacToken" value="{{ $api->hmac_token }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="toggleTokenVisibility()">
                            <i class="fa fa-eye" id="toggleIcon"></i>
                        </button>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('hmacToken')">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <button class="btn btn-warning btn-sm" type="button" onclick="regenerateHmacToken()">
                        <i class="bi bi-arrow-clockwise"></i> Regenerate HMAC Token
                    </button>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Webhook Verify Token</label>
                    <p class="text-muted small">Use this token to verify webhook callbacks:</p>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="verifyToken" value="{{ $api->webhook_verify_token }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('verifyToken')">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                </div>

                <hr class="my-4">

                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Integration Guide</h6>
                    <p class="mb-2"><strong>To send a message to this channel:</strong></p>
                    <ol class="mb-2 small">
                        <li>Send a POST request to the "Incoming Messages Webhook URL" above</li>
                        <li>Include the HMAC signature in the request headers</li>
                        <li>Format the message payload according to the API documentation</li>
                    </ol>
                    <p class="mb-0 small"><strong>Need help?</strong> Check the API documentation for detailed integration examples.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    const originalType = input.type;

    // Temporarily change type to text to allow selection
    input.type = 'text';
    input.select();
    document.execCommand('copy');
    input.type = originalType;

    // Show feedback
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-check"></i> Copied!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');

    setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}

function toggleTokenVisibility() {
    const input = document.getElementById('hmacToken');
    const icon = document.getElementById('toggleIcon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function regenerateHmacToken() {
    if (!confirm('Are you sure you want to regenerate the HMAC token? This will invalidate the current token and may break existing integrations.')) {
        return;
    }

    $.ajax({
        url: '{{ route('admin.channels.api.regenerate-hmac', $api) }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#hmacToken').val(response.hmac_token);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('Error regenerating token. Please try again.');
        }
    });
}
</script>
@endpush
