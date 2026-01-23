@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Create Web Widget</h2>
            <a href="{{ route('admin.channels.webs.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>

<form action="{{ route('admin.channels.webs.store') }}" method="POST" id="widgetForm">
    @csrf

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <!-- Tabs for organizing settings -->
                    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button">
                                <i class="bi bi-info-circle"></i> Basic
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button">
                                <i class="bi bi-palette"></i> Appearance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="prechat-tab" data-bs-toggle="tab" data-bs-target="#prechat" type="button">
                                <i class="bi bi-chat-dots"></i> Pre-Chat
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="offline-tab" data-bs-toggle="tab" data-bs-target="#offline" type="button">
                                <i class="bi bi-moon"></i> Offline
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="settingsTabsContent">
                        <!-- Basic Settings -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel">
                            <div class="mb-3">
                                <label for="name" class="form-label">Inbox Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                <div class="form-text">Name of the inbox for this widget.</div>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="website_url" class="form-label">Website URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control @error('website_url') is-invalid @enderror"
                                       id="website_url" name="website_url" value="{{ old('website_url') }}"
                                       placeholder="https://example.com" required>
                                <div class="form-text">URL where this widget will be installed.</div>
                                @error('website_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="welcome_title" class="form-label">Welcome Title</label>
                                <input type="text" class="form-control @error('welcome_title') is-invalid @enderror"
                                       id="welcome_title" name="welcome_title" value="{{ old('welcome_title', 'Hello!') }}"
                                       placeholder="Hello!" data-preview="welcome-title">
                                <div class="form-text">Greeting message shown when widget opens.</div>
                                @error('welcome_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="welcome_tagline" class="form-label">Welcome Tagline</label>
                                <textarea class="form-control @error('welcome_tagline') is-invalid @enderror"
                                          id="welcome_tagline" name="welcome_tagline" rows="2"
                                          placeholder="We're here to help!" data-preview="welcome-tagline">{{ old('welcome_tagline') }}</textarea>
                                <div class="form-text">Additional message below the welcome title.</div>
                                @error('welcome_tagline')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="reply_time_message" class="form-label">Reply Time Message</label>
                                <input type="text" class="form-control @error('reply_time_message') is-invalid @enderror"
                                       id="reply_time_message" name="reply_time_message"
                                       value="{{ old('reply_time_message') }}"
                                       placeholder="We typically reply in under 5 minutes">
                                <div class="form-text">Optional message about expected reply time.</div>
                                @error('reply_time_message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Appearance Settings -->
                        <div class="tab-pane fade" id="appearance" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="widget_color" class="form-label">Primary Color</label>
                                    <input type="color" class="form-control form-control-color @error('widget_color') is-invalid @enderror"
                                           id="widget_color" name="widget_color" value="{{ old('widget_color', '#1f93ff') }}"
                                           data-preview="widget-color">
                                    <div class="form-text">Main widget color.</div>
                                    @error('widget_color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="widget_bubble_color" class="form-label">Bubble Color</label>
                                    <input type="color" class="form-control form-control-color @error('widget_bubble_color') is-invalid @enderror"
                                           id="widget_bubble_color" name="widget_bubble_color"
                                           value="{{ old('widget_bubble_color', '#1f93ff') }}"
                                           data-preview="bubble-color">
                                    <div class="form-text">Launcher bubble color.</div>
                                    @error('widget_bubble_color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="widget_position" class="form-label">Widget Position</label>
                                <select class="form-select @error('widget_position') is-invalid @enderror"
                                        id="widget_position" name="widget_position" data-preview="widget-position">
                                    <option value="right" {{ old('widget_position') === 'right' ? 'selected' : '' }}>Bottom Right</option>
                                    <option value="left" {{ old('widget_position') === 'left' ? 'selected' : '' }}>Bottom Left</option>
                                </select>
                                <div class="form-text">Where the widget bubble will appear.</div>
                                @error('widget_position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="widget_bubble_launcher_title" class="form-label">Launcher Title</label>
                                <input type="text" class="form-control @error('widget_bubble_launcher_title') is-invalid @enderror"
                                       id="widget_bubble_launcher_title" name="widget_bubble_launcher_title"
                                       value="{{ old('widget_bubble_launcher_title') }}"
                                       placeholder="Chat with us!" data-preview="launcher-title">
                                <div class="form-text">Text shown next to launcher bubble (optional).</div>
                                @error('widget_bubble_launcher_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="widget_launcher_icon" class="form-label">Launcher Icon</label>
                                <input type="text" class="form-control @error('widget_launcher_icon') is-invalid @enderror"
                                       id="widget_launcher_icon" name="widget_launcher_icon"
                                       value="{{ old('widget_launcher_icon', '💬') }}"
                                       placeholder="💬">
                                <div class="form-text">Emoji or icon in the launcher bubble.</div>
                                @error('widget_launcher_icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="show_powered_by"
                                       name="show_powered_by" value="1" {{ old('show_powered_by', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_powered_by">
                                    Show "Powered by" branding
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="show_availability_status"
                                       name="show_availability_status" value="1" {{ old('show_availability_status', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_availability_status">
                                    Show availability status (online/offline)
                                </label>
                            </div>
                        </div>

                        <!-- Pre-Chat Form Settings -->
                        <div class="tab-pane fade" id="prechat" role="tabpanel">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="pre_chat_form_enabled"
                                       name="pre_chat_form_enabled" value="1" {{ old('pre_chat_form_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="pre_chat_form_enabled">
                                    <strong>Enable Pre-chat Form</strong>
                                </label>
                            </div>
                            <div class="form-text mb-3">Collect visitor information before starting conversation.</div>

                            <div id="pre-chat-options" style="display:none;">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    The form collects name and email by default when enabled.
                                </div>
                            </div>
                        </div>

                        <!-- Offline Messages Settings -->
                        <div class="tab-pane fade" id="offline" role="tabpanel">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="offline_message_enabled"
                                       name="offline_message_enabled" value="1" {{ old('offline_message_enabled', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="offline_message_enabled">
                                    <strong>Enable Offline Messages</strong>
                                </label>
                            </div>
                            <div class="form-text mb-3">Allow visitors to leave messages when no agents available.</div>

                            <div id="offline-options">
                                <div class="mb-3">
                                    <label for="offline_message" class="form-label">Offline Message</label>
                                    <textarea class="form-control @error('offline_message') is-invalid @enderror"
                                              id="offline_message" name="offline_message" rows="3"
                                              placeholder="We're currently offline. Leave us a message!">{{ old('offline_message') }}</textarea>
                                    <div class="form-text">Message shown when all agents are offline.</div>
                                    @error('offline_message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.channels.webs.index') }}" class="btn btn-secondary">
                            <i class="fa fa-times-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check-circle"></i> Create Web Widget
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Preview -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa fa-eye"></i> Live Preview</h5>
                </div>
                <div class="card-body position-relative" style="min-height: 400px; background: #f0f0f0;">
                    <div id="widget-preview" style="position: relative; height: 350px;">
                        <div id="preview-bubble" class="position-absolute bottom-0" style="right: 20px;">
                            <div id="preview-launcher-title" class="small mb-2 text-end"></div>
                            <div style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.2);"
                                 id="preview-bubble-btn">
                                <span id="preview-icon">💬</span>
                            </div>
                        </div>

                        <div id="preview-window" class="position-absolute bg-white rounded shadow-lg" style="display: none; width: 350px; height: 500px; bottom: 80px; right: 20px;">
                            <div id="preview-header" class="text-white p-3 rounded-top">
                                <h6 class="mb-1" id="preview-welcome-title">Hello!</h6>
                                <small id="preview-welcome-tagline"></small>
                            </div>
                            <div class="p-3">
                                <p class="text-muted small mb-0" id="preview-reply-time"></p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="togglePreviewWindow()">
                            <i class="bi bi-arrows-fullscreen"></i> Toggle Widget
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle"></i> Next Steps</h6>
                    <ul class="small mb-0">
                        <li>After creating, you'll get an embed code</li>
                        <li>Install the code on your website</li>
                        <li>Widget updates automatically when you change settings</li>
                        <li>All conversations appear in your inbox</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Show/hide pre-chat options
    $('#pre_chat_form_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#pre-chat-options').slideDown();
        } else {
            $('#pre-chat-options').slideUp();
        }
    });

    // Show/hide offline options
    $('#offline_message_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#offline-options').slideDown();
        } else {
            $('#offline-options').slideUp();
        }
    });

    // Live preview updates
    function updatePreview() {
        var widgetColor = $('#widget_color').val();
        var bubbleColor = $('#widget_bubble_color').val();
        $('#preview-bubble-btn').css('background-color', bubbleColor || widgetColor);
        $('#preview-header').css('background-color', widgetColor);

        var position = $('#widget_position').val();
        var $bubble = $('#preview-bubble');
        var $window = $('#preview-window');
        var $launcherTitle = $('#preview-launcher-title');

        if (position === 'left') {
            $bubble.css({ 'right': 'auto', 'left': '20px' });
            $window.css({ 'right': 'auto', 'left': '20px' });
            $launcherTitle.removeClass('text-end').addClass('text-start');
        } else {
            $bubble.css({ 'left': 'auto', 'right': '20px' });
            $window.css({ 'left': 'auto', 'right': '20px' });
            $launcherTitle.removeClass('text-start').addClass('text-end');
        }

        $('#preview-welcome-title').text($('#welcome_title').val() || 'Hello!');
        $('#preview-welcome-tagline').text($('#welcome_tagline').val() || '');
        $('#preview-launcher-title').text($('#widget_bubble_launcher_title').val() || '');
        $('#preview-reply-time').text($('#reply_time_message').val() || '');

        var icon = $('#widget_launcher_icon').val();
        $('#preview-icon').text(icon || '💬');
    }

    window.togglePreviewWindow = function() {
        $('#preview-window').fadeToggle();
    };

    $('input[data-preview], textarea[data-preview], select[data-preview]').on('input change', function() {
        updatePreview();
    });

    updatePreview();
});
</script>
@endpush
