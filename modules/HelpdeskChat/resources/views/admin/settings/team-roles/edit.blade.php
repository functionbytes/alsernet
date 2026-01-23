@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Edit Team Role: {{ $teamRole->name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.team-roles.index') }}">Team Roles</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.team-roles.update', $teamRole) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $teamRole->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description', $teamRole->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default', $teamRole->is_default) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">
                                    Set as default role for new users
                                </label>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Permissions</h5>
                        <p class="text-muted small">Select the permissions this role should have:</p>

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
                            $rolePermissions = old('permissions', $teamRole->permissions ?? []);
                        @endphp

                        <div class="row">
                            @foreach($groupedPermissions as $group => $permissions)
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <strong>{{ ucfirst($group) }}</strong>
                                        </div>
                                        <div class="card-body">
                                            @foreach($permissions as $key => $label)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="permissions[]" value="{{ $key }}"
                                                           id="perm_{{ $key }}"
                                                           {{ in_array($key, $rolePermissions) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="perm_{{ $key }}">
                                                        {{ $label }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.team-roles.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle me-1"></i> Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
