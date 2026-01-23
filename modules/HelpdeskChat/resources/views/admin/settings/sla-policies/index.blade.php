@extends('layouts.admin')

@section('title', 'SLA Policies')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">SLA Policies</h1>
            <p class="text-muted">Define response time expectations and resolution targets</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.sla-policies.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create SLA Policy
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
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>First Response</th>
                            <th>Resolution</th>
                            <th>Business Hours</th>
                            <th>Status</th>
                            <th>Conversations</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $policy)
                            <tr>
                                <td>
                                    <strong>{{ $policy->name }}</strong>
                                    @if($policy->description)
                                        <br>
                                        <small class="text-muted">{{ Str::limit($policy->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($policy->first_response_time_minutes)
                                        <span class="badge bg-info">{{ $policy->formatted_first_response_time }}</span>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($policy->resolution_time_minutes)
                                        <span class="badge bg-warning">{{ $policy->formatted_resolution_time }}</span>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($policy->business_hours_only)
                                        <i class="fa fa-check-circle text-success" title="Business hours only"></i>
                                    @else
                                        <i class="bi bi-clock text-secondary" title="24/7"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($policy->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $policy->conversations_count }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.sla-policies.show', $policy) }}" class="btn btn-outline-secondary" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.sla-policies.edit', $policy) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete('{{ route('admin.sla-policies.destroy', $policy) }}')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No SLA policies found. <a href="{{ route('admin.sla-policies.create') }}">Create one now</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $policies->links() }}
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
    if (confirm('Are you sure you want to delete this SLA policy?')) {
        const form = 20 20 12 61 79 80 81 98 33 100 204 250 395 398 399 400 701"#'delete-form');
        form.action = url;
        form.submit();
    }
}
</script>
@endsection
