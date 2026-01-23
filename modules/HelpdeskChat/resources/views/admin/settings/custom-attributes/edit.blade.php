@extends('layouts.admin')

@section('title', 'Edit Custom Attribute')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Edit Custom Attribute</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.custom-attributes.index') }}">Custom Attributes</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <form action="{{ route('admin.custom-attributes.update', $customAttribute) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="attribute_display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('attribute_display_name') is-invalid @enderror"
                                id="attribute_display_name" name="attribute_display_name"
                                value="{{ old('attribute_display_name', $customAttribute->attribute_display_name) }}" required>
                            @error('attribute_display_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="attribute_key" class="form-label">Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('attribute_key') is-invalid @enderror"
                                id="attribute_key" name="attribute_key"
                                value="{{ old('attribute_key', $customAttribute->attribute_key) }}" required>
                            @error('attribute_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="attribute_description" class="form-label">Description</label>
                            <textarea class="form-control" id="attribute_description" name="attribute_description"
                                rows="2">{{ old('attribute_description', $customAttribute->attribute_description) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" readonly
                                    value="{{ $customAttribute->attribute_model === 0 ? 'Contact' : 'Conversation' }}">
                                <div class="form-text">Model cannot be changed after creation</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="attribute_display_type" class="form-label">Display Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="attribute_display_type" name="attribute_display_type" required>
                                    <option value="0" {{ $customAttribute->attribute_display_type == 0 ? 'selected' : '' }}>Text</option>
                                    <option value="1" {{ $customAttribute->attribute_display_type == 1 ? 'selected' : '' }}>Number</option>
                                    <option value="2" {{ $customAttribute->attribute_display_type == 2 ? 'selected' : '' }}>Currency</option>
                                    <option value="3" {{ $customAttribute->attribute_display_type == 3 ? 'selected' : '' }}>Percent</option>
                                    <option value="4" {{ $customAttribute->attribute_display_type == 4 ? 'selected' : '' }}>Link</option>
                                    <option value="5" {{ $customAttribute->attribute_display_type == 5 ? 'selected' : '' }}>Date</option>
                                    <option value="6" {{ $customAttribute->attribute_display_type == 6 ? 'selected' : '' }}>List</option>
                                    <option value="7" {{ $customAttribute->attribute_display_type == 7 ? 'selected' : '' }}>Checkbox</option>
                                </select>
                            </div>
                        </div>

                        <div id="list-values-section" style="display: {{ $customAttribute->attribute_display_type == 6 ? 'block' : 'none' }};" class="mb-3">
                            <label for="attribute_values" class="form-label">List Values</label>
                            <textarea class="form-control" id="attribute_values_input" rows="3">{{ is_array($customAttribute->attribute_values) ? implode("\n", $customAttribute->attribute_values) : '' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="regex_pattern" class="form-label">Validation Regex</label>
                            <input type="text" class="form-control" id="regex_pattern" name="regex_pattern"
                                value="{{ old('regex_pattern', $customAttribute->regex_pattern) }}">
                        </div>

                        <div class="mb-3">
                            <label for="regex_cue" class="form-label">Validation Hint</label>
                            <input type="text" class="form-control" id="regex_cue" name="regex_cue"
                                value="{{ old('regex_cue', $customAttribute->regex_cue) }}">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check-circle me-1"></i> Update Attribute
                    </button>
                    <a href="{{ route('admin.custom-attributes.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
@vite(['resources/js/admin/custom-attributes.js'])
@endpush
@endsection
