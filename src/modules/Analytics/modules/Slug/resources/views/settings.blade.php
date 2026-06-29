@extends('layouts.theme')

@section('content')
<div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ trans('slug::slug.settings.title') }}</h3>
            <p class="card-subtitle">{{ trans('slug::slug.settings.description') }}</p>
        </div>

        <form action="{{ route('slug.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle me-2"></i>
                    Configure URL structure for different content types. Use variables: %year%, %month%, %day%, %category%
                </div>

                {{-- Automatic translation toggle --}}
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="slug_turn_off_automatic_url_translation_into_latin"
                            name="slug_turn_off_automatic_url_translation_into_latin"
                            value="1"
                            {{ setting('slug_turn_off_automatic_url_translation_into_latin') ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="slug_turn_off_automatic_url_translation_into_latin">
                            {{ trans('slug::slug.settings.turn_off_automatic_url_translation_into_latin') }}
                        </label>
                    </div>
                    <small class="text-muted">
                        If enabled, URLs will keep original characters (ñ, é, etc.) instead of converting to Latin (n, e, etc.)
                    </small>
                </div>

                <hr>

                {{-- Permalink prefixes for different models --}}
                <h5 class="mb-3">Permalink Prefixes</h5>

                <div class="mb-3">
                    <label for="permalink-page" class="form-label">
                        {{ trans('slug::slug.prefixes.page') }}
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        id="permalink-page"
                        name="permalink-Modules_Page_Models_Page"
                        value="{{ setting('permalink-Modules_Page_Models_Page', '') }}"
                        placeholder="e.g., /pages or leave empty"
                    >
                    <small class="text-muted">
                        Preview: {{ url(setting('permalink-Modules_Page_Models_Page', '') . '/your-page-slug') }}
                    </small>
                </div>

                {{-- Add more model prefixes as needed --}}
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> Changing permalinks may affect SEO. Update with caution.
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-2"></i>
                    {{ trans('slug::slug.settings.save_settings') }}
                </button>
            </div>
        </form>
    </div>
@endsection
