@extends('layouts.admin')

@section('title', 'Create message template')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Create message template</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.message-templates.index') }}">Message templates</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form id="templateForm" action="{{ route('admin.message-templates.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Name -->
                            <div class="col-12 mb-3">
                                <label for="name" class="form-label">Template name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}"
                                       placeholder="e.g., Welcome message, Follow-up reminder">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">A descriptive name for this template</div>
                            </div>

                            <!-- Category and Shortcut -->
                            <div class="col-12 col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select @error('category') is-invalid @enderror"
                                        id="category" name="category">
                                    <option value="">Select a category</option>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="shortcut" class="form-label">Shortcut</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" class="form-control @error('shortcut') is-invalid @enderror"
                                           id="shortcut" name="shortcut" value="{{ old('shortcut') }}"
                                           placeholder="e.g., welcome, followup">
                                </div>
                                @error('shortcut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Use lowercase, numbers, and hyphens only</div>
                            </div>

                            <!-- Content -->
                            <div class="col-12 mb-3">
                                <label for="content" class="form-label">Template content <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('content') is-invalid @enderror"
                                          id="content" name="content" rows="8"
                                          placeholder="Enter your message template here. Use variables from the sidebar...">{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">The message that will be inserted. Use variables from the sidebar</div>
                            </div>

                            <!-- Is Public -->
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public"
                                           value="1" {{ old('is_public') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_public">
                                        Make this template public
                                        <br>
                                        <small class="text-muted">Public templates can be used by all team members</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Preview Section -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Preview</label>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div id="preview-content" class="text-muted">
                                            Type your template content to see a preview...
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-check-circle me-1"></i> Create template
                                    </button>
                                    <a href="{{ route('admin.message-templates.index') }}" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Available Variables Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fa fa-info-circle me-1"></i> How to use</h6>
                    <ol class="small mb-0">
                        <li>Create a message template with optional shortcut</li>
                        <li>While in conversation, type <code>/</code> to see available templates</li>
                        <li>Type the shortcut or select from the list</li>
                        <li>Variables will be replaced with actual values</li>
                    </ol>
                </div>
            </div>

            <div class="card border-primary mb-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fa fa-code me-1"></i> Available variables</h6>
                    <p class="small text-muted">Click to insert into template:</p>

                    @foreach($availableVariables as $group => $variables)
                        <h6 class="mt-3 text-uppercase small fw-bold">{{ ucfirst($group) }}</h6>
                        @foreach($variables as $key => $label)
                            <div class="mb-2">
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm w-100 text-start insert-variable"
                                        data-variable="{{ $group }}.{{ $key }}">
                                    <i class="fa fa-code"></i> @{{ '{{' }} {{ $group }}.{{ $key }} {{ '}}' }}
                                    <br>
                                    <small class="text-muted">{{ $label }}</small>
                                </button>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="card bg-warning bg-opacity-10 border-warning">
                <div class="card-body">
                    <h6 class="card-title"><i class="fa fa-lightbulb me-1"></i> Examples</h6>
                    <div class="small">
                        <p class="mb-2"><strong>Greeting:</strong></p>
                        <pre class="bg-white p-2 rounded small mb-3">Hi @{{ '{{contact.name}}' }},

Thank you for reaching out to us!</pre>

                        <p class="mb-2"><strong>Follow-up:</strong></p>
                        <pre class="bg-white p-2 rounded small mb-0">Hello @{{ '{{contact.name}}' }},

I'm @{{ '{{agent.name}}' }} from @{{ '{{account.name}}' }}. How can I help you today?</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // jQuery Validate
    $('#templateForm').validate({
        rules: {
            name: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            shortcut: {
                minlength: 2,
                maxlength: 50,
                pattern: /^[a-z0-9-]+$/
            },
            content: {
                required: true,
                minlength: 5
            }
        },
        messages: {
            name: {
                required: 'Template name is required',
                minlength: 'Template name must be at least 2 characters',
                maxlength: 'Template name must not exceed 100 characters'
            },
            shortcut: {
                minlength: 'Shortcut must be at least 2 characters',
                maxlength: 'Shortcut must not exceed 50 characters',
                pattern: 'Shortcut must contain only lowercase letters, numbers, and hyphens'
            },
            content: {
                required: 'Template content is required',
                minlength: 'Template content must be at least 5 characters'
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

    // Add custom pattern validation method
    $.validator.addMethod('pattern', function(value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        if (typeof param === 'string') {
            param = new RegExp('^(?:' + param + ')$');
        }
        return param.test(value);
    }, 'Invalid format');

    const $contentTextarea = $('#content');
    const $previewContent = $('#preview-content');

    // Insert variable on click
    $('.insert-variable').on('click', function() {
        const variable = '{{' + $(this).data('variable') + '}}';
        const textarea = $contentTextarea[0];
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        // Insert variable at cursor position
        textarea.value = text.substring(0, start) + variable + text.substring(end);

        // Update cursor position
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;

        // Focus back on textarea
        textarea.focus();

        // Update preview
        updatePreview();
    });

    // Update preview on content change
    $contentTextarea.on('input', function() {
        updatePreview();
    });

    // Update preview function
    function updatePreview() {
        let content = $contentTextarea.val();

        if (content.trim() === '') {
            $previewContent.html('<span class="text-muted">Type your template content to see a preview...</span>');
            return;
        }

        // Replace variables with example values for preview
        content = content
            .replace(/\{\{contact\.name\}\}/g, '<span class="badge bg-primary">John Doe</span>')
            .replace(/\{\{contact\.email\}\}/g, '<span class="badge bg-primary">john@example.com</span>')
            .replace(/\{\{contact\.phone\}\}/g, '<span class="badge bg-primary">+1234567890</span>')
            .replace(/\{\{agent\.name\}\}/g, '<span class="badge bg-success">Support Agent</span>')
            .replace(/\{\{agent\.email\}\}/g, '<span class="badge bg-success">agent@company.com</span>')
            .replace(/\{\{conversation\.id\}\}/g, '<span class="badge bg-info">12345</span>')
            .replace(/\{\{conversation\.status\}\}/g, '<span class="badge bg-info">open</span>')
            .replace(/\{\{account\.name\}\}/g, '<span class="badge bg-warning text-dark">Company Name</span>')
            .replace(/\{\{system\.date\}\}/g, '<span class="badge bg-secondary">2024-01-15</span>')
            .replace(/\{\{system\.time\}\}/g, '<span class="badge bg-secondary">14:30:00</span>')
            .replace(/\{\{system\.datetime\}\}/g, '<span class="badge bg-secondary">2024-01-15 14:30:00</span>');

        // Replace line breaks with <br>
        content = content.replace(/\n/g, '<br>');

        $previewContent.html(content);
    }

    // Initial preview
    updatePreview();

    // Validate shortcut format
    $('#shortcut').on('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    });
});
</script>
@endpush
