@extends('layouts.admin')

@section('title', 'Create SLA Policy')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Create SLA Policy</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sla-policies.index') }}">SLA Policies</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.sla-policies.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Policy Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">A descriptive name for this SLA policy (e.g., "Standard Support", "Premium SLA")</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_response_time_minutes" class="form-label">First Response Time (minutes)</label>
                                <input type="number" class="form-control @error('first_response_time_minutes') is-invalid @enderror"
                                    id="first_response_time_minutes" name="first_response_time_minutes"
                                    value="{{ old('first_response_time_minutes') }}" min="1" placeholder="e.g., 60">
                                @error('first_response_time_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Time allowed for first agent response (60 mins = 1 hour)</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="resolution_time_minutes" class="form-label">Resolution Time (minutes)</label>
                                <input type="number" class="form-control @error('resolution_time_minutes') is-invalid @enderror"
                                    id="resolution_time_minutes" name="resolution_time_minutes"
                                    value="{{ old('resolution_time_minutes') }}" min="1" placeholder="e.g., 480">
                                @error('resolution_time_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Time allowed to resolve the conversation (480 mins = 8 hours)</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                        name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Active</strong>
                                    </label>
                                </div>
                                <div class="form-text">Only active policies are applied to conversations</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="business_hours_only"
                                        name="business_hours_only" value="1" {{ old('business_hours_only') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="business_hours_only">
                                        <strong>Business Hours Only</strong>
                                    </label>
                                </div>
                                <div class="form-text">Calculate SLA time only during business hours</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle me-1"></i> Create SLA Policy
                            </button>
                            <a href="{{ route('admin.sla-policies.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">SLA Policy Tips</h6>
                    <ul class="small mb-0">
                        <li><strong>First Response Time</strong> - Time for agents to send the first reply</li>
                        <li><strong>Resolution Time</strong> - Total time to resolve and close the conversation</li>
                        <li><strong>Business Hours</strong> - SLA clock only runs during configured business hours</li>
                        <li><strong>Active Status</strong> - Inactive policies won't be applied to new conversations</li>
                    </ul>
                </div>
            </div>

            <div class="card bg-info bg-opacity-10 mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-lightbulb me-1"></i> Quick Reference</h6>
                    <ul class="small mb-0">
                        <li>30 mins = 0.5 hours</li>
                        <li>60 mins = 1 hour</li>
                        <li>240 mins = 4 hours</li>
                        <li>480 mins = 8 hours (1 business day)</li>
                        <li>1440 mins = 24 hours</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
