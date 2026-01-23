@extends('layouts.admin')

@section('title', 'Labels')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Labels</h1>
            <p class="text-muted">Organize conversations with labels for better classification and filtering</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.labels.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create new label
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="labelsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50"></th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Show on sidebar</th>
                            <th>Created</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($labels as $label)
                            <tr>
                                <td>
                                    <span class="badge" style="background-color: {{ $label->color }}; width: 30px; height: 30px; border-radius: 50%;"></span>
                                </td>
                                <td><strong>{{ $label->title }}</strong></td>
                                <td>{{ Str::limit($label->description, 60) }}</td>
                                <td>
                                    @if($label->show_on_sidebar)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>{{ $label->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.labels.edit', $label) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete('{{ route('admin.labels.destroy', $label) }}')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No labels found. <a href="{{ route('admin.labels.create') }}">Create one now</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this label?')) {
        const form = document.querySelector('#delete-form');
        form.action = url;
        form.submit();
    }
}

$(document).ready(function() {
    $('#labelsTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        language: {
            search: 'Search labels:',
            lengthMenu: 'Show _MENU_ labels per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ labels',
            infoEmpty: 'No labels found',
            emptyTable: 'No labels found',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        },
        columnDefs: [
            {
                targets: 5,
                orderable: false,
                searchable: false
            }
        ]
    });
});
</script>
@endsection
