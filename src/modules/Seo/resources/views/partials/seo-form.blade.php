@php
    $seo = $model->seoMeta ?? null;
@endphp

<div class="seo-form-container">
    {{-- SEO Form Section --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-search me-2"></i>SEO Settings
            </h5>
        </div>
        <div class="card-body">
            {{-- Basic SEO --}}
            <h6 class="text-muted mb-3">Basic SEO</h6>

            <div class="mb-3">
                <label for="seo_title" class="form-label">
                    SEO Title
                    <small class="text-muted">({{ config('Seo.title_limits.seo', 60) }} characters recommended)</small>
                </label>
                <input
                    type="text"
                    class="form-control @error('seo_title') is-invalid @enderror"
                    id="seo_title"
                    name="seo_title"
                    value="{{ old('seo_title', $seo?->title) }}"
                    maxlength="255"
                    placeholder="Leave empty to use default title"
                >
                <div class="form-text">
                    <span id="seo_title_count">0</span> characters
                </div>
                @error('seo_title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="seo_description" class="form-label">
                    Meta Description
                    <small class="text-muted">({{ config('Seo.description_limits.seo', 160) }} characters recommended)</small>
                </label>
                <textarea
                    class="form-control @error('seo_description') is-invalid @enderror"
                    id="seo_description"
                    name="seo_description"
                    rows="3"
                    maxlength="500"
                    placeholder="Brief description for search results"
                >{{ old('seo_description', $seo?->description) }}</textarea>
                <div class="form-text">
                    <span id="seo_description_count">0</span> characters
                </div>
                @error('seo_description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="seo_keywords" class="form-label">
                    Keywords
                    <small class="text-muted">(comma separated)</small>
                </label>
                <input
                    type="text"
                    class="form-control @error('seo_keywords') is-invalid @enderror"
                    id="seo_keywords"
                    name="seo_keywords"
                    value="{{ old('seo_keywords', $seo?->keywords) }}"
                    placeholder="keyword1, keyword2, keyword3"
                >
                @error('seo_keywords')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Advanced Settings --}}
            <div class="accordion mt-4" id="seoAdvancedSettings">
                {{-- Open Graph --}}
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ogSettings">
                            <i class="fab fa-facebook me-2"></i>Open Graph (Facebook/LinkedIn)
                        </button>
                    </h2>
                    <div id="ogSettings" class="accordion-collapse collapse" data-bs-parent="#seoAdvancedSettings">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label for="og_title" class="form-label">
                                    OG Title
                                    <small class="text-muted">({{ config('Seo.title_limits.og', 95) }} characters recommended)</small>
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="og_title"
                                    name="og_title"
                                    value="{{ old('og_title', $seo?->og_title) }}"
                                    maxlength="255"
                                    placeholder="Leave empty to use SEO title"
                                >
                                <div class="form-text">
                                    <span id="og_title_count">0</span> characters
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="og_description" class="form-label">
                                    OG Description
                                    <small class="text-muted">({{ config('Seo.description_limits.og', 200) }} characters recommended)</small>
                                </label>
                                <textarea
                                    class="form-control"
                                    id="og_description"
                                    name="og_description"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Leave empty to use meta description"
                                >{{ old('og_description', $seo?->og_description) }}</textarea>
                                <div class="form-text">
                                    <span id="og_description_count">0</span> characters
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="og_image" class="form-label">
                                    OG Image URL
                                    <small class="text-muted">(1200x630px recommended)</small>
                                </label>
                                <input
                                    type="url"
                                    class="form-control"
                                    id="og_image"
                                    name="og_image"
                                    value="{{ old('og_image', $seo?->og_image) }}"
                                    placeholder="https://example.com/image.jpg"
                                >
                                @if($seo?->og_image)
                                <div class="mt-2">
                                    <img src="{{ $seo->og_image }}" alt="OG Image Preview" class="img-thumbnail" style="max-width: 300px;">
                                </div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="og_type" class="form-label">OG Type</label>
                                <select class="form-select select2" id="og_type" name="og_type">
                                    <option value="website" {{ old('og_type', $seo?->og_type) === 'website' ? 'selected' : '' }}>Website</option>
                                    <option value="article" {{ old('og_type', $seo?->og_type) === 'article' ? 'selected' : '' }}>Article</option>
                                    <option value="product" {{ old('og_type', $seo?->og_type) === 'product' ? 'selected' : '' }}>Product</option>
                                    <option value="profile" {{ old('og_type', $seo?->og_type) === 'profile' ? 'selected' : '' }}>Profile</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Twitter Card --}}
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#twitterSettings">
                            <i class="fab fa-twitter me-2"></i>Twitter Card
                        </button>
                    </h2>
                    <div id="twitterSettings" class="accordion-collapse collapse" data-bs-parent="#seoAdvancedSettings">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label for="twitter_card" class="form-label">Card Type</label>
                                <select class="form-select select2" id="twitter_card" name="twitter_card">
                                    <option value="summary" {{ old('twitter_card', $seo?->twitter_card) === 'summary' ? 'selected' : '' }}>Summary</option>
                                    <option value="summary_large_image" {{ old('twitter_card', $seo?->twitter_card) === 'summary_large_image' ? 'selected' : '' }}>Summary Large Image</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="twitter_title" class="form-label">
                                    Twitter Title
                                    <small class="text-muted">({{ config('Seo.title_limits.twitter', 70) }} characters recommended)</small>
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="twitter_title"
                                    name="twitter_title"
                                    value="{{ old('twitter_title', $seo?->twitter_title) }}"
                                    maxlength="255"
                                    placeholder="Leave empty to use SEO title"
                                >
                                <div class="form-text">
                                    <span id="twitter_title_count">0</span> characters
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="twitter_description" class="form-label">
                                    Twitter Description
                                    <small class="text-muted">({{ config('Seo.description_limits.twitter', 200) }} characters recommended)</small>
                                </label>
                                <textarea
                                    class="form-control"
                                    id="twitter_description"
                                    name="twitter_description"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Leave empty to use meta description"
                                >{{ old('twitter_description', $seo?->twitter_description) }}</textarea>
                                <div class="form-text">
                                    <span id="twitter_description_count">0</span> characters
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="twitter_image" class="form-label">
                                    Twitter Image URL
                                    <small class="text-muted">(1200x675px for large image)</small>
                                </label>
                                <input
                                    type="url"
                                    class="form-control"
                                    id="twitter_image"
                                    name="twitter_image"
                                    value="{{ old('twitter_image', $seo?->twitter_image) }}"
                                    placeholder="https://example.com/image.jpg"
                                >
                                @if($seo?->twitter_image)
                                <div class="mt-2">
                                    <img src="{{ $seo->twitter_image }}" alt="Twitter Image Preview" class="img-thumbnail" style="max-width: 300px;">
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Advanced Settings --}}
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#advancedSettings">
                            <i class="fas fa-cog me-2"></i>Advanced Settings
                        </button>
                    </h2>
                    <div id="advancedSettings" class="accordion-collapse collapse" data-bs-parent="#seoAdvancedSettings">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label for="canonical_url" class="form-label">Canonical URL</label>
                                <input
                                    type="url"
                                    class="form-control"
                                    id="canonical_url"
                                    name="canonical_url"
                                    value="{{ old('canonical_url', $seo?->canonical_url) }}"
                                    placeholder="https://example.com/page"
                                >
                                <div class="form-text">Specify the preferred URL for duplicate content</div>
                            </div>

                            <div class="mb-3">
                                <label for="robots" class="form-label">Robots Directive</label>
                                <select class="form-select select2" id="robots" name="robots">
                                    <option value="index,follow" {{ old('robots', $seo?->robots) === 'index,follow' ? 'selected' : '' }}>Index, Follow (Default)</option>
                                    <option value="noindex,nofollow" {{ old('robots', $seo?->robots) === 'noindex,nofollow' ? 'selected' : '' }}>No Index, No Follow</option>
                                    <option value="noindex,follow" {{ old('robots', $seo?->robots) === 'noindex,follow' ? 'selected' : '' }}>No Index, Follow</option>
                                    <option value="index,nofollow" {{ old('robots', $seo?->robots) === 'index,nofollow' ? 'selected' : '' }}>Index, No Follow</option>
                                </select>
                                <div class="form-text">Control how search engines crawl this page</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SEO Preview Section --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-eye me-2"></i>SEO Preview
            </h5>
        </div>
        <div class="card-body">
            {{-- Google Preview --}}
            <h6 class="text-muted mb-3">Google Search Result</h6>
            <div class="seo-preview-google mb-4">
                <div class="preview-url text-success small" id="preview_google_url">{{ url()->current() }}</div>
                <div class="preview-title text-primary fs-5" id="preview_google_title">{{ $seo?->title ?? $model->title ?? 'Page Title' }}</div>
                <div class="preview-description text-muted" id="preview_google_description">{{ $seo?->description ?? $model->description ?? 'Page description will appear here' }}</div>
            </div>

            {{-- Facebook Preview --}}
            <h6 class="text-muted mb-3">Facebook/LinkedIn Share</h6>
            <div class="seo-preview-facebook mb-4 border rounded p-3">
                <div class="row">
                    @if($seo?->og_image || config('Seo.default_og_image'))
                    <div class="col-md-4">
                        <img src="{{ $seo?->og_image ?? config('Seo.default_og_image') }}" alt="Preview" class="img-fluid rounded" id="preview_facebook_image">
                    </div>
                    <div class="col-md-8">
                    @else
                    <div class="col-md-12">
                    @endif
                        <div class="preview-url text-muted mb-1" id="preview_facebook_url">{{ parse_url(url()->current(), PHP_URL_HOST) }}</div>
                        <div class="preview-title fw-bold" id="preview_facebook_title">{{ $seo?->og_title ?? $seo?->title ?? $model->title ?? 'Page Title' }}</div>
                        <div class="preview-description text-muted" id="preview_facebook_description">{{ $seo?->og_description ?? $seo?->description ?? $model->description ?? 'Page description' }}</div>
                    </div>
                </div>
            </div>

            {{-- Twitter Preview --}}
            <h6 class="text-muted mb-3">Twitter/X Card</h6>
            <div class="seo-preview-twitter border rounded p-3">
                @if($seo?->twitter_image || $seo?->og_image || config('Seo.default_og_image'))
                <img src="{{ $seo?->twitter_image ?? $seo?->og_image ?? config('Seo.default_og_image') }}" alt="Preview" class="img-fluid rounded mb-2" id="preview_twitter_image">
                @endif
                <div class="preview-title fw-bold" id="preview_twitter_title">{{ $seo?->twitter_title ?? $seo?->title ?? $model->title ?? 'Page Title' }}</div>
                <div class="preview-description text-muted mb-1" id="preview_twitter_description">{{ $seo?->twitter_description ?? $seo?->description ?? $model->description ?? 'Page description' }}</div>
                <div class="preview-url text-muted" id="preview_twitter_url">{{ parse_url(url()->current(), PHP_URL_HOST) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Character Counter JavaScript --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counters
    const fields = [
        'seo_title',
        'seo_description',
        'og_title',
        'og_description',
        'twitter_title',
        'twitter_description'
    ];

    fields.forEach(field => {
        const input = document.getElementById(field);
        const counter = document.getElementById(field + '_count');

        if (input && counter) {
            const updateCount = () => {
                const count = input.value.length;
                counter.textContent = count;

                // Add color coding based on recommended limits
                const limits = {
                    'seo_title': {{ config('Seo.title_limits.seo', 60) }},
                    'seo_description': {{ config('Seo.description_limits.seo', 160) }},
                    'og_title': {{ config('Seo.title_limits.og', 95) }},
                    'og_description': {{ config('Seo.description_limits.og', 200) }},
                    'twitter_title': {{ config('Seo.title_limits.twitter', 70) }},
                    'twitter_description': {{ config('Seo.description_limits.twitter', 200) }}
                };

                const limit = limits[field];
                if (limit) {
                    if (count > limit) {
                        counter.classList.add('text-danger');
                        counter.classList.remove('text-warning', 'text-success');
                    } else if (count > limit * 0.9) {
                        counter.classList.add('text-warning');
                        counter.classList.remove('text-danger', 'text-success');
                    } else {
                        counter.classList.add('text-success');
                        counter.classList.remove('text-danger', 'text-warning');
                    }
                }
            };

            updateCount();
            input.addEventListener('input', updateCount);
        }
    });

    // Live preview updates
    const updatePreview = () => {
        const seoTitle = document.getElementById('seo_title').value || '{{ $model->title ?? "Page Title" }}';
        const seoDescription = document.getElementById('seo_description').value || '{{ $model->description ?? "Page description" }}';
        const ogTitle = document.getElementById('og_title')?.value || seoTitle;
        const ogDescription = document.getElementById('og_description')?.value || seoDescription;
        const twitterTitle = document.getElementById('twitter_title')?.value || seoTitle;
        const twitterDescription = document.getElementById('twitter_description')?.value || seoDescription;

        // Update Google preview
        document.getElementById('preview_google_title').textContent = seoTitle + '{{ config("Seo.default_title_suffix") }}';
        document.getElementById('preview_google_description').textContent = seoDescription;

        // Update Facebook preview
        if (document.getElementById('preview_facebook_title')) {
            document.getElementById('preview_facebook_title').textContent = ogTitle;
            document.getElementById('preview_facebook_description').textContent = ogDescription;
        }

        // Update Twitter preview
        if (document.getElementById('preview_twitter_title')) {
            document.getElementById('preview_twitter_title').textContent = twitterTitle;
            document.getElementById('preview_twitter_description').textContent = twitterDescription;
        }
    };

    // Attach live update listeners
    ['seo_title', 'seo_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', updatePreview);
        }
    });
});
</script>
@endpush

@push('styles')
<style>
.seo-preview-google .preview-url {
    font-size: 0.875rem;
}

.seo-preview-google .preview-title {
    font-size: 1.25rem;
    cursor: pointer;
}

.seo-preview-google .preview-title:hover {
    text-decoration: underline;
}

.seo-preview-facebook,
.seo-preview-twitter {
    background-color: #f8f9fa;
}

.seo-preview-facebook img,
.seo-preview-twitter img {
    max-height: 200px;
    object-fit: cover;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0c63e4;
}
</style>
@endpush
