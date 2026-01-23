@extends('layouts.admin')

@section('title', 'View message template')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">View message template</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.message-templates.index') }}">Message templates</a></li>
                    <li class="breadcrumb-item active">{{ $template->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <!-- Template Details Card -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $template->name }}</h5>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.message-templates.edit', $template) }}"
                           class="btn btn-outline-primary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-outline-danger delete-btn"
                                data-url="{{ route('admin.message-templates.destroy', $template) }}">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Template Info -->
                    <div class="row mb-4">
                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                            <h6 class="text-muted small">Category</h6>
                            @if($template->category)
                                <span class="badge bg-secondary">{{ $categories[$template->category] ?? $template->category }}</span>
                            @else
                                <span class="text-muted">No category</span>
                            @endif
                        </div>
                        <div class="col-12 col-md-6">
                            <h6 class="text-muted small">Shortcut</h6>
                            @if($template->shortcut)
                                <code class="bg-light px-2 py-1 rounded">/{{ $template->shortcut }}</code>
                            @else
                                <span class="text-muted">No shortcut</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                            <h6 class="text-muted small">Type</h6>
                            @if($template->is_public)
                                <span class="badge bg-success">Public</span>
                                <small class="text-muted d-block mt-1">Available to all team members</small>
                            @else
                                <span class="badge bg-info">Private</span>
                                <small class="text-muted d-block mt-1">Only visible to you</small>
                            @endif
                        </div>
                        <div class="col-12 col-md-6">
                            <h6 class="text-muted small">Created by</h6>
                            @if($template->user)
                                <span>{{ $template->user->name }}</span>
                            @else
                                <span class="text-muted">Unknown</span>
                            @endif
                        </div>
                    </div>

                    <!-- Template Content -->
                    <div class="mb-4">
                        <h6 class="text-muted small">Template content</h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <pre class="mb-0" style="white-space: pre-wrap; font-family: 'Courier New', monospace;">{{ $template->content }}</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Preview with Example Data -->
                    <div class="mb-4">
                        <h6 class="text-muted small">Preview with example data</h6>
                        <div class="card border-primary">
                            <div class="card-body" id="preview-content">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Used Variables -->
                    @if($usedVariables->isNotEmpty())
                    <div class="mb-4">
                        <h6 class="text-muted small">Variables used in this template</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($usedVariables as $variable)
                                <span class="badge bg-light text-dark border">
                                    <code>{{ '{{' }}{{ $variable }}{{ '}}' }}</code>
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Usage Statistics Card -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Usage statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <div>
                                <h3 class="text-primary">{{ number_format($template->usage_count) }}</h3>
                                <small class="text-muted">Times used</small>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <div>
                                <h6 class="text-muted">
                                    @if($template->last_used_at)
                                        {{ $template->last_used_at->format('M d, Y') }}
                                        <br>
                                        <small>{{ $template->last_used_at->format('h:i A') }}</small>
                                    @else
                                        <span class="badge bg-secondary">Never used</span>
                                    @endif
                                </h6>
                                <small class="text-muted">Last used</small>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div>
                                <h6 class="text-muted">
                                    {{ $template->created_at->format('M d, Y') }}
                                    <br>
                                    <small>{{ $template->created_at->diffForHumans() }}</small>
                                </h6>
                                <small class="text-muted">Created</small>
                            </div>
                        </div>
                    </div>

                    @if($template->usage_count > 0)
                    <div class="alert alert-success mt-3">
                        <i class="fa fa-check-circle"></i>
                        <strong>Popular template!</strong>
                        This template has been used {{ number_format($template->usage_count) }} time(s).
                    </div>
                    @else
                    <div class="alert alert-info mt-3">
                        <i class="fa fa-info-circle"></i>
                        This template hasn't been used yet. Try it in a conversation!
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="card sticky-top mb-4" style="top: 20px;">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fa fa-bolt me-1"></i> Quick actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary test-template">
                            <i class="fa fa-play-circle"></i> Test template
                        </button>
                        <a href="{{ route('admin.message-templates.edit', $template) }}" class="btn btn-outline-primary">
                            <i class="fa fa-edit"></i> Edit template
                        </a>
                        <button type="button" class="btn btn-outline-secondary copy-content">
                            <i class="fa fa-clipboard"></i> Copy content
                        </button>
                        <button type="button" class="btn btn-outline-info duplicate-template">
                            <i class="fa fa-files-o"></i> Duplicate
                        </button>
                        <hr>
                        <button type="button" class="btn btn-outline-danger delete-btn"
                                data-url="{{ route('admin.message-templates.destroy', $template) }}">
                            <i class="fa fa-trash"></i> Delete template
                        </button>
                    </div>
                </div>
            </div>

            <!-- How to Use Card -->
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fa fa-question-circle me-1"></i> How to use</h6>
                </div>
                <div class="card-body">
                    <ol class="small ps-3">
                        <li class="mb-2">Open any conversation</li>
                        <li class="mb-2">
                            @if($template->shortcut)
                                Type <code>/{{ $template->shortcut }}</code> in the message box
                            @else
                                Search for "{{ $template->name }}" in templates
                            @endif
                        </li>
                        <li class="mb-2">The template will be inserted with variables replaced</li>
                        <li>Review and send the message</li>
                    </ol>

                    <div class="alert alert-info small mb-0 mt-3">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>Tip:</strong> Variables like <code>{{ '{{contact.name}}' }}</code> will automatically be replaced with real data.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this message template? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Test Template Modal -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">This is how the template will appear with example data:</p>
                <div class="card bg-light">
                    <div class="card-body" id="test-preview-content">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const templateContent = @json($template->content);

    // Generate preview with example data
    function generatePreview() {
        let content = templateContent;

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

        return content;
    }

    // Initial preview render
    $('#preview-content').html(generatePreview());

    // Test template button
    $('.test-template').on('click', function() {
        $('#test-preview-content').html(generatePreview());
        const testModal = new bootstrap.Modal(document.getElementById('testModal'));
        testModal.show();
    });

    // Copy content button
    $('.copy-content').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();

        navigator.clipboard.writeText(templateContent).then(function() {
            $btn.html('<i class="fa fa-check-circle"></i> Copied!');
            $btn.removeClass('btn-outline-secondary').addClass('btn-success');

            setTimeout(function() {
                $btn.html(originalHtml);
                $btn.removeClass('btn-success').addClass('btn-outline-secondary');
            }, 2000);
        }).catch(function(err) {
            console.error('Failed to copy:', err);
            alert('Failed to copy content. Please try again.');
        });
    });

    // Duplicate template button
    $('.duplicate-template').on('click', function() {
        if (confirm('Create a copy of this template?')) {
            // Redirect to create page with query params
            const params = new URLSearchParams({
                name: '{{ $template->name }} (Copy)',
                content: templateContent,
                category: '{{ $template->category }}',
                is_public: '{{ $template->is_public ? '1' : '0' }}'
            });
            window.location.href = '{{ route("admin.message-templates.create") }}?' + params.toString();
        }
    });

    // Delete confirmation
    $('.delete-btn').on('click', function() {
        const url = $(this).data('url');
        $('#delete-form').attr('action', url);
        new bootstrap.Modal($('#deleteModal')).show();
    });
});
</script>
@endpush
