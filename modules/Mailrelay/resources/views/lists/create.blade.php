@extends('layouts.theme')

@section('title', 'Create New List')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-plus-circle"></i> Create New List
                    </h1>
                    <p class="text-muted mb-0">Add a new subscriber list to your account</p>
                </div>
                <a href="{{ route('mailrelay.lists.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Lists
                </a>
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle"></i> Please correct the following errors:
                    </h6>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Form Card -->
            <div class="card">
                <div class="card-body p-4">
                    <form action="{{ route('mailrelay.lists.store') }}" method="POST">
                        @csrf

                        <!-- List Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">
                                <i class="fas fa-tag"></i> List Name
                                <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="e.g., Newsletter Subscribers"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Unique identifier for this list
                            </small>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">
                                <i class="fas fa-align-left"></i> Description
                            </label>
                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Describe the purpose of this list..."
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Optional description to help organize your lists
                            </small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between gap-2">
                            <a href="{{ route('mailrelay.lists.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create List
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Section -->
            <div class="alert alert-info mt-4" role="alert">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle"></i> About Lists
                </h6>
                <ul class="mb-0 small">
                    <li>Lists are used to organize subscribers for campaigns</li>
                    <li>Each subscriber can be in multiple lists</li>
                    <li>Use descriptive names to identify list purposes</li>
                    <li>You can add subscribers to lists individually or via import</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
