@extends('layouts.theme')

@section('title', 'Notification settings')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-bell text-primary"></i> Notification settings
                </h2>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('settings.chat.notifications.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="mb-4">
                                    <h5 class="card-title mb-3">
                                        <i class="fas fa-envelope text-primary"></i> Email notifications
                                    </h5>
                                    <p class="text-muted small mb-3">
                                        Choose which events trigger email notifications
                                    </p>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_conversation_assigned"
                                               name="email[conversation_assigned]" value="1"
                                               {{ $settings['email']['conversation_assigned'] ?? false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_conversation_assigned">
                                            <strong>Conversation assigned</strong>
                                            <br>
                                            <small class="text-muted">Receive an email when a conversation is assigned to you</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_new_message"
                                               name="email[new_message]" value="1"
                                               {{ $settings['email']['new_message'] ?? false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_new_message">
                                            <strong>New message</strong>
                                            <br>
                                            <small class="text-muted">Receive an email when you get a new message</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_conversation_mention"
                                               name="email[conversation_mention]" value="1"
                                               {{ $settings['email']['conversation_mention'] ?? false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_conversation_mention">
                                            <strong>Mentioned in conversation</strong>
                                            <br>
                                            <small class="text-muted">Receive an email when someone mentions you</small>
                                        </label>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-4">
                                    <h5 class="card-title mb-3">
                                        <i class="fas fa-desktop text-primary"></i> Browser notifications
                                    </h5>
                                    <p class="text-muted small mb-3">
                                        Choose which events trigger browser notifications
                                    </p>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="browser_conversation_assigned"
                                               name="browser[conversation_assigned]" value="1"
                                               {{ $settings['browser']['conversation_assigned'] ?? false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="browser_conversation_assigned">
                                            <strong>Conversation assigned</strong>
                                            <br>
                                            <small class="text-muted">Show a browser notification when a conversation is assigned to you</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="browser_new_message"
                                               name="browser[new_message]" value="1"
                                               {{ $settings['browser']['new_message'] ?? false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="browser_new_message">
                                            <strong>New message</strong>
                                            <br>
                                            <small class="text-muted">Show a browser notification when you get a new message</small>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="browser_conversation_mention"
                                               name="browser[conversation_mention]" value="1"
                                               {{ $settings['browser']['conversation_mention'] ?? false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="browser_conversation_mention">
                                            <strong>Mentioned in conversation</strong>
                                            <br>
                                            <small class="text-muted">Show a browser notification when someone mentions you</small>
                                        </label>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-4">
                                    <h5 class="card-title mb-3">
                                        <i class="fas fa-volume-up text-primary"></i> Sound settings
                                    </h5>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="sound"
                                               name="sound" value="1"
                                               {{ $settings['sound'] ?? false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="sound">
                                            <strong>Enable notification sounds</strong>
                                            <br>
                                            <small class="text-muted">Play a sound when you receive a notification</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check-circle"></i> Save settings
                                    </button>
                                    <a href="{{ route('settings.chat.configurations.global') }}" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-info-circle"></i> About notifications
                            </h6>
                            <p class="small mb-3">
                                Control how and when you receive notifications about conversations and messages.
                            </p>

                            <h6 class="mt-4">Email notifications</h6>
                            <p class="small">
                                Emails are sent to <strong>{{ Auth::user()->email }}</strong>.
                                Update your email in profile settings.
                            </p>

                            <h6 class="mt-4">Browser notifications</h6>
                            <p class="small mb-2">
                                Browser notifications appear as desktop alerts even when you're not actively viewing the page.
                            </p>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="requestPermissionBtn">
                                <i class="fas fa-bell"></i> Request browser permission
                            </button>
                            <p class="small text-muted mt-2">
                                Current permission: <span id="permissionStatus" class="badge bg-secondary">Checking...</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    var $permissionStatus = $('#permissionStatus');
    var $requestBtn = $('#requestPermissionBtn');

    function updatePermissionStatus() {
        if (!('Notification' in window)) {
            $permissionStatus.text('Not supported').removeClass().addClass('badge bg-danger');
            $requestBtn.prop('disabled', true);
            return;
        }

        var permission = Notification.permission;
        if (permission === 'granted') {
            $permissionStatus.text('Granted').removeClass().addClass('badge bg-success');
            $requestBtn.prop('disabled', true).html('<i class="fas fa-check-circle"></i> Permission granted');
        } else if (permission === 'denied') {
            $permissionStatus.text('Denied').removeClass().addClass('badge bg-danger');
            $requestBtn.prop('disabled', true).html('<i class="fas fa-times-circle"></i> Permission denied');
        } else {
            $permissionStatus.text('Not requested').removeClass().addClass('badge bg-warning');
        }
    }

    $requestBtn.on('click', function() {
        if ('Notification' in window) {
            Notification.requestPermission().then(function(permission) {
                updatePermissionStatus();
                if (permission === 'granted') {
                    new Notification('Notifications enabled!', {
                        body: 'You will now receive browser notifications.',
                        icon: '/favicon.ico'
                    });
                }
            });
        }
    });

    updatePermissionStatus();
});
</script>
@endpush
@endsection
