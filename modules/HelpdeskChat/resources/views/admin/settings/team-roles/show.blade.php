@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">{{ $teamRole->name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.team-roles.index') }}">Team Roles</a></li>
                    <li class="breadcrumb-item active">{{ $teamRole->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.team-roles.edit', $teamRole) }}" class="btn btn-primary">
                <i class="fa fa-edit me-1"></i> Edit Role
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Role Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Name:</strong> {{ $teamRole->name }}
                        @if($teamRole->is_default)
                            <span class="badge bg-primary ms-2">Default Role</span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <p>{{ $teamRole->description ?? 'No description provided' }}</p>
                    </div>
                    <div class="mb-0">
                        <strong>Users with this role:</strong>
                        <span class="badge bg-secondary">{{ $teamRole->users->count() }} users</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Permissions ({{ count($teamRole->permissions ?? []) }})</h5>
                </div>
                <div class="card-body">
                    @php
                        $groupedPermissions = [];
                        foreach ($availablePermissions as $key => $label) {
                            $parts = explode('.', $key);
                            $group = $parts[0];
                            if (!isset($groupedPermissions[$group])) {
                                $groupedPermissions[$group] = [];
                            }
                            $groupedPermissions[$group][$key] = $label;
                        }
                    @endphp

                    <div class="row">
                        @foreach($groupedPermissions as $group => $permissions)
                            <div class="col-md-6 mb-3">
                                <h6 class="text-uppercase text-muted">{{ $group }}</h6>
                                @foreach($permissions as $key => $label)
                                    <div class="mb-2">
                                        @if(in_array($key, $teamRole->permissions ?? []))
                                            <i class="fa fa-check-circle-fill text-success me-2"></i>
                                        @else
                                            <i class="fa fa-times-circle text-muted me-2"></i>
                                        @endif
                                        <span class="{{ in_array($key, $teamRole->permissions ?? []) ? '' : 'text-muted' }}">
                                            {{ $label }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Users with this Role</h6>
                </div>
                <div class="card-body">
                    @forelse($teamRole->users as $user)
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-circle me-2"></i>
                            <div>
                                <strong>{{ $user->name }}</strong><br>
                                <small class="text-muted">{{ $user->email }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No users assigned to this role yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
