@extends('layouts.admin')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Edit attribute</h4>
                    <p class="text-muted small mb-0">Update the attribute configuration</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Attribute information</h5>
        </div>

        <form id="attributeForm" method="POST" action="{{ route('manager.helpdesk.settings.tickets.attributes.update', $attribute->id) }}" novalidate>
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="row g-4">

                    <!-- Basic Information Section -->
                    <div class="col-12">
                        <h6 class="fw-semibold mb-3">Basic information</h6>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Attribute name
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $attribute->name) }}" placeholder="e.g., Customer priority">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Visible name for users</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Unique key
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="key" class="form-control bg-light" value="{{ old('key', $attribute->key) }}" readonly>
                            @error('key')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Key cannot be modified once created</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Optional description of the attribute">{{ old('description', $attribute->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Help other users understand the purpose of this attribute</small>
                        </div>
                    </div>

                    <!-- Field Configuration Section -->
                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h6 class="fw-semibold mb-3">Field configuration</h6>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Field type
                                <span class="text-danger">*</span>
                            </label>
                            <select name="format" class="form-select" id="formatSelect">
                                <option value="text" {{ old('format', $attribute->format) === 'text' ? 'selected' : '' }}>Text</option>
                                <option value="textarea" {{ old('format', $attribute->format) === 'textarea' ? 'selected' : '' }}>Text area</option>
                                <option value="number" {{ old('format', $attribute->format) === 'number' ? 'selected' : '' }}>Number</option>
                                <option value="switch" {{ old('format', $attribute->format) === 'switch' ? 'selected' : '' }}>Switch (yes/no)</option>
                                <option value="rating" {{ old('format', $attribute->format) === 'rating' ? 'selected' : '' }}>Rating (stars)</option>
                                <option value="select" {{ old('format', $attribute->format) === 'select' ? 'selected' : '' }}>Select list</option>
                                <option value="checkboxGroup" {{ old('format', $attribute->format) === 'checkboxGroup' ? 'selected' : '' }}>Checkbox group</option>
                                <option value="date" {{ old('format', $attribute->format) === 'date' ? 'selected' : '' }}>Date</option>
                            </select>
                            @error('format')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Changing the type may affect existing data</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Permission
                                <span class="text-danger">*</span>
                            </label>
                            <select name="permission" class="form-select">
                                <option value="agentCanEdit" {{ old('permission', $attribute->permission) === 'agentCanEdit' ? 'selected' : '' }}>
                                    Agent can edit
                                </option>
                                <option value="userCanEdit" {{ old('permission', $attribute->permission) === 'userCanEdit' ? 'selected' : '' }}>
                                    User can edit
                                </option>
                                <option value="userCanView" {{ old('permission', $attribute->permission) === 'userCanView' ? 'selected' : '' }}>
                                    User can view only
                                </option>
                            </select>
                            @error('permission')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Define who can modify this field</small>
                        </div>
                    </div>

                    <!-- Options Configuration (for select/checkbox types) -->
                    <div class="col-12" id="optionsContainer" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Options</label>
                            <div id="optionsList">
                                @php
                                    $existingOptions = old('options', $attribute->config['options'] ?? []);
                                @endphp
                                @if(!empty($existingOptions))
                                    @foreach($existingOptions as $index => $option)
                                        <div class="option-item mb-2">
                                            <div class="input-group">
                                                <input type="text" name="options[]" class="form-control" value="{{ $option }}" placeholder="Option {{ $index + 1 }}">
                                                <button type="button" class="btn btn-outline-danger remove-option">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="option-item mb-2">
                                        <div class="input-group">
                                            <input type="text" name="options[]" class="form-control" placeholder="Option 1">
                                            <button type="button" class="btn btn-outline-danger remove-option">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addOption">
                                <i class="fa fa-plus"></i> Add option
                            </button>
                            @error('options')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-2">Define the available options for this field</small>
                        </div>
                    </div>

                    <!-- Number Range Configuration -->
                    <div class="col-12" id="numberRangeContainer" style="display: none;">
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Minimum value</label>
                                    <input type="number" name="min_value" class="form-control"
                                           value="{{ old('min_value', $attribute->config['min'] ?? '') }}"
                                           placeholder="No limit">
                                    <small class="text-muted d-block mt-1">Minimum allowed value (optional)</small>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Maximum value</label>
                                    <input type="number" name="max_value" class="form-control"
                                           value="{{ old('max_value', $attribute->config['max'] ?? '') }}"
                                           placeholder="No limit">
                                    <small class="text-muted d-block mt-1">Maximum allowed value (optional)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Options Section -->
                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h6 class="fw-semibold mb-3">Options</h6>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="required" value="0">
                            <input type="checkbox" name="required" class="form-check-input" id="requiredCheck" value="1" {{ old('required', $attribute->required) ? 'checked' : '' }}>
                            <label class="form-check-label" for="requiredCheck">
                                <strong>Required field</strong>
                            </label>
                            <small class="text-muted d-block mt-1">The field must have a value to save</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" class="form-check-input" id="activeCheck" value="1" {{ old('active', $attribute->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activeCheck">
                                <strong>Attribute is active</strong>
                            </label>
                            <small class="text-muted d-block mt-1">When enabled, this attribute is visible and available</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update attribute
                </button>
                <a href="{{ route('manager.helpdesk.settings.tickets.attributes.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let optionCounter = {{ !empty($existingOptions) ? count($existingOptions) : 1 }};

    // Initialize form validation
    $('#attributeForm').validate({
        rules: {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            description: {
                maxlength: 500
            },
            format: {
                required: true
            },
            permission: {
                required: true
            },
            min_value: {
                number: true
            },
            max_value: {
                number: true
            }
        },
        messages: {
            name: {
                required: 'Attribute name is required',
                minlength: 'Attribute name must be at least 2 characters',
                maxlength: 'Attribute name cannot exceed 100 characters'
            },
            description: {
                maxlength: 'Description cannot exceed 500 characters'
            },
            format: {
                required: 'Field type is required'
            },
            permission: {
                required: 'Permission is required'
            },
            min_value: {
                number: 'Minimum value must be a number'
            },
            max_value: {
                number: 'Maximum value must be a number'
            }
        },
        errorElement: 'div',
        errorClass: 'invalid-feedback d-block',
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        }
    });

    // Format select handler
    $('#formatSelect').on('change', function() {
        const format = $(this).val();

        // Hide all config sections
        $('#optionsContainer').hide();
        $('#numberRangeContainer').hide();

        // Show relevant config section
        if (format === 'select' || format === 'checkboxGroup') {
            $('#optionsContainer').show();
        } else if (format === 'number') {
            $('#numberRangeContainer').show();
        }
    }).trigger('change');

    // Add option
    $('#addOption').on('click', function() {
        optionCounter++;
        const optionHtml = `
            <div class="option-item mb-2">
                <div class="input-group">
                    <input type="text" name="options[]" class="form-control" placeholder="Option ${optionCounter}">
                    <button type="button" class="btn btn-outline-danger remove-option">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#optionsList').append(optionHtml);
    });

    // Remove option
    $(document).on('click', '.remove-option', function() {
        if ($('.option-item').length > 1) {
            $(this).closest('.option-item').remove();
        } else {
            toastr.warning('Must keep at least one option', 'Warning');
        }
    });

    // Toast messages
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Success');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
