@extends('layouts.theme')

@section('title', 'Create view')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Create view</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('settings.chat.configurations.global') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manager.chat.settings.tickets.views.index') }}">Views</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="viewForm" action="{{ route('manager.chat.settings.tickets.views.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" value="{{ old('name') }}" placeholder="e.g., Open tickets">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">A short, descriptive name for this view</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="description" name="description" rows="3" placeholder="Add any relevant details about this view">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Optional description to help with view identification</div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Filters</h6>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="filters[status_id]" id="status" class="form-control select2 @error('filters.status_id') is-invalid @enderror">
                                    <option value="">All statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->id }}" {{ old('filters.status_id') == $status->id ? 'selected' : '' }}>
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('filters.status_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="group" class="form-label">Group</label>
                                <select name="filters[group_id]" id="group" class="form-control select2 @error('filters.group_id') is-invalid @enderror">
                                    <option value="">All groups</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('filters.group_id') == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('filters.group_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Options</h6>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_public"
                                    name="is_public" value="1"
                                    {{ old('is_public', false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_public">
                                    <strong>Public view</strong>
                                </label>
                            </div>
                            <div class="form-text">Make this view visible to all agents</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_default"
                                    name="is_default" value="1"
                                    {{ old('is_default', false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">
                                    <strong>Default view</strong>
                                </label>
                            </div>
                            <div class="form-text">Set as your default view when loading conversations</div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle me-1"></i> Create view
                            </button>
                            <a href="{{ route('manager.chat.settings.tickets.views.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">View tips</h6>
                    <ul class="small mb-0">
                        <li>Use views to organize conversations with custom filters</li>
                        <li>Choose descriptive, memorable names</li>
                        <li>Public views are visible to all team members</li>
                        <li>Set a default view for quick access to your most important conversations</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#viewForm').validate({
        rules: {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            description: {
                maxlength: 500
            }
        },
        messages: {
            name: {
                required: 'Name is required',
                minlength: 'Name must be at least 2 characters',
                maxlength: 'Name must not exceed 100 characters'
            },
            description: {
                maxlength: 'Description must not exceed 500 characters'
            }
        },
        errorElement: 'div',
        errorClass: 'invalid-feedback',
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        }
    });
});
</script>
@endsection
