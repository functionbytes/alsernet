@extends('layouts.admin')

@section('title', 'Preview Email Template')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Preview Email Template</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.email-templates.index') }}">Email Templates</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.email-templates.edit', $emailTemplate) }}">{{ $emailTemplate->name }}</a></li>
                    <li class="breadcrumb-item active">Preview</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" class="btn btn-secondary">
                <i class="fa fa-edit me-1"></i> Edit Template
            </a>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        This preview uses sample data. The actual email will use real data when sent.
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-envelope me-1"></i> Email Preview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="row mb-2">
                            <div class="col-2 text-muted"><strong>From:</strong></div>
                            <div class="col-10">{{ config('mail.from.name') }} &lt;{{ config('mail.from.address') }}&gt;</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-2 text-muted"><strong>To:</strong></div>
                            <div class="col-10">recipient@example.com</div>
                        </div>
                        <div class="row">
                            <div class="col-2 text-muted"><strong>Subject:</strong></div>
                            <div class="col-10"><strong>{{ $renderedSubject }}</strong></div>
                        </div>
                    </div>

                    <div class="email-preview-body">
                        {!! $renderedHtml !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Template Info</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Name:</dt>
                        <dd class="col-sm-7">{{ $emailTemplate->name }}</dd>

                        <dt class="col-sm-5">Event Type:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-info">
                                {{ \App\Services\EmailTemplateVariableService::getEventTypes()[$emailTemplate->event_type] ?? $emailTemplate->event_type }}
                            </span>
                        </dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-{{ $emailTemplate->active ? 'success' : 'secondary' }}">
                                {{ $emailTemplate->active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>

                        <dt class="col-sm-5">Created:</dt>
                        <dd class="col-sm-7">{{ $emailTemplate->created_at->format('M d, Y H:i') }}</dd>

                        @if($emailTemplate->updated_at != $emailTemplate->created_at)
                            <dt class="col-sm-5">Updated:</dt>
                            <dd class="col-sm-7">{{ $emailTemplate->updated_at->format('M d, Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if($emailTemplate->description)
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Description</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $emailTemplate->description }}</p>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Preview Options</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="toggleViewMode()">
                            <i class="bi bi-code-slash me-1"></i> <span id="view-mode-text">View HTML Source</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.email-preview-body {
    min-height: 300px;
    border: 1px solid #dee2e6;
    padding: 20px;
    background: #fff;
    border-radius: 4px;
}

.email-preview-body img {
    max-width: 100%;
    height: auto;
}

@media print {
    .card-header, .btn, nav {
        display: none !important;
    }
}
</style>

<script>
let viewingSource = false;
const emailBody = document.querySelector('.email-preview-body');
const originalHtml = emailBody.innerHTML;

function toggleViewMode() {
    viewingSource = !viewingSource;
    const btn = document.getElementById('view-mode-text');

    if (viewingSource) {
        // Show HTML source
        const escaped = originalHtml
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        emailBody.innerHTML = `<pre class="mb-0"><code>${escaped}</code></pre>`;
        btn.textContent = 'View Rendered';
    } else {
        // Show rendered HTML
        emailBody.innerHTML = originalHtml;
        btn.textContent = 'View HTML Source';
    }
}
</script>
@endsection
