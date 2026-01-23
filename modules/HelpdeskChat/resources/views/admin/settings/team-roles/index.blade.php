@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3"><i class="bi bi-shield-check me-2"></i> Team Roles & Permissions</h1>
            <p class="text-muted">Manage roles and permissions for your team members</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.team-roles.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create Role
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

    <div class="row">
        @forelse($roles as $role)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 {{ $role->is_default ? 'border-primary' : '' }}">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                {{ $role->name }}
                                @if($role->is_default)
                                    <span class="badge bg-primary ms-2">Default</span>
                                @endif
                            </h5>
                        </div>
                        <span class="badge bg-secondary">{{ $role->users_count }} users</span>
                    </div>
                    <div class="card-body">
                        @if($role->description)
                            <p class="text-muted small">{{ $role->description }}</p>
                        @else
                            <p class="text-muted fst-italic small">No description</p>
                        @endif

                        <div class="mt-3">
                            <strong class="small">Permissions ({{ count($role->permissions ?? []) }}):</strong>
                            <div class="mt-2" style="max-height: 150px; overflow-y: auto;">
                                @forelse($role->permissions ?? [] as $permission)
                                    <span class="badge bg-light text-dark mb-1 me-1">
                                        <i class="fa fa-check-circle text-success me-1"></i>
                                        {{ $availablePermissions[$permission] ?? $permission }}
                                    </span>
                                @empty
                                    <small class="text-muted">No permissions assigned</small>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="btn-group btn-group-sm w-100">
                            <a href="{{ route('admin.team-roles.show', $role) }}" class="btn btn-outline-primary">
                                <i class="fa fa-eye"></i> View
                            </a>
                            <a href="{{ route('admin.team-roles.edit', $role) }}" class="btn btn-outline-secondary">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            @if($role->users_count == 0)
                                <button type="button" class="btn btn-outline-danger"
                                    onclick="confirmDelete('{{ route('admin.team-roles.destroy', $role) }}')">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No roles found. <a href="{{ route('admin.team-roles.create') }}" class="alert-link">Create your first role</a> to get started.
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Delete confirmation modal -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
        const form = document.getElementById('delete-form');
        form.action = url;
        form.submit();
    }
}
</script>
@endsection
