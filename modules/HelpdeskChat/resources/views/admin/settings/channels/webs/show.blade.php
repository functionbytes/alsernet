@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>{{ $webWidget->inbox->name }}</h2>
            <div>
                <a href="{{ route('admin.channels.webs.edit', $webWidget) }}" class="btn btn-warning me-2">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('admin.channels.webs.index') }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Installation Instructions -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-code-slash"></i> Installation Code</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    Copy and paste the following code snippet just before the closing <code>&lt;/body&gt;</code> tag
                    on every page where you want the chat widget to appear.
                </p>

                <div class="bg-dark text-white p-3 rounded position-relative">
                    <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 copy-embed-btn">
                        <i class="bi bi-clipboard"></i> <span class="copy-text">Copy Code</span>
                    </button>
                    <pre class="mb-0 text-white" id="embed-code"><code>&lt;script src="{{ route('widget.script', $webWidget->website_token) }}"&gt;&lt;/script&gt;</code></pre>
                </div>

                <div class="alert alert-info mt-3 mb-3">
                    <i class="bi bi-info-circle"></i>
                    <strong>That's it!</strong> The widget will automatically load on your website with all your custom settings.
                    Visitors can start conversations immediately.
                </div>

                <h6 class="mb-2"><i class="bi bi-tools"></i> Advanced Configuration</h6>
                <p class="text-muted small mb-2">You can also manually configure the widget by using the following code:</p>

                <div class="bg-dark text-white p-3 rounded position-relative">
                    <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 copy-advanced-btn">
                        <i class="bi bi-clipboard"></i> <span class="copy-text">Copy Code</span>
                    </button>
                    <pre class="mb-0 text-white small" id="advanced-code"><code>&lt;script&gt;
  window.channelsWidgetConfig = {
    websiteToken: '{{ $webWidget->website_token }}',
    baseUrl: '{{ url('/') }}',
    // You can override widget settings here
    // color: '#1f93ff',
    // position: 'right'
  };
