@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>{{ $facebookPage->page_name }}</h2>
            <div>
                <a href="{{ route('admin.channels.facebook-pages.edit', $facebookPage) }}" class="btn btn-warning me-2">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('admin.channels.facebook-pages.index') }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-gear"></i> Page Configuration</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <td class="fw-bold" width="200">Page Name:</td>
                            <td>{{ $facebookPage->page_name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Page ID:</td>
                            <td><code>{{ $facebookPage->page_id }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Inbox:</td>
                            <td>{{ $facebookPage->inbox->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Instagram Linked:</td>
                            <td>
                                @if($facebookPage->instagram_id)
                                    <span class="badge bg-success">Yes</span>
                                    <code>{{ $facebookPage->instagram_id }}</code>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Created:</td>
                            <td>{{ $facebookPage->created_at->format('F d, Y \a\t H:i') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-webhook"></i> Webhook Configuration</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label"><strong>Webhook URL:</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ $facebookPage->getWebhookUrl() }}" readonly>
                        <button class="btn btn-outline-secondary" onclick="copyToClipboard('{{ $facebookPage->getWebhookUrl() }}')">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                </div>

                <div class="alert alert-info mb-0">
                    <h6><i class="bi bi-info-circle"></i> Setup Instructions:</h6>
                    <ol class="small mb-0">
                        <li>Go to Facebook Developer Console</li>
                        <li>Navigate to Webhooks → Page</li>
                        <li>Subscribe to: messages, messaging_postbacks, message_deliveries, message_reads</li>
                        <li>Use the webhook URL above</li>
                        <li>Verify Token: <code>{{ config('channels.facebook.verify_token') }}</code></li>
                    </ol>
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
                    <strong>{{ $facebookPage->inbox->conversations()->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Open Conversations:</span>
                    <strong>{{ $facebookPage->inbox->conversations()->where('status', 'open')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total Messages:</span>
                    <strong>{{ $facebookPage->inbox->messages()->count() }}</strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.conversation.index', ['inbox_id' => $facebookPage->inbox->id]) }}"
                   class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-chat-left-text"></i> View Conversations
                </a>
                <form action="{{ route('admin.channels.facebook-pages.reauthorize', $facebookPage) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning w-100">
                        <i class="bi bi-arrow-repeat"></i> Reauthorize
                    </button>
                </form>
                <button type="button" class="btn btn-outline-danger w-100" onclick="confirmDelete()">
                    <i class="fa fa-trash"></i> Disconnect
                </button>

                <form id="delete-form" action="{{ route('admin.channels.facebook-pages.destroy', $facebookPage) }}" method="POST" class="d-none">
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
    if (confirm('Are you sure you want to disconnect this Facebook Page? All conversation will be preserved but you will stop receiving new messages.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endpush
