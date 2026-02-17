@extends('layouts.theme')

@section('page_title', __('seo::robots-txt.title'))

@section('content')
    <div class="row g-4">
        {{-- Columna principal --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">{{ __('seo::robots-txt.editor_title') }}</h5>
                    <p class="small mb-0 text-muted">{{ __('seo::robots-txt.editor_description') }}</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.theme.robots-txt.update') }}" id="robots-txt-form">
                        @csrf
                        <div class="mb-4">
                            <label for="robots-editor" class="form-label fw-semibold">{{ __('seo::robots-txt.content') }}</label>
                            <textarea id="robots-editor" name="robots_txt" class="form-control">{{ old('robots_txt', $robotsTxt) }}</textarea>
                            @error('robots_txt')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ __('seo::robots-txt.save') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Columna lateral con info --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-semibold mb-0">{{ __('seo::robots-txt.info_title') }}</h6>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">{{ __('seo::robots-txt.info_description') }}</p>
                    <div class="alert alert-info small mb-0">
                        <strong>{{ __('seo::robots-txt.public_url') }}:</strong><br>
                        <code>{{ url('/robots.txt') }}</code>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-semibold mb-0">{{ __('seo::robots-txt.directives_title') }}</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="small">
                        <dt class="fw-semibold">User-agent</dt>
                        <dd class="text-muted mb-2">{{ __('seo::robots-txt.directive_user_agent') }}</dd>

                        <dt class="fw-semibold">Allow</dt>
                        <dd class="text-muted mb-2">{{ __('seo::robots-txt.directive_allow') }}</dd>

                        <dt class="fw-semibold">Disallow</dt>
                        <dd class="text-muted mb-2">{{ __('seo::robots-txt.directive_disallow') }}</dd>

                        <dt class="fw-semibold">Sitemap</dt>
                        <dd class="text-muted">{{ __('seo::robots-txt.directive_sitemap') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/theme/monokai.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.js"></script>
    <script>
        $(document).ready(function () {
            var robotsEditor = CodeMirror.fromTextArea(document.getElementById('robots-editor'), {
                mode: null,
                theme: 'monokai',
                lineNumbers: true,
                indentUnit: 4,
                indentWithTabs: false,
                lineWrapping: true,
                matchBrackets: false,
                styleActiveLine: true,
                highlightSelectionMatches: { showToken: /\w/, annotateScrollbar: true },
            });

            $('#robots-txt-form').on('submit', function () {
                robotsEditor.save();
            });
        });
    </script>
@endpush
