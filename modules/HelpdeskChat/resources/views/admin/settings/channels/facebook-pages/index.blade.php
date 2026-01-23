@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Facebook Pages</h2>
            <a href="{{ route('admin.channels.facebook-pages.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> Connect Facebook Page
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if($pages->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-messenger fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No Facebook Pages Connected</h5>
                        <p class="text-muted">Connect your Facebook Page to start receiving messages from Messenger.</p>
                        <a href="{{ route('admin.channels.facebook-pages.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus-circle"></i> Connect Facebook Page
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Page Name</th>
                                    <th>Inbox</th>
                                    <th>Page ID</th>
                                    <th>Instagram</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pages as $page)
                                    <tr>
                                        <td>
                                            <strong>{{ $page->page_name }}</strong>
                                        </td>
                                        <td>{{ $page->inbox->name ?? 'N/A' }}</td>
                                        <td><code>{{ $page->page_id }}</code></td>
                                        <td>
                                            @if($page->instagram_id)
                                                <span class="badge bg-success">Linked</span>
                                            @else
                                                <span class="badge bg-secondary">Not Linked</span>
                                            @endif
                                        </td>
                                        <td>{{ $page->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.channels.facebook-pages.show', $page) }}" class="btn btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.channels.facebook-pages.edit', $page) }}" class="btn btn-warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.channels.facebook-pages.reauthorize', $page) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-secondary" title="Reauthorize Credentials">
                                                        <i class="bi bi-shield-lock"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-danger" title="Delete" onclick="confirmDelete({{ $page->id }})">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>

                                            <form id="delete-form-{{ $page->id }}" action="{{ route('admin.channels.facebook-pages.destroy', $page) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(pageId) {
    if (confirm('Are you sure you want to disconnect this Facebook Page? This action cannot be undone.')) {
        document.getElementById('delete-form-' + pageId).submit();
    }
}
</script>
@endpush
