@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Connect a Messaging Channel</h2>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        <p class="text-muted mb-4">
            Choose a channel to connect with your customers. Manage all your messaging channels from a single dashboard.
        </p>
    </div>
</div>

<div class="row">
    <!-- Facebook -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card channel-card h-100 cursor-pointer" onclick="navigateTo('{{ route('admin.channels.facebook-pages.create') }}')">
            <div class="card-body text-center py-5">
                <div class="channel-icon mb-3">
                    <i class="bi bi-facebook fs-1 text-primary"></i>
                </div>
                <h5 class="card-title">Facebook Pages</h5>
                <p class="card-text text-muted small mb-3">
                    Connect your Facebook Pages to receive and send messages from Messenger.
                </p>

                @if(\App\Services\ChannelCredentialsValidator::isConfigured('facebook'))
                    <span class="badge bg-success mb-3">
                        <i class="fa fa-check-circle"></i> Configured
                    </span>
                @else
                    <span class="badge bg-danger mb-3">
                        <i class="bi bi-exclamation-circle"></i> Not Configured
                    </span>
                @endif

                <div class="d-grid">
                    @if(\App\Services\ChannelCredentialsValidator::isConfigured('facebook'))
                        <button type="button" class="btn btn-primary btn-sm" onclick="navigateTo('{{ route('admin.channels.facebook-pages.create') }}')">
                            <i class="fa fa-plus-circle"></i> Add Facebook
                        </button>
                    @else
                        <a href="{{ route('admin.settings.channel-credentials.show', 'facebook') }}" class="btn btn-warning btn-sm">
                            <i class="bi bi-gear"></i> Setup First
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Instagram -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card channel-card h-100 cursor-pointer" onclick="navigateTo('{{ route('admin.channels.instagrams.create') }}')">
            <div class="card-body text-center py-5">
                <div class="channel-icon mb-3">
                    <i class="bi bi-instagram fs-1 text-danger"></i>
                </div>
                <h5 class="card-title">Instagram DM</h5>
                <p class="card-text text-muted small mb-3">
                    Connect your Instagram Business Account to manage direct messages.
                </p>

                @if(\App\Services\ChannelCredentialsValidator::isConfigured('instagram'))
                    <span class="badge bg-success mb-3">
                        <i class="fa fa-check-circle"></i> Configured
                    </span>
                @else
                    <span class="badge bg-danger mb-3">
                        <i class="bi bi-exclamation-circle"></i> Not Configured
                    </span>
                @endif

                <div class="d-grid">
                    @if(\App\Services\ChannelCredentialsValidator::isConfigured('instagram'))
                        <button type="button" class="btn btn-primary btn-sm" onclick="navigateTo('{{ route('admin.channels.instagrams.create') }}')">
                            <i class="fa fa-plus-circle"></i> Add Instagram
                        </button>
                    @else
                        <a href="{{ route('admin.settings.channel-credentials.show', 'instagram') }}" class="btn btn-warning btn-sm">
                            <i class="bi bi-gear"></i> Setup First
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card channel-card h-100 cursor-pointer" onclick="navigateTo('{{ route('admin.channels.whatsapps.create') }}')">
            <div class="card-body text-center py-5">
                <div class="channel-icon mb-3">
                    <i class="bi bi-whatsapp fs-1 text-success"></i>
                </div>
                <h5 class="card-title">WhatsApp Business</h5>
                <p class="card-text text-muted small mb-3">
                    Connect your WhatsApp Business Account via Cloud API or 360Dialog.
                </p>

                @if(\App\Services\ChannelCredentialsValidator::isConfigured('whatsapp'))
                    <span class="badge bg-success mb-3">
                        <i class="fa fa-check-circle"></i> Configured
                    </span>
                @else
                    <span class="badge bg-warning mb-3">
                        <i class="bi bi-exclamation-circle"></i> Manual Setup
                    </span>
                @endif

                <div class="d-grid">
                    <button type="button" class="btn btn-primary btn-sm" onclick="navigateTo('{{ route('admin.channels.whatsapps.create') }}')">
                        <i class="fa fa-plus-circle"></i> Add WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Web Widget -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card channel-card h-100 cursor-pointer" onclick="navigateTo('{{ route('admin.channels.webs.create') }}')">
            <div class="card-body text-center py-5">
                <div class="channel-icon mb-3">
                    <i class="bi bi-chat-dots fs-1 text-info"></i>
                </div>
                <h5 class="card-title">Web Widget</h5>
                <p class="card-text text-muted small mb-3">
                    Embed a chat widget on your website for direct customer communication.
                </p>

                <span class="badge bg-success mb-3">
                    <i class="fa fa-check-circle"></i> Always Available
                </span>

                <div class="d-grid">
                    <button type="button" class="btn btn-primary btn-sm" onclick="navigateTo('{{ route('admin.channels.webs.create') }}')">
                        <i class="fa fa-plus-circle"></i> Add Widget
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Email -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card channel-card h-100 cursor-pointer" onclick="navigateTo('{{ route('admin.channels.emails.create') }}')">
            <div class="card-body text-center py-5">
                <div class="channel-icon mb-3">
                    <i class="bi bi-envelope fs-1" style="color: #EA4335;"></i>
                </div>
                <h5 class="card-title">Email</h5>
                <p class="card-text text-muted small mb-3">
                    Connect Gmail, Outlook or any IMAP/SMTP email account.
                </p>

                <span class="badge bg-success mb-3">
                    <i class="fa fa-check-circle"></i> Always Available
                </span>

                <div class="d-grid">
                    <button type="button" class="btn btn-primary btn-sm" onclick="navigateTo('{{ route('admin.channels.emails.create') }}')">
                        <i class="fa fa-plus-circle"></i> Add Email
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Coming Soon Channels -->
<div class="row mt-5">
    <div class="col-12">
        <h5 class="mb-4">Coming Soon</h5>
    </div>

    <!-- Telegram -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card channel-card h-100 opacity-50">
            <div class="card-body text-center py-5">
                <div class="channel-icon mb-3">
                    <i class="bi bi-telegram fs-1 text-secondary"></i>
                </div>
                <h5 class="card-title">Telegram Bot</h5>
                <p class="card-text text-muted small mb-3">
                    Create a Telegram Bot to interact with users.
                </p>
                <span class="badge bg-secondary">Coming Soon 🚀</span>
            </div>
        </div>
    </div>

    <!-- SMS -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card channel-card h-100 opacity-50">
            <div class="card-body text-center py-5">
                <div class="channel-icon mb-3">
                    <i class="bi bi-telephone fs-1 text-secondary"></i>
                </div>
                <h5 class="card-title">SMS</h5>
                <p class="card-text text-muted small mb-3">
                    Send and receive SMS messages via Twilio or Bandwidth.
                </p>
                <span class="badge bg-secondary">Coming Soon 🚀</span>
            </div>
        </div>
    </div>

    <!-- API -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card channel-card h-100 opacity-50">
            <div class="card-body text-center py-5">
                <div class="channel-icon mb-3">
                    <i class="bi bi-code-square fs-1 text-secondary"></i>
                </div>
                <h5 class="card-title">Custom API</h5>
                <p class="card-text text-muted small mb-3">
                    Build custom integrations using our REST API.
                </p>
                <span class="badge bg-secondary">Coming Soon 🚀</span>
            </div>
        </div>
    </div>
