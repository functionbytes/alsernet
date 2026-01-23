@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>API Channels</h2>
            <a href="{{ route('admin.channels.api.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> Add API Channel
            </a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if($apiChannels->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-cloud-upload fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No API Channels</h5>
                        <p class="text-muted">Add your first API channel to start integrating external systems</p>
                        <a href="{{ route('admin.channels.api.create') }}" class="btn btn-primary">Add API Channel</a>
                    </div>
                @else
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Identifier</th>
                                <th>Inbox</th>
                                <th>Webhook URL</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($apiChannels as $api)
                                <tr>
                                    <td><code>{{ $api->identifier }}</code></td>
                                    <td>{{ $api->inbox->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($api->webhook_url)
                                            <span class="badge bg-info" title="{{ $api->webhook_url }}">
                                                <i class="bi bi-link-45deg"></i> Configured
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($api->active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.channels.api.show', $api) }}" class="btn btn-info" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.channels.api.edit', $api) }}" class="btn btn-warning" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" title="Delete" onclick="confirmDelete({{ $api->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>

                                        <form id="delete-form-{{ $api->id }}" action="{{ route('admin.channels.api.destroy', $api) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this API channel? This will also delete all conversation.')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endpush
