@extends('layouts.admin')

@section('title', 'Edit label')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Edit label</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.labels.index') }}">Labels</a></li>
                    <li class="breadcrumb-item active">Edit: {{ $label->title }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="labelForm" action="{{ route('admin.labels.update', $label) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                id="title" name="title" value="{{ old('title', $label->title) }}" placeholder="e.g., Customer issue">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">A short, descriptive name for this label</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="description" name="description" rows="3" placeholder="Add any relevant details about this label">{{ old('description', $label->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Optional description to help with label identification</div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="color" class="form-label">Color <span class="text-danger">*</span></label>
                                <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                                    id="color" name="color" value="{{ old('color', $label->color) }}">
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Choose a color to visually distinguish this label</div>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_on_sidebar"
                                        name="show_on_sidebar" value="1"
                                        {{ old('show_on_sidebar', $label->show_on_sidebar) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_on_sidebar">
                                        <strong>Show on sidebar</strong>
                                    </label>
                                </div>
                                <div class="form-text">Display this label in the conversation sidebar for quick access</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle me-1"></i> Update label
                            </button>
                            <a href="{{ route('admin.labels.index') }}" class="btn btn-secondary">
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
                    <h6 class="card-title">Label tips</h6>
                    <ul class="small mb-0">
                        <li>Use labels to categorize conversations by topic or priority</li>
                        <li>Choose descriptive, memorable titles</li>
                        <li>Pick colors that make labels easy to identify at a glance</li>
                        <li>Enable "Show on sidebar" for frequently used labels</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#labelForm').validate({
        rules: {
            title: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            description: {
                maxlength: 500
            },
            color: {
                required: true
            }
        },
        messages: {
            title: {
                required: 'Title is required',
                minlength: 'Title must be at least 2 characters',
                maxlength: 'Title must not exceed 100 characters'
            },
            description: {
                maxlength: 'Description must not exceed 500 characters'
            },
            color: {
                required: 'Color is required'
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
