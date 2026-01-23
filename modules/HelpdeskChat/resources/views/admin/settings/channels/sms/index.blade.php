@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>SMS Channels</h2>
            <a href="{{ route('admin.channels.sms.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> Add SMS Channel
            </a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if($smsChannels->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No SMS Channels</h5>
                        <p class="text-muted">Add your first SMS channel to start receiving messages</p>
                        <a href="{{ route('admin.channels.sms.create') }}" class="btn btn-primary">Add SMS Channel</a>
                    </div>
                @else
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Phone Number</th>
                                <th>Inbox</th>
                                <th>Provider</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($smsChannels as $sms)
                                <tr>
                                    <td><code>{{ $sms->formatted_phone_number }}</code></td>
                                    <td>{{ $sms->inbox->name ?? 'N/A' }}</td>
                                    <td><span class="badge bg-info">{{ $sms->provider_display_name }}</span></td>
                                    <td>
                                        @if($sms->active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.channels.sms.show', $sms) }}" class="btn btn-info" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.channels.sms.edit', $sms) }}" class="btn btn-warning" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" title="Delete" onclick="confirmDelete({{ $sms->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>

                                        <form id="delete-form-{{ $sms->id }}" action="{{ route('admin.channels.sms.destroy', $sms) }}" method="POST" class="d-none">
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
    if (confirm('Are you sure you want to delete this SMS channel? This will also delete all conversation.')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endpush
