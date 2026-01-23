@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-envelope"></i> Email Channel Details</h2>
                <div>
                    <a href="{{ route('admin.channels.emails.edit', $email) }}" class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('admin.channels.emails.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
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
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Inbox Name:</strong></div>
                        <div class="col-md-8">{{ $email->inbox->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Email Address:</strong></div>
                        <div class="col-md-8">{{ $email->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Provider:</strong></div>
                        <div class="col-md-8">
                            <span class="badge bg-info">{{ ucfirst($email->provider ?? 'custom') }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Forward Email:</strong></div>
                        <div class="col-md-8">
                            <code>{{ $email->forward_to_email }}</code>
                            <small class="text-muted d-block">Use this address to forward emails to this inbox</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IMAP Configuration -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">IMAP Configuration (Receiving)</h5>
                    @if($email->imap_enabled)
                        <span class="badge bg-success">Enabled</span>
                    @else
                        <span class="badge bg-secondary">Disabled</span>
                    @endif
                </div>
                @if($email->imap_enabled)
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Server:</strong></div>
                            <div class="col-md-8">{{ $email->imap_address }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Port:</strong></div>
                            <div class="col-md-8">{{ $email->imap_port }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Username:</strong></div>
                            <div class="col-md-8">{{ $email->imap_login }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>SSL/TLS:</strong></div>
                            <div class="col-md-8">
                                @if($email->imap_enable_ssl)
                                    <span class="badge bg-success">Enabled</span>
                                @else
                                    <span class="badge bg-secondary">Disabled</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <p class="text-muted mb-0">IMAP is disabled. Edit this channel to enable email receiving.</p>
                    </div>
                @endif
            </div>

            <!-- SMTP Configuration -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">SMTP Configuration (Sending)</h5>
                    @if($email->smtp_enabled)
                        <span class="badge bg-success">Enabled</span>
                    @else
                        <span class="badge bg-secondary">Disabled</span>
                    @endif
                </div>
                @if($email->smtp_enabled)
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Server:</strong></div>
                            <div class="col-md-8">{{ $email->smtp_address }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Port:</strong></div>
                            <div class="col-md-8">{{ $email->smtp_port }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Username:</strong></div>
                            <div class="col-md-8">{{ $email->smtp_login }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Encryption:</strong></div>
                            <div class="col-md-8">
                                @if($email->smtp_enable_ssl_tls)
                                    <span class="badge bg-success">SSL/TLS</span>
                                @elseif($email->smtp_enable_starttls_auto)
                                    <span class="badge bg-success">STARTTLS</span>
                                @else
                                    <span class="badge bg-secondary">None</span>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"></div>
                            <div class="col-md-8">
                                <form action="{{ route('admin.channels.emails.testSmtp', $email) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-plug"></i> Test SMTP Connection
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <p class="text-muted mb-0">SMTP is disabled. Edit this channel to enable email sending.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($email->imap_enabled)
                        <form action="{{ route('admin.channels.emails.index') }}" method="GET" class="mb-2">
                            <button type="button" class="btn btn-primary btn-sm w-100" onclick="fetchEmails()">
                                <i class="bi bi-arrow-clockwise"></i> Fetch Emails Now
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('admin.channels.emails.edit', $email) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fa fa-edit"></i> Edit Configuration
                    </a>

                    <form action="{{ route('admin.channels.emails.destroy', $email) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this email channel?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fa fa-trash"></i> Delete Channel
                        </button>
                    </form>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6"><strong>Conversations:</strong></div>
                        <div class="col-6 text-end">{{ $email->inbox->conversations()->count() }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Messages:</strong></div>
                        <div class="col-6 text-end">{{ $email->inbox->messages()->count() }}</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Created:</strong></div>
                        <div class="col-6 text-end">{{ $email->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function fetchEmails() {
    if (confirm('This will fetch emails from your IMAP server. Continue?')) {
        $.ajax({
            url: '{{ route("admin.channels.emails.index") }}',
            type: 'POST',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: JSON.stringify({
                action: 'fetch',
                email_id: {{ $email->id }}
            }),
            success: function() {
                alert('Email fetch job dispatched! Check your conversation in a few moments.');
            },
            error: function(error) {
                alert('Error fetching emails. Please try again.');
                console.error('Fetch error:', error);
            }
        });
    }
}
</script>
@endpush
@endsection
