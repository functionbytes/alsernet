@extends('layouts.admin')

@section('title', 'Webhooks')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Webhooks</h1>
            <p class="text-muted">Send event notifications to external URLs</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create Webhook
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Type</th>
                        <th>Subscriptions</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($webhooks as $webhook)
                        <tr>
                            <td><strong>{{ $webhook->name }}</strong></td>
                            <td><code class="small">{{ Str::limit($webhook->url, 40) }}</code></td>
                            <td>
                                <span class="badge bg-{{ $webhook->webhook_type === 0 ? 'primary' : 'info' }}">
                                    {{ $webhook->webhook_type === 0 ? 'Account' : 'Inbox' }}
                                </span>
                            </td>
                            <td><span class="badge bg-secondary">{{ count($webhook->subscriptions ?? []) }} events</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.webhooks.edit', $webhook) }}" class="btn btn-outline-secondary">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger"
                                        onclick="if(confirm('Delete?')) 20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'delete-{{ $webhook->id }}').submit()">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-{{ $webhook->id }}" action="{{ route('admin.webhooks.destroy', $webhook) }}" method="POST" style="display:none">
                                    @csrf @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No webhooks found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $webhooks->links() }}
        </div>
    </div>
</div>
@endsection