</div>

<!-- Info Section -->
<div class="row mt-5">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-lightbulb"></i> Why Multiple Channels?</h5>
                <ul>
                    <li><strong>Unified Inbox:</strong> Manage all conversations in one place</li>
                    <li><strong>Team Collaboration:</strong> Assign conversations to team members</li>
                    <li><strong>Automation:</strong> Set up auto-replies and routing rules</li>
                    <li><strong>Analytics:</strong> Track response times and conversation metrics</li>
                    <li><strong>Customization:</strong> Personalize each channel with branding</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-question-circle"></i> Need Help?</h5>
                <p class="small mb-3">
                    Check our documentation for detailed setup guides for each channel.
                </p>
                <a href="{{ route('admin.settings.channel-credentials.index') }}" class="btn btn-sm btn-outline-primary">
                    View Setup Guides
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.channel-card {
    transition: all 0.3s ease;
    border: 2px solid #f0f0f0;
}

.channel-card:hover {
    border-color: #007bff;
    box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.15);
    transform: translateY(-2px);
}

.channel-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.channel-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(0, 123, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.channel-card:hover .channel-icon {
    background: rgba(0, 123, 255, 0.2);
}
</style>
@endpush

@push('scripts')
<script>
function navigateTo(url) {
    window.location.href = url;
}
</script>
@endpush
