@extends('layouts.theme')

@section('title', 'Edit List')

@section('content')
<div class="py-4 px-3">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-edit"></i> Edit List
                    </h1>
                    <p class="text-muted mb-0">Update list information</p>
                </div>
                <a href="{{ route('mailrelay.lists.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Lists
                </a>
            </div>

            <!-- List Info Badge -->
            <div class="alert alert-info">
                <div class="row">
                    <div class="col-md-6">
                        <small class="d-block text-muted">List ID:</small>
                        <strong>{{ $list->id }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="d-block text-muted">Total Subscribers:</small>
                        <strong>{{ $list->subscribers_count ?? 0 }}</strong>
                    </div>
                </div>
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
                    <form action="{{ route('mailrelay.lists.update', $list) }}" method="POST">
                        @csrf
                        @method('PUT')

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
                                value="{{ old('name', $list->name) }}"
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
                            >{{ old('description', $list->description) }}</textarea>
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
                                <i class="fas fa-save"></i> Update List
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card  mt-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-circle"></i> Danger Zone
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Deleting a list will remove it permanently. Subscribers will not be affected.
                    </p>
                    <form action="{{ route('mailrelay.lists.destroy', $list) }}" method="POST"
                          class="d-inline needs-confirm" data-confirm-msg="¿Estás seguro de eliminar esta lista? Esta acción no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-trash"></i> Delete List
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
