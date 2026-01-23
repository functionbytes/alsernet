@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>{{ $whatsapp->inbox->name }} - Advanced Settings</h2>
                <p class="text-muted mb-0">Configure Evolution API instance settings</p>
            </div>
            <div>
                <a href="{{ route('admin.channels.whatsapps.show', $whatsapp) }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="actions-tab" data-bs-toggle="tab" data-bs-target="#actions" type="button" role="tab">
                            <i class="bi bi-lightning-fill"></i> Instance Actions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="instance-tab" data-bs-toggle="tab" data-bs-target="#instance" type="button" role="tab">
                            <i class="bi bi-gear"></i> Instance Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="chatwoot-tab" data-bs-toggle="tab" data-bs-target="#chatwoot" type="button" role="tab">
                            <i class="bi bi-chat-dots"></i> Chatwoot Integration
                        </button>
                    </li>
                </ul>

                <form action="{{ route('admin.channels.whatsapps.update-settings', $whatsapp) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="tab-content" id="settingsTabContent">
                        <!-- Instance Actions Tab -->
                        <div class="tab-pane fade show active" id="actions" role="tabpanel">
                            <h5 class="mb-3">Connection & Sync</h5>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="bi bi-plug"></i> Connection</h6>
                                            <p class="card-text text-muted small">Manage WhatsApp connection</p>
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-outline-success" onclick="showQrCode()">
                                                    <i class="bi bi-qr-code"></i> Show QR Code
                                                </button>
                                                <form action="{{ route('admin.channels.whatsapps.restart', $whatsapp) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Restart instance?')">
                                                        <i class="bi bi-arrow-clockwise"></i> Restart Instance
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.channels.whatsapps.logout', $whatsapp) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Disconnect from WhatsApp?')">
                                                        <i class="bi bi-box-arrow-right"></i> Disconnect
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card mb-3" id="sync-card">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="bi bi-arrow-repeat"></i> Synchronization</h6>
                                            <p class="card-text text-muted small">Sync data from WhatsApp</p>
                                            <div id="sync-buttons" class="d-grid gap-2" style="display: none;">
                                                <form action="{{ route('admin.channels.whatsapps.sync-contacts', $whatsapp) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary w-100">
                                                        <i class="bi bi-people"></i> Sync Contacts
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.channels.whatsapps.sync-chats', $whatsapp) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary w-100">
                                                        <i class="bi bi-chat-left-dots"></i> Sync Chats
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.channels.whatsapps.sync-messages', $whatsapp) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="limit" value="50">
                                                    <button type="submit" class="btn btn-outline-success w-100" onclick="return confirm('This will sync the last 50 messages from each conversation. This may take a while. Continue?')">
                                                        <i class="bi bi-envelope-fill"></i> Sync Messages (Last 50)
                                                    </button>
                                                </form>
                                            </div>
                                            <div id="sync-disabled-message" class="alert alert-warning mb-0">
                                                <i class="bi bi-exclamation-triangle"></i> Connect WhatsApp first to enable synchronization
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code Modal Section -->
                            <div id="qr-code-section" style="display:none;" class="mb-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="bi bi-qr-code"></i> Connect WhatsApp</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <p class="mb-3">Scan this QR code with WhatsApp to connect:</p>
                                        <div id="qr-code-display" class="mb-3">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <div class="alert alert-info text-start">
                                            <strong>Instructions:</strong>
                                            <ol class="mb-0">
                                                <li>Open WhatsApp on your phone</li>
                                                <li>Go to Settings → Linked Devices</li>
                                                <li>Tap "Link a Device"</li>
                                                <li>Scan this QR code</li>
                                            </ol>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="refreshQrCodeInActions()">
                                            <i class="bi bi-arrow-clockwise"></i> Refresh QR Code
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="hideQrCode()">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mb-3">Instance Information</h5>
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Instance Name:</strong> <code>{{ $whatsapp->getInstanceName() }}</code></p>
                                            <p><strong>Phone Number:</strong> <code>{{ $whatsapp->phone_number }}</code></p>
                                            <p><strong>API URL:</strong> <code>{{ $whatsapp->getApiUrl() }}</code></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Connection Status:</strong> <span id="status-badge" class="badge bg-secondary">Checking...</span></p>
                                            <p><strong>Created:</strong> {{ $whatsapp->created_at->format('F d, Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Instance Settings Tab -->
                        <div class="tab-pane fade" id="instance" role="tabpanel">
                            <h5 class="mb-3">Instance Behavior</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="reject_call" name="reject_call" value="1"
                                                {{ ($settings['rejectCall'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="reject_call">
                                                <strong>Reject Calls</strong>
                                                <br>
                                                <small class="text-muted">Automatically reject incoming WhatsApp calls</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3" id="msg_call_container">
                                        <label for="msg_call" class="form-label">Call Rejection Message</label>
                                        <textarea class="form-control" id="msg_call" name="msg_call" rows="2"
                                            placeholder="Sorry, I can't answer calls right now.">{{ $settings['msgCall'] ?? '' }}</textarea>
                                        <small class="text-muted">Message sent when rejecting a call</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="groups_ignore" name="groups_ignore" value="1"
                                                {{ ($settings['groupsIgnore'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="groups_ignore">
                                                <strong>Ignore Groups</strong>
                                                <br>
                                                <small class="text-muted">Don't process messages from WhatsApp groups</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="always_online" name="always_online" value="1"
                                                {{ ($settings['alwaysOnline'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="always_online">
                                                <strong>Always Online</strong>
                                                <br>
                                                <small class="text-muted">Show as always online in WhatsApp</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="read_messages" name="read_messages" value="1"
                                                {{ ($settings['readMessages'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="read_messages">
                                                <strong>Read Messages</strong>
                                                <br>
                                                <small class="text-muted">Automatically mark received messages as read</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="read_status" name="read_status" value="1"
                                                {{ ($settings['readStatus'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="read_status">
                                                <strong>Read Status</strong>
                                                <br>
                                                <small class="text-muted">Receive and process WhatsApp status updates</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="sync_full_history" name="sync_full_history" value="1"
                                                {{ ($settings['syncFullHistory'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sync_full_history">
                                                <strong>Sync Full History</strong>
                                                <br>
                                                <small class="text-muted">Sync complete message history on connection</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chatwoot Integrations Tab -->
                        <div class="tab-pane fade" id="chatwoot" role="tabpanel">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="chatwoot_enabled" name="chatwoot_enabled" value="1"
                                        {{ ($chatwootSettings['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="chatwoot_enabled">
                                        <strong>Enable Chatwoot Integration</strong>
                                        <br>
                                        <small class="text-muted">Forward messages to Chatwoot instance</small>
                                    </label>
                                </div>
                            </div>

                            <div id="chatwoot-settings-container">
                                <h5 class="mb-3">Connection Settings</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="chatwoot_url" class="form-label">Chatwoot URL <span class="text-danger">*</span></label>
                                            <input type="url" class="form-control" id="chatwoot_url" name="chatwoot_url"
                                                value="{{ $chatwootSettings['url'] ?? '' }}" placeholder="https://app.chatwoot.com">
                                        </div>

                                        <div class="mb-3">
                                            <label for="chatwoot_account_id" class="form-label">Account ID <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="chatwoot_account_id" name="chatwoot_account_id"
                                                value="{{ $chatwootSettings['accountId'] ?? '' }}" placeholder="123">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="chatwoot_token" class="form-label">API Token <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="chatwoot_token" name="chatwoot_token"
                                                value="{{ $chatwootSettings['token'] ?? '' }}" placeholder="your-api-token">
                                        </div>

                                        <div class="mb-3">
                                            <label for="chatwoot_name_inbox" class="form-label">Inbox Name</label>
                                            <input type="text" class="form-control" id="chatwoot_name_inbox" name="chatwoot_name_inbox"
                                                value="{{ $chatwootSettings['nameInbox'] ?? $whatsapp->inbox->name }}">
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mb-3 mt-4">Message Settings</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="chatwoot_sign_msg" name="chatwoot_sign_msg" value="1"
                                                    {{ ($chatwootSettings['signMsg'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="chatwoot_sign_msg">
                                                    <strong>Sign Messages</strong>
                                                    <br>
                                                    <small class="text-muted">Add agent signature to outgoing messages</small>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="chatwoot_reopen_conversation" name="chatwoot_reopen_conversation" value="1"
                                                    {{ ($chatwootSettings['reopenConversation'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="chatwoot_reopen_conversation">
                                                    <strong>Reopen Conversation</strong>
                                                    <br>
                                                    <small class="text-muted">Automatically reopen resolved conversations</small>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="chatwoot_conversation_pending" name="chatwoot_conversation_pending" value="1"
                                                    {{ ($chatwootSettings['conversationPending'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="chatwoot_conversation_pending">
                                                    <strong>Set Conversations as Pending</strong>
                                                    <br>
                                                    <small class="text-muted">New conversations start as pending instead of open</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="chatwoot_import_contacts" name="chatwoot_import_contacts" value="1"
                                                    {{ ($chatwootSettings['importContacts'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="chatwoot_import_contacts">
                                                    <strong>Import Contacts</strong>
                                                    <br>
                                                    <small class="text-muted">Sync WhatsApp contacts to Chatwoot</small>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="chatwoot_import_messages" name="chatwoot_import_messages" value="1"
                                                    {{ ($chatwootSettings['importMessages'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="chatwoot_import_messages">
                                                    <strong>Import Messages</strong>
                                                    <br>
                                                    <small class="text-muted">Import existing WhatsApp messages to Chatwoot</small>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3" id="import_days_container">
                                            <label for="chatwoot_days_limit_import_messages" class="form-label">Days Limit for Import</label>
                                            <input type="number" class="form-control" id="chatwoot_days_limit_import_messages" name="chatwoot_days_limit_import_messages"
                                                value="{{ $chatwootSettings['daysLimitImportMessages'] ?? 60 }}" min="1" max="365">
                                            <small class="text-muted">Number of days of message history to import</small>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mb-3 mt-4">Advanced Settings</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="chatwoot_merge_brazil_contacts" name="chatwoot_merge_brazil_contacts" value="1"
                                                    {{ ($chatwootSettings['mergeBrazilContacts'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="chatwoot_merge_brazil_contacts">
                                                    <strong>Merge Brazil Contacts</strong>
                                                    <br>
                                                    <small class="text-muted">Merge contacts with Brazilian phone number format variations</small>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="chatwoot_organization" class="form-label">Organization</label>
                                            <input type="text" class="form-control" id="chatwoot_organization" name="chatwoot_organization"
                                                value="{{ $chatwootSettings['organization'] ?? '' }}" placeholder="Your Organization">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="chatwoot_logo" class="form-label">Logo URL</label>
                                            <input type="url" class="form-control" id="chatwoot_logo" name="chatwoot_logo"
                                                value="{{ $chatwootSettings['logo'] ?? '' }}" placeholder="https://example.com/logo.png">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check-circle"></i> Save Settings
                        </button>
                        <a href="{{ route('admin.channels.whatsapps.show', $whatsapp) }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// QR Code functions
let connectionCheckInterval = null;

function showQrCode() {
    $('#qr-code-section').show();
    loadQrCodeInActions();
    startConnectionPolling();
}

function hideQrCode() {
    $('#qr-code-section').hide();
    stopConnectionPolling();
}

function startConnectionPolling() {
    stopConnectionPolling();
    checkConnectionAfterQR();
    connectionCheckInterval = setInterval(checkConnectionAfterQR, 3000);
}

function stopConnectionPolling() {
    if (connectionCheckInterval) {
        clearInterval(connectionCheckInterval);
        connectionCheckInterval = null;
    }
}

function checkConnectionAfterQR() {
    $.ajax({
        url: '{{ route('admin.channels.whatsapps.connection-status', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Connection status response:', data);
            const state = data.state || (data.instance && data.instance.state) || 'unknown';
            const $statusBadge = $('#status-badge');

            if ($statusBadge.length) {
                if (state === 'open') {
                    $statusBadge.html('<i class="fa fa-check-circle me-1"></i> Connected').removeClass().addClass('badge bg-success');
                } else if (state === 'close' || state === 'connecting') {
                    const msg = state === 'connecting' ? 'Connecting...' : 'Disconnected';
                    const cls = state === 'connecting' ? 'badge bg-warning' : 'badge bg-danger';
                    $statusBadge.html(`<i class="bi bi-exclamation-circle me-1"></i> ${msg}`).removeClass().addClass(cls);
                } else {
                    $statusBadge.html(`<i class="bi bi-question-circle me-1"></i> ${state}`).removeClass().addClass('badge bg-warning');
                }
            }

            const $syncButtons = $('#sync-buttons');
            const $syncMessage = $('#sync-disabled-message');
            if (state === 'open') {
                $syncButtons.show();
                $syncMessage.hide();
            } else {
                $syncButtons.hide();
                $syncMessage.show();
            }

            if (state === 'open') {
                const $qrDisplay = $('#qr-code-display');
                if ($qrDisplay.find('.spinner-border').length) {
                    $qrDisplay.html(`
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle-fill fs-1 d-block mb-3"></i>
                            <h5>Successfully Connected!</h5>
                            <p class="mb-0">Your WhatsApp instance is now connected and ready to use.</p>
                        </div>
                        <button type="button" class="btn btn-primary mt-3" onclick="hideQrCode(); location.reload();">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Page
                        </button>
                    `);
                }
                stopConnectionPolling();
            }
        },
        error: function(error) {
            console.error('Error checking connection status:', error);
        }
    });
}

function loadQrCodeInActions() {
    const $qrDisplay = $('#qr-code-display');
    $qrDisplay.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');

    $.ajax({
        url: '{{ route('admin.channels.whatsapps.qr-code', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('QR Code response:', data);

            if (data.error) {
                $qrDisplay.html('<div class="alert alert-warning">' + data.error + '</div>');
                return;
            }

            let qrBase64 = null;
            let pairingCode = null;

            if (data.qrcode && data.qrcode.base64) {
                qrBase64 = data.qrcode.base64;
                pairingCode = data.qrcode.pairingCode;
            } else if (data.base64) {
                qrBase64 = data.base64;
                pairingCode = data.pairingCode;
            } else if (data.qrcode && data.qrcode.code) {
                qrBase64 = data.qrcode.code;
                pairingCode = data.qrcode.pairingCode;
            } else if (data.code) {
                qrBase64 = data.code;
                pairingCode = data.pairingCode;
            }

            if (qrBase64) {
                if (!qrBase64.startsWith('data:image')) {
                    qrBase64 = 'data:image/png;base64,' + qrBase64;
                }
                $qrDisplay.html('<img src="' + qrBase64 + '" alt="QR Code" class="img-fluid" style="max-width: 400px;">');

                if (pairingCode) {
                    $qrDisplay.append('<div class="alert alert-info mt-3"><strong>Pairing Code:</strong> ' + pairingCode + '</div>');
                }
            } else if (pairingCode) {
                $qrDisplay.html('<div class="alert alert-info"><strong>Pairing Code:</strong> <code class="fs-4">' + pairingCode + '</code><br><small>Enter this code in WhatsApp → Linked Devices → Link with phone number</small></div>');
            } else if (data.instance && data.instance.status === 'open') {
                $qrDisplay.html('<div class="alert alert-success"><i class="fa fa-check-circle"></i> Instance is already connected!</div>');
            } else {
                $qrDisplay.html('<div class="alert alert-info">QR Code not available. Instance may already be connected or still connecting.</div>');
            }
        },
        error: function(error) {
            console.error('Error loading QR code:', error);
            $qrDisplay.html('<div class="alert alert-danger">Failed to load QR code: ' + error.message + '</div>');
        }
    });
}

function refreshQrCodeInActions() {
    loadQrCodeInActions();
}

function checkConnectionStatusInActions() {
    $.ajax({
        url: '{{ route('admin.channels.whatsapps.connection-status', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Initial connection check:', data);
            const state = data.state || (data.instance && data.instance.state) || 'unknown';
            const $statusBadge = $('#status-badge');

            if ($statusBadge.length) {
                if (state === 'open') {
                    $statusBadge.html('<i class="fa fa-check-circle me-1"></i> Connected').removeClass().addClass('badge bg-success');
                } else if (state === 'close' || state === 'connecting') {
                    const msg = state === 'connecting' ? 'Connecting...' : 'Disconnected';
                    const cls = state === 'connecting' ? 'badge bg-warning' : 'badge bg-danger';
                    $statusBadge.html(`<i class="bi bi-exclamation-circle me-1"></i> ${msg}`).removeClass().addClass(cls);
                } else {
                    $statusBadge.html(`<i class="bi bi-question-circle me-1"></i> ${state}`).removeClass().addClass('badge bg-warning');
                }
            }

            const $syncButtons = $('#sync-buttons');
            const $syncMessage = $('#sync-disabled-message');
            if (state === 'open') {
                $syncButtons.show();
                $syncMessage.hide();
            } else {
                $syncButtons.hide();
                $syncMessage.show();
            }
        },
        error: function(error) {
            console.error('Error checking connection status:', error);
            $('#status-badge').html('<i class="fa fa-times-circle me-1"></i> Error').removeClass().addClass('badge bg-danger');
        }
    });
}

$(document).ready(function() {
    // Show/hide call rejection message field
    $('#reject_call').on('change', function() {
        $('#msg_call_container').toggle(this.checked);
    });

    // Show/hide Chatwoot settings
    $('#chatwoot_enabled').on('change', function() {
        $('#chatwoot-settings-container').toggle(this.checked);
    });

    // Show/hide import days limit
    $('#chatwoot_import_messages').on('change', function() {
        $('#import_days_container').toggle(this.checked);
    });

    // Initialize visibility on page load
    $('#msg_call_container').toggle($('#reject_call').is(':checked'));
    $('#chatwoot-settings-container').toggle($('#chatwoot_enabled').is(':checked'));
    $('#import_days_container').toggle($('#chatwoot_import_messages').is(':checked'));

    // Check connection status on load
    checkConnectionStatusInActions();

    // Refresh status every 10 seconds
    setInterval(checkConnectionStatusInActions, 10000);
});
</script>
@endpush
