@extends('layouts.admin')

@section('title', 'Create Team')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Create Team</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">Teams</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.teams.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Team Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                        id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Brief description of this team's purpose</div>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="allow_auto_assign"
                            name="allow_auto_assign" value="1" {{ old('allow_auto_assign', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="allow_auto_assign">
                            Allow Auto Assign
                        </label>
                    </div>
                    <div class="form-text">Automatically assign conversations to team members</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Team Members</label>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        @forelse($users as $user)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    id="member_{{ $user->id }}"
                                    name="member_ids[]"
                                    value="{{ $user->id }}"
                                    {{ in_array($user->id, old('member_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="member_{{ $user->id }}">
                                    {{ $user->name }} <small class="text-muted">({{ $user->email }})</small>
                                </label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No users available</p>
                        @endforelse
                    </div>
                    @error('member_ids')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check-circle me-1"></i> Create Team
                    </button>
                    <a href="{{ route('admin.teams.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
