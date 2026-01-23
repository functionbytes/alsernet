@extends('layouts.admin')

@section('title', 'Create Custom Attribute')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Create Custom Attribute</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.custom-attributes.index') }}">Custom Attributes</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <form action="{{ route('admin.custom-attributes.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="attribute_display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('attribute_display_name') is-invalid @enderror"
                                id="attribute_display_name" name="attribute_display_name" value="{{ old('attribute_display_name') }}" required>
                            @error('attribute_display_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="attribute_key" class="form-label">Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('attribute_key') is-invalid @enderror"
                                id="attribute_key" name="attribute_key" value="{{ old('attribute_key') }}" required
                                placeholder="e.g., customer_id, order_number">
                            @error('attribute_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Unique identifier (lowercase, underscores allowed)</div>
                        </div>

                        <div class="mb-3">
                            <label for="attribute_description" class="form-label">Description</label>
                            <textarea class="form-control @error('attribute_description') is-invalid @enderror"
                                id="attribute_description" name="attribute_description" rows="2">{{ old('attribute_description') }}</textarea>
                            @error('attribute_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="attribute_model" class="form-label">Model <span class="text-danger">*</span></label>
                                <select class="form-select @error('attribute_model') is-invalid @enderror"
                                    id="attribute_model" name="attribute_model" required>
                                    <option value="0" {{ old('attribute_model') == 0 ? 'selected' : '' }}>Contact</option>
                                    <option value="1" {{ old('attribute_model') == 1 ? 'selected' : '' }}>Conversation</option>
                                </select>
                                @error('attribute_model')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="attribute_display_type" class="form-label">Display Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('attribute_display_type') is-invalid @enderror"
                                    id="attribute_display_type" name="attribute_display_type" required>
                                    <option value="0">Text</option>
                                    <option value="1">Number</option>
                                    <option value="2">Currency</option>
                                    <option value="3">Percent</option>
                                    <option value="4">Link</option>
                                    <option value="5">Date</option>
                                    <option value="6">List</option>
                                    <option value="7">Checkbox</option>
                                </select>
                                @error('attribute_display_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div id="list-values-section" style="display: none;" class="mb-3">
                            <label for="attribute_values" class="form-label">List Values</label>
                            <textarea class="form-control" id="attribute_values_input" rows="3"
                                placeholder="Enter one value per line"></textarea>
                            <div class="form-text">For List type: enter one option per line</div>
                        </div>

                        <div class="mb-3">
                            <label for="regex_pattern" class="form-label">Validation Regex (Optional)</label>
                            <input type="text" class="form-control" id="regex_pattern" name="regex_pattern" value="{{ old('regex_pattern') }}">
                            <div class="form-text">Regular expression for input validation</div>
                        </div>

                        <div class="mb-3">
                            <label for="regex_cue" class="form-label">Validation Hint</label>
                            <input type="text" class="form-control" id="regex_cue" name="regex_cue" value="{{ old('regex_cue') }}"
                                placeholder="e.g., Must be a valid email address">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check-circle me-1"></i> Create Attribute
                    </button>
                    <a href="{{ route('admin.custom-attributes.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Attribute Types</h6>
                        <dl class="small mb-0">
                            <dt>Text</dt>
                            <dd>Single line text input</dd>
                            <dt>Number</dt>
                            <dd>Numeric values only</dd>
                            <dt>List</dt>
                            <dd>Dropdown with predefined options</dd>
                            <dt>Checkbox</dt>
                            <dd>Boolean yes/no</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
@vite(['resources/js/admin/custom-attributes.js'])
@endpush
@endsection
