@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>{{ $instagram->inbox->name }}</h2>
            <div>
                <a href="{{ route('admin.channels.instagrams.edit', $instagram) }}" class="btn btn-warning me-2">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('admin.channels.instagrams.index') }}" class="btn btn-secondary">
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
                <h5 class="mb-0"><i class="bi bi-instagram"></i> Instagram Account Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <td class="fw-bold" width="200">Instagram ID:</td>
                            <td><code>{{ $instagram->instagram_id }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Username:</td>
                            <td>{{ $instagram->username ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Connected Facebook Page:</td>
                            <td>
                                @if($instagram->facebookPage)
                                    <a href="{{ route('admin.channels.facebook-pages.show', $instagram->facebookPage) }}">
                                        {{ $instagram->facebookPage->page_name }}
                                    </a>
                                @else
                                    <span class="text-muted">Not connected</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Token Status:</td>
                            <td>
                                @if($instagram->isTokenExpired())
                                    <span class="badge bg-danger">Expired</span>
                                    <a href="{{ route('admin.channels.instagrams.refresh-token', $instagram) }}"
                                       class="btn btn-sm btn-warning ms-2"
                                       onclick="event.preventDefault(); document.getElementById('refresh-token-form').submit();">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh Token
                                    </a>
                                    <form id="refresh-token-form" action="{{ route('admin.channels.instagrams.refresh-token', $instagram) }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                @elseif($instagram->needsTokenRefresh())
                                    <span class="badge bg-warning">Expiring Soon</span>
                                    @if($instagram->token_expires_at)
                                        <small class="text-muted ms-2">Expires: {{ $instagram->token_expires_at->diffForHumans() }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-success">Active</span>
                                    @if($instagram->token_expires_at)
                                        <small class="text-muted ms-2">Expires: {{ $instagram->token_expires_at->diffForHumans() }}</small>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Created:</td>
                            <td>{{ $instagram->created_at->format('F d, Y \a\t H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Last Updated:</td>
                            <td>{{ $instagram->updated_at->format('F d, Y \a\t H:i') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Webhook Configuration</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Configure this webhook URL in your Instagram Business Account settings:</p>

                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="{{ route('webhooks.instagram.handle') }}" readonly id="webhook-url">
                    <button class="btn btn-outline-secondary" onclick="copyToClipboard('{{ route('webhooks.instagram.handle') }}')">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>

                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i>
                    <strong>Verify Token:</strong> Use <code>{{ config('channels.instagram.verify_token') }}</code>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Conversations:</span>
                    <strong>{{ $instagram->inbox->conversations()->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Open Conversations:</span>
                    <strong>{{ $instagram->inbox->conversations()->where('status', 'open')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total Messages:</span>
                    <strong>{{ $instagram->inbox->messages()->count() }}</strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.conversation.index', ['inbox_id' => $instagram->inbox->id]) }}"
                   class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-chat-left-text"></i> View Conversations
                </a>
                <a href="{{ route('admin.channels.instagrams.edit', $instagram) }}"
                   class="btn btn-outline-warning w-100 mb-2">
                    <i class="fa fa-edit"></i> Edit Settings
                </a>
                <button type="button" class="btn btn-outline-danger w-100" onclick="confirmDelete()">
                    <i class="fa fa-trash"></i> Delete Instagram Account
                </button>

                <form id="delete-form" action="{{ route('admin.channels.instagrams.destroy', $instagram) }}" method="POST" class="d-none">
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
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    });
}

function confirmDelete() {
    if (confirm('Are you sure you want to delete this Instagram account? This action cannot be undone.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endpush
