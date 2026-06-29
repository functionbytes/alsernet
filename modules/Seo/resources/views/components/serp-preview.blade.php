@props(['meta'])

{{--
    Live SERP preview component. Renders Google Desktop / Google Mobile /
    Facebook / Twitter cards that update as the user edits the meta form.
    Reads values from #seo-title-input, #seo-description-input, #seo-og-image-input,
    #canonical-url-input — the IDs used by the meta edit view.
--}}

<div class="card mb-3" id="serp-preview-card">
    <div class="card-header p-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Vista previa SERP</h6>
            <div class="btn-group btn-group-sm seo-preview-tabs" role="group" aria-label="Plataforma">
                <button type="button" class="btn btn-outline-secondary active" data-preview="google" title="Google">
                    <i class="fab fa-google"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-preview="mobile" title="Móvil">
                    <i class="fas fa-mobile-screen"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-preview="facebook" title="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-preview="twitter" title="Twitter">
                    <i class="fab fa-x-twitter"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body p-3">
        {{-- Google Desktop --}}
        <div class="preview-pane" data-pane="google">
            <div class="serp-google" style="font-family: arial, sans-serif; max-width: 600px;">
                <div class="d-flex align-items-center mb-1">
                    <img src="https://www.google.com/s2/favicons?domain={{ parse_url($meta->canonical_url ?? config('app.url'), PHP_URL_HOST) }}&sz=16" width="16" height="16" class="me-2" alt="">
                    <div class="small text-muted" id="preview-url-google">{{ $meta->canonical_url ?? url('/') }}</div>
                </div>
                <a href="#" onclick="return false;" class="d-block" style="font-size: 20px; color: #1a0dab; text-decoration: none; line-height: 1.3;" id="preview-title-google">
                    {{ \Illuminate\Support\Str::limit($meta->title ?? 'Sin título', 60) }}
                </a>
                <div class="small text-muted mt-1" id="preview-desc-google" style="color: #4d5156; font-size: 14px; line-height: 1.4;">
                    {{ \Illuminate\Support\Str::limit($meta->description ?? 'Sin descripción', 160) }}
                </div>
            </div>
        </div>

        {{-- Mobile --}}
        <div class="preview-pane d-none" data-pane="mobile">
            <div class="serp-mobile" style="font-family: arial, sans-serif; max-width: 380px; border: 1px solid #dfe1e5; border-radius: 8px; padding: 12px;">
                <div class="d-flex align-items-center mb-1">
                    <img src="https://www.google.com/s2/favicons?domain={{ parse_url($meta->canonical_url ?? config('app.url'), PHP_URL_HOST) }}&sz=16" width="16" height="16" class="me-2" alt="">
                    <div class="small text-muted text-truncate" id="preview-url-mobile">{{ $meta->canonical_url ?? url('/') }}</div>
                </div>
                <a href="#" onclick="return false;" class="d-block" style="font-size: 18px; color: #1a0dab; text-decoration: none; line-height: 1.3;" id="preview-title-mobile">
                    {{ \Illuminate\Support\Str::limit($meta->title ?? 'Sin título', 60) }}
                </a>
                <div class="small mt-1" id="preview-desc-mobile" style="color: #4d5156; font-size: 13px; line-height: 1.4;">
                    {{ \Illuminate\Support\Str::limit($meta->description ?? 'Sin descripción', 120) }}
                </div>
            </div>
        </div>

        {{-- Facebook --}}
        <div class="preview-pane d-none" data-pane="facebook">
            <div class="card" style="max-width: 500px;">
                @if($meta->og_image)
                    <img src="{{ $meta->og_image }}" alt="" class="card-img-top" id="preview-fb-image" style="height: 260px; object-fit: cover;">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center" id="preview-fb-image-placeholder" style="height: 260px;">
                        <span class="text-muted">Sin imagen OG</span>
                    </div>
                @endif
                <div class="card-body p-3" style="background: #f0f2f5;">
                    <div class="small text-uppercase text-muted" id="preview-fb-url">{{ parse_url($meta->canonical_url ?? config('app.url'), PHP_URL_HOST) }}</div>
                    <div class="fw-semibold mt-1" id="preview-fb-title" style="font-size: 16px;">
                        {{ \Illuminate\Support\Str::limit($meta->og_title ?? $meta->title ?? 'Sin título', 95) }}
                    </div>
                    <div class="small text-muted mt-1" id="preview-fb-desc">
                        {{ \Illuminate\Support\Str::limit($meta->og_description ?? $meta->description ?? '', 200) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Twitter --}}
        <div class="preview-pane d-none" data-pane="twitter">
            <div class="card" style="max-width: 500px; border: 1px solid #cfd9de; border-radius: 16px; overflow: hidden;">
                @if($meta->twitter_image ?? $meta->og_image)
                    <img src="{{ $meta->twitter_image ?? $meta->og_image }}" alt="" id="preview-tw-image" style="width: 100%; height: 260px; object-fit: cover;">
                @endif
                <div class="p-3">
                    <div class="small text-muted" id="preview-tw-url">{{ parse_url($meta->canonical_url ?? config('app.url'), PHP_URL_HOST) }}</div>
                    <div class="fw-semibold mt-1" id="preview-tw-title">
                        {{ \Illuminate\Support\Str::limit($meta->twitter_title ?? $meta->title ?? 'Sin título', 70) }}
                    </div>
                    <div class="small text-muted mt-1" id="preview-tw-desc">
                        {{ \Illuminate\Support\Str::limit($meta->twitter_description ?? $meta->description ?? '', 200) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).ready(function () {
    // Tab switch
    $('[data-preview]').on('click', function () {
        $('[data-preview]').removeClass('active');
        $(this).addClass('active');
        $('.preview-pane').addClass('d-none');
        $('.preview-pane[data-pane="' + $(this).data('preview') + '"]').removeClass('d-none');
    });

    // Live sync from form inputs to preview
    function truncate(str, n) {
        if (! str) return '';
        return str.length > n ? str.substring(0, n - 1).trim() + '…' : str;
    }

    function refresh() {
        const title = $('input[name="title"]').val() || 'Sin título';
        const desc = $('textarea[name="description"]').val() || 'Sin descripción';
        const ogTitle = $('input[name="og_title"]').val() || title;
        const ogDesc = $('textarea[name="og_description"]').val() || desc;
        const ogImage = $('input[name="og_image"]').val();
        const twTitle = $('input[name="twitter_title"]').val() || title;
        const twDesc = $('textarea[name="twitter_description"]').val() || desc;
        const twImage = $('input[name="twitter_image"]').val() || ogImage;

        $('#preview-title-google, #preview-title-mobile').text(truncate(title, 60));
        $('#preview-desc-google').text(truncate(desc, 160));
        $('#preview-desc-mobile').text(truncate(desc, 120));

        $('#preview-fb-title').text(truncate(ogTitle, 95));
        $('#preview-fb-desc').text(truncate(ogDesc, 200));
        $('#preview-tw-title').text(truncate(twTitle, 70));
        $('#preview-tw-desc').text(truncate(twDesc, 200));

        if (ogImage) {
            $('#preview-fb-image').attr('src', ogImage).removeClass('d-none');
            $('#preview-fb-image-placeholder').addClass('d-none');
        }
        if (twImage) {
            $('#preview-tw-image').attr('src', twImage).removeClass('d-none');
        }
    }

    $(document).on('input change', 'input[name="title"], textarea[name="description"], input[name="og_title"], textarea[name="og_description"], input[name="og_image"], input[name="twitter_title"], textarea[name="twitter_description"], input[name="twitter_image"]', refresh);
});
</script>
@endpush
@endonce
