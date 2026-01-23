@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Instagram Accounts</h2>
            <a href="{{ route('admin.channels.instagrams.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> Connect Instagram
            </a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if($instagrams->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-instagram fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No Instagram Accounts Connected</h5>
                        <p class="text-muted">Connect an Instagram Business Account via Facebook Page.</p>
                        <a href="{{ route('admin.channels.instagrams.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus-circle"></i> Connect Instagram
                        </a>
                    </div>
                @else
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Inbox</th>
                                <th>Facebook Page</th>
                                <th>Token Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($instagrams as $instagram)
                                <tr>
                                    <td><strong>{{ $instagram->username ?? 'N/A' }}</strong></td>
                                    <td>{{ $instagram->inbox->name ?? 'N/A' }}</td>
                                    <td>{{ $instagram->facebookPage->page_name ?? 'Not Linked' }}</td>
                                    <td>
                                        @if($instagram->isTokenExpired())
                                            <span class="badge bg-danger">Expired</span>
                                        @elseif($instagram->needsTokenRefresh())
                                            <span class="badge bg-warning">Expiring Soon</span>
                                        @else
                                            <span class="badge bg-success">Valid</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.channels.instagrams.show', $instagram) }}" class="btn btn-info" title="View"><i class="fa fa-eye"></i></a>
                                            <a href="{{ route('admin.channels.instagrams.edit', $instagram) }}" class="btn btn-warning" title="Edit"><i class="fa fa-edit"></i></a>
                                            <form action="{{ route('admin.channels.instagrams.reauthorize', $instagram) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Reauthorize Credentials">
                                                    <i class="bi bi-shield-lock"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-danger" title="Delete" onclick="confirmDelete({{ $instagram->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>

                                        <form id="delete-form-{{ $instagram->id }}" action="{{ route('admin.channels.instagrams.destroy', $instagram) }}" method="POST" class="d-none">
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
function confirmDelete(instagramId) {
    if (confirm('Are you sure you want to disconnect this Instagram Account? This action cannot be undone.')) {
        document.getElementById('delete-form-' + instagramId).submit();
    }
}
</script>
@endpush
