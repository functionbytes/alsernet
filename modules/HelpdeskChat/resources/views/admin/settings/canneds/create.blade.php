@extends('layouts.admin')

@section('title', 'Create canned response')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Create canned response</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.canneds.index') }}">Canned responses</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form id="cannedForm" action="{{ route('admin.canneds.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                    id="title" name="title" value="{{ old('title') }}"
                                    placeholder="e.g., Welcome message, Thank you, Closing statement">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">A descriptive name for this response (optional, for organization)</div>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="short_code" class="form-label">Short code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" class="form-control @error('short_code') is-invalid @enderror"
                                        id="short_code" name="short_code" value="{{ old('short_code') }}"
                                        placeholder="e.g., greeting, thanks, closing">
                                </div>
                                @error('short_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Use lowercase, no spaces. Type this code in chat to quickly insert this response</div>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="visibility" class="form-label">Visibility <span class="text-danger">*</span></label>
                                <select class="form-select @error('visibility') is-invalid @enderror" id="visibility" name="visibility">
                                    <option value="personal" {{ old('visibility') === 'personal' ? 'selected' : '' }}>
                                        Personal - Only visible to me
                                    </option>
                                    <option value="team" {{ old('visibility') === 'team' ? 'selected' : '' }}>
                                        Team - Visible to my team members
                                    </option>
                                    <option value="everyone" {{ old('visibility', 'everyone') === 'everyone' ? 'selected' : '' }}>
                                        Everyone - Visible to all agents
                                    </option>
                                </select>
                                @error('visibility')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror"
                                    id="description" name="description" value="{{ old('description') }}"
                                    placeholder="Brief description of when to use this response">
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('content') is-invalid @enderror"
                                    id="content" name="content" rows="8">{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">The message that will be inserted. Use variables from the list below</div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Insert variable</label>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($availableVariables as $key => $description)
                                        <button type="button" class="btn btn-outline-secondary btn-sm insert-var-btn"
                                            data-variable="{{ $key }}" title="{{ $description }}">
                                            <i class="fa fa-code"></i> {{ '{{' . $key . '}}' }}
                                        </button>
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mt-2">Click to insert a variable at cursor position. Variables are replaced with actual values when used</small>
                            </div>

                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-check-circle me-1"></i> Create response
                                    </button>
                                    <a href="{{ route('admin.canneds.index') }}" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fa fa-info-circle me-1"></i> How to use</h6>
                    <ol class="small mb-0">
                        <li>Create a canned response with a short code</li>
                        <li>While chatting, type <code>/</code> to see available responses</li>
                        <li>Type the short code or select from the list</li>
                        <li>Variables will be replaced with actual values</li>
                    </ol>
                </div>
            </div>

            <div class="card border-primary mb-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fa fa-code me-1"></i> Available variables</h6>
                    <div class="small">
                        @foreach($availableVariables as $key => $description)
                            <div class="mb-2">
                                <code class="bg-light px-2 py-1 rounded">{{ '{{' . $key . '}}' }}</code><br>
                                <span class="text-muted">{{ $description }}</span>
                            </div>
                        @endforeach
                    </div>
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

I'm @{{ '{{agent.name}}' }} from @{{ '{{inbox.name}}' }}. How can I help you today?</pre>
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
    $('#cannedForm').validate({
        rules: {
            title: {
                maxlength: 100
            },
            short_code: {
                required: true,
                minlength: 2,
                maxlength: 50,
                pattern: /^[a-z0-9_-]+$/
            },
            description: {
                maxlength: 255
            },
            content: {
                required: true,
                minlength: 5
            },
            visibility: {
                required: true
            }
        },
        messages: {
            title: {
                maxlength: 'Title must not exceed 100 characters'
            },
            short_code: {
                required: 'Short code is required',
                minlength: 'Short code must be at least 2 characters',
                maxlength: 'Short code must not exceed 50 characters',
                pattern: 'Short code must contain only lowercase letters, numbers, hyphens and underscores'
            },
            description: {
                maxlength: 'Description must not exceed 255 characters'
            },
            content: {
                required: 'Content is required',
                minlength: 'Content must be at least 5 characters'
            },
            visibility: {
                required: 'Visibility is required'
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

    // Insert variable at cursor position
    $('.insert-var-btn').on('click', function() {
        const variable = '{{' + $(this).data('variable') + '}}';
        const textarea = $('#content')[0];
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        textarea.value = text.substring(0, start) + variable + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        textarea.focus();
    });
});
</script>
@endpush