&lt;/script&gt;
&lt;script src="{{ route('widget.script', $webWidget->website_token) }}"&gt;&lt;/script&gt;</code></pre>
                </div>
            </div>
        </div>

        <!-- Widget Configuration -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-gear"></i> Widget Configuration</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button">
                            Basic
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button">
                            Appearance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button">
                            Features
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="configTabsContent">
                    <!-- Basic Settings -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" width="200">Website URL:</td>
                                    <td>
                                        <a href="{{ $webWidget->website_url }}" target="_blank">
                                            {{ $webWidget->website_url }}
                                            <i class="bi bi-box-arrow-up-right small"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Welcome Title:</td>
                                    <td>{{ $webWidget->welcome_title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Welcome Tagline:</td>
                                    <td>{{ $webWidget->welcome_tagline ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Reply Time Message:</td>
                                    <td>{{ $webWidget->reply_time_message ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Website Token:</td>
                                    <td>
                                        <code class="copy-target" data-copy="{{ $webWidget->website_token }}">{{ $webWidget->website_token }}</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2 copy-token-btn">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">HMAC Token:</td>
                                    <td>
                                        <code class="copy-target" data-copy="{{ $webWidget->hmac_token }}">{{ Str::limit($webWidget->hmac_token, 20) }}...</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2 copy-hmac-btn">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Created:</td>
                                    <td>{{ $webWidget->created_at->format('F d, Y \a\t H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Appearance Settings -->
                    <div class="tab-pane fade" id="appearance" role="tabpanel">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" width="200">Widget Color:</td>
                                    <td>
                                        <span class="badge px-3 py-2" style="background-color: {{ $webWidget->widget_color }}">
                                            {{ $webWidget->widget_color }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Bubble Color:</td>
                                    <td>
                                        <span class="badge px-3 py-2" style="background-color: {{ $webWidget->widget_bubble_color ?? $webWidget->widget_color }}">
                                            {{ $webWidget->widget_bubble_color ?? $webWidget->widget_color }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Widget Position:</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($webWidget->widget_position ?? 'right') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Launcher Title:</td>
                                    <td>{{ $webWidget->widget_bubble_launcher_title ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Launcher Icon:</td>
                                    <td>
                                        <span style="font-size: 24px;">{{ $webWidget->widget_launcher_icon ?? '💬' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Show Branding:</td>
                                    <td>
                                        @if($webWidget->show_powered_by)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Features Settings -->
                    <div class="tab-pane fade" id="features" role="tabpanel">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" width="200">Pre-chat Form:</td>
                                    <td>
                                        @if($webWidget->pre_chat_form_enabled)
                                            <span class="badge bg-success">Enabled</span>
                                        @else
                                            <span class="badge bg-secondary">Disabled</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Offline Messages:</td>
                                    <td>
                                        @if($webWidget->offline_message_enabled)
                                            <span class="badge bg-success">Enabled</span>
                                        @else
                                            <span class="badge bg-secondary">Disabled</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($webWidget->offline_message_enabled && $webWidget->offline_message)
                                <tr>
                                    <td class="fw-bold">Offline Message:</td>
                                    <td>{{ $webWidget->offline_message }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="fw-bold">Show Availability:</td>
                                    <td>
                                        @if($webWidget->show_availability_status)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Widget Preview -->
        <div class="card mb-3 sticky-top" style="top: 20px;">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa fa-eye"></i> Widget Preview</h5>
            </div>
            <div class="card-body position-relative" style="min-height: 350px; background: #f0f0f0;">
                <div class="position-relative" style="height: 300px;">
                    <div class="position-absolute bottom-0" style="{{ ($webWidget->widget_position ?? 'right') === 'left' ? 'left: 20px;' : 'right: 20px;' }}">
                        @if($webWidget->widget_bubble_launcher_title)
                        <div class="small mb-2 {{ ($webWidget->widget_position ?? 'right') === 'left' ? 'text-start' : 'text-end' }}">
                            {{ $webWidget->widget_bubble_launcher_title }}
                        </div>
                        @endif
                        <div class="rounded-circle d-flex align-items-center justify-content-center shadow"
                             style="width: 60px; height: 60px; background-color: {{ $webWidget->widget_bubble_color ?? $webWidget->widget_color }}; color: white; font-size: 24px;">
                            {{ $webWidget->widget_launcher_icon ?? '💬' }}
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">Preview of how the widget will appear on your website</small>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Conversations:</span>
                    <strong>{{ $webWidget->inbox->conversations()->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Open Conversations:</span>
                    <strong>{{ $webWidget->inbox->conversations()->where('status', 'open')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total Messages:</span>
                    <strong>{{ $webWidget->inbox->messages()->count() }}</strong>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('demo.widget', $webWidget->website_token) }}" target="_blank"
                   class="btn btn-outline-success w-100 mb-2">
                    <i class="bi bi-play-circle"></i> View Live Demo
                </a>
                <a href="{{ route('admin.conversation.index', ['inbox_id' => $webWidget->inbox->id]) }}"
                   class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-chat-left-text"></i> View Conversations
                </a>
                <a href="{{ route('admin.channels.webs.edit', $webWidget) }}"
                   class="btn btn-outline-warning w-100 mb-2">
                    <i class="fa fa-edit"></i> Edit Settings
                </a>
                <button type="button" class="btn btn-outline-danger w-100 delete-widget-btn">
                    <i class="fa fa-trash"></i> Delete Widget
                </button>

                <form id="delete-form" action="{{ route('admin.channels.webs.destroy', $webWidget) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Copy embed code
    $('.copy-embed-btn').on('click', function() {
        var $btn = $(this);
        var text = $('#embed-code').text().trim();
        copyToClipboard(text, $btn);
    });

    // Copy advanced code
    $('.copy-advanced-btn').on('click', function() {
        var $btn = $(this);
        var text = $('#advanced-code').text().trim();
        copyToClipboard(text, $btn);
    });

    // Copy token
    $('.copy-token-btn').on('click', function() {
        var $btn = $(this);
        var text = '{{ $webWidget->website_token }}';
        copyToClipboard(text, $btn);
    });

    // Copy HMAC token
    $('.copy-hmac-btn').on('click', function() {
        var $btn = $(this);
        var text = '{{ $webWidget->hmac_token }}';
        copyToClipboard(text, $btn);
    });

    // Delete widget confirmation
    $('.delete-widget-btn').on('click', function() {
        if (confirm('Are you sure you want to delete this web widget? This action cannot be undone and will delete all associated conversation.')) {
            $('#delete-form').submit();
        }
    });

    // Copy to clipboard function with jQuery
    function copyToClipboard(text, $button) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();

        try {
            document.execCommand('copy');
            var originalText = $button.find('.copy-text').text();
            $button.find('.copy-text').text('Copied!');
            $button.addClass('btn-success').removeClass('btn-light');

            setTimeout(function() {
                $button.find('.copy-text').text(originalText);
                $button.removeClass('btn-success').addClass('btn-light');
            }, 2000);
        } catch (err) {
            alert('Failed to copy to clipboard. Please copy manually.');
        }

        $temp.remove();
    }
});
</script>
@endpush
