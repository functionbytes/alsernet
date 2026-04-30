@extends('layouts.theme')

@section('content')

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Create new tag</h4>
                    <p class="text-muted small mb-0">Define a new tag to organize and categorize conversations</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Tag information</h5>
        </div>

        <form id="tagForm" method="POST" action="{{ route('manager.chat.settings.tickets.tags.store') }}" novalidate>
            @csrf

            <div class="card-body">
                <div class="row g-4">

                    <!-- Basic Information Section -->
                    <div class="col-12">
                        <h6 class="fw-semibold mb-3">Basic information</h6>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Name
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g., Urgent">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Visible name for the tag</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="Auto-generated if empty">
                            @error('slug')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Lowercase letters, numbers and hyphens only</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Optional description of the tag">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Provide additional context about this tag</small>
                        </div>
                    </div>

                    <!-- Color Configuration Section -->
                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h6 class="fw-semibold mb-3">Color configuration</h6>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', '#081A28') }}" id="colorPicker" style="width: 60px; height: 40px;">
                                <input type="text" id="colorHex" class="form-control" value="{{ old('color', '#081A28') }}" readonly style="max-width: 120px;">
                                <div id="colorPreview" class="rounded border" style="width: 40px; height: 40px; background-color: {{ old('color', '#081A28') }};"></div>
                            </div>
                            <small class="text-muted d-block mt-1">Choose a color to identify the tag visually</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Suggested colors</label>
                            <small class="text-muted d-block mb-2">Click any color to apply it quickly</small>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#081A28" style="background-color: #081A28; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Green"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#13C672" style="background-color: #13C672; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Emerald"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#FA896B" style="background-color: #FA896B; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Coral"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#FEC90F" style="background-color: #FEC90F; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Yellow"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#539BFF" style="background-color: #539BFF; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Blue"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#8E44AD" style="background-color: #8E44AD; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Purple"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#E74C3C" style="background-color: #E74C3C; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Red"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#95A5A6" style="background-color: #95A5A6; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Gray"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#FF6B9D" style="background-color: #FF6B9D; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Pink"></button>
                                <button type="button" class="btn btn-sm color-preset p-0" data-color="#00D4FF" style="background-color: #00D4FF; width: 40px; height: 40px; border-radius: 6px; border: 2px solid #dee2e6;" title="Cyan"></button>
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
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activeCheck">
                                <strong>Tag is active</strong>
                            </label>
                            <small class="text-muted d-block mt-1">When enabled, this tag is available for assignment to conversations</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Create tag
                </button>
                <a href="{{ route('manager.chat.settings.tickets.tags.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize form validation
    $('#tagForm').validate({
        rules: {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            slug: {
                maxlength: 100,
                pattern: /^[a-z0-9-]*$/
            },
            description: {
                maxlength: 500
            }
        },
        messages: {
            name: {
                required: 'Name is required',
                minlength: 'Name must be at least 2 characters',
                maxlength: 'Name cannot exceed 100 characters'
            },
            slug: {
                maxlength: 'Slug cannot exceed 100 characters',
                pattern: 'Slug can only contain lowercase letters, numbers and hyphens'
            },
            description: {
                maxlength: 'Description cannot exceed 500 characters'
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

    // Auto-generate slug from name
    $('input[name="name"]').on('input', function() {
        if (!$('input[name="slug"]').val()) {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $('input[name="slug"]').val(slug);
        }
    });

    // Color picker sync
    $('#colorPicker').on('input', function() {
        const color = $(this).val();
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });

    // Color presets
    $('.color-preset').on('click', function(e) {
        e.preventDefault();
        const color = $(this).data('color');
        $('#colorPicker').val(color);
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });

    // Toast messages
    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Success');
    @endif
});
</script>
@endsection
