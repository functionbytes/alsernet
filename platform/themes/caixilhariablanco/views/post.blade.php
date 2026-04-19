@php
    $layout = 'blog-right-sidebar';
    if (function_exists('get_blog_single_layouts')) {
        $layouts = get_blog_single_layouts();
        if (isset($postLayout) && array_key_exists($postLayout, $layouts)) {
            $layout = $postLayout;
        }
    }
@endphp

@extends('template::layouts.' . $layout)

@section('title', $post->seo_title ?? $post->title)

@section('seo_head')
    @php
        $postTitle = $post->seo_title ?? $post->title ?? config('app.name');
        $postDesc  = $post->seo_description ?? $post->description ?? '';
        $postImage = $post->image ? \RvMedia::getImageUrl($post->image) : null;
        $postUrl   = $post->url;
    @endphp
    <link rel="canonical" href="{{ $postUrl }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $postTitle }}">
    <meta property="og:description" content="{{ $postDesc }}">
    <meta property="og:url" content="{{ $postUrl }}">
    @if($postImage)
        <meta property="og:image" content="{{ $postImage }}">
        <link rel="preload" as="image" href="{{ $postImage }}">
    @endif
    <meta property="og:site_name" content="{{ theme_option('site_title', config('app.name')) }}">
    <meta name="twitter:card" content="{{ $postImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $postTitle }}">
    <meta name="twitter:description" content="{{ $postDesc }}">
    @if($postImage)
        <meta name="twitter:image" content="{{ $postImage }}">
    @endif
    {!! $post->renderCompleteSchemaScript() !!}
@endsection

@push('schema_org')
@php
    $schemaData = [
        '@@context' => 'https://schema.org',
        '@@type' => 'Article',
        'headline' => $post->seo_title ?? $post->title ?? '',
        'description' => $post->seo_description ?? $post->description ?? '',
        'datePublished' => $post->published_at ? $post->published_at->toIso8601String() : $post->created_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'url' => $post->url,
        'author' => ['@@type' => 'Person', 'name' => $post->user?->name ?? config('app.name')],
    ];
    if ($post->image) {
        $schemaData['image'] = RvMedia::getImageUrl($post->image);
    }
    $schemaJson = str_replace('@@', '@', json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
@endphp
<script type="application/ld+json">{!! $schemaJson !!}</script>
@endpush

@section('content')
    {{-- Breadcrumb --}}
    <div class="blog-page-header mb-30">
        <h4 class="mb-5">{{ \Illuminate\Support\Str::limit($post->title, 60) }}</h4>
        <p class="text-muted mb-0" style="font-size:13px">
            <a href="{{ url('/') }}" class="text-brand">{{ __('Home') }}</a>
            <i class="fa-solid fa-angle-right mx-1"></i>
            <a href="{{ route('blog.public.index') }}" class="text-brand">{{ __('Blog') }}</a>
            <i class="fa-solid fa-angle-right mx-1"></i>
            <span>{{ \Illuminate\Support\Str::limit($post->title, 40) }}</span>
        </p>
    </div>

    {{-- Wrapper que activa los selectores CSS del template --}}
    <div class="blog-leftside-section-area">
        <div class="blog-leftside-area heading2 blog-singleside">

            {{-- Imagen destacada --}}
            @if ($post->image)
                <div class="blog-left-heading heading2 mb-30">
                    <div class="img1">
                        <img alt="" src="{{ RvMedia::getImageUrl($post->image, null, false, RvMedia::getDefaultImage()) }}"
                             alt="{{ $post->title }}" class="w-100" style="border-radius:8px">
                    </div>
                </div>
                <div class="space48"></div>
            @endif

            {{-- Meta: autor, fecha, vistas --}}
            <div class="left-heading2 heading2 mb-20">
                <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0" style="font-size:13px; color:#666">
                    @if ($post->user)
                        <li><i class="fa-solid fa-user me-1" style="color:var(--color-brand)"></i> {{ $post->user->name }}</li>
                    @endif
                    <li><i class="fa-solid fa-calendar-days me-1" style="color:var(--color-brand)"></i> {{ $post->created_at->translatedFormat('d M, Y') }}</li>
                    @if (function_exists('get_time_to_read'))
                        <li><i class="fa-regular fa-clock me-1" style="color:var(--color-brand)"></i> {{ __(':count mins read', ['count' => get_time_to_read($post)]) }}</li>
                    @endif
                    <li><i class="fa-regular fa-eye me-1" style="color:var(--color-brand)"></i> {{ number_format($post->views) }}</li>
                </ul>
            </div>
            <div class="space16"></div>

            {{-- Contenido --}}
            <div class="left-heading2 heading2">
                <div class="ck-content">{!! \Modules\Shortcode\Facades\Shortcode::compile(BaseHelper::clean($post->content)) !!}</div>
            </div>
            <div class="space48"></div>

            {{-- Tags y compartir --}}
            <div class="tags-share-area">
                <div class="tags">
                    @if (!$post->tags->isEmpty())
                        <h4>{{ __('Tags') }}:</h4>
                        <ul>
                            @foreach ($post->tags as $tag)
                                <li><a href="{{ $tag->url }}">{{ $tag->name }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="share">
                    <h4>{{ __('Share this') }}:</h4>
                    <ul>
                        <li>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($post->url) }}" target="_blank">
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>
                        </li>
                        <li>
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url) }}&text={{ strip_tags($post->description) }}" target="_blank">
                                <i class="fa-brands fa-x-twitter"></i>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode($post->url) }}" target="_blank">
                                <i class="fa-brands fa-linkedin-in"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Comentarios --}}
            @if ($commentsEnabled)
                <div class="space48"></div>

                <div class="heading2" id="comments-section">
                    @if ($comments->count())
                        <h3>{{ __('Comments') }} ({{ $comments->count() }})</h3>
                        <div class="space16"></div>

                        @foreach ($comments as $comment)
                            <div class="comments-boxarea">
                                <div class="comment-auhtor-box">
                                    <div class="all-content">
                                        <div class="img1">
                                            <img alt="" src="{{ $comment->avatar_url }}" alt="{{ $comment->author_name }}" loading="lazy">
                                        </div>
                                        <div class="content-area">
                                            <a href="#">{{ $comment->author_name }}</a>
                                            <a href="#" class="date">{{ $comment->created_at->translatedFormat('d M, Y') }}</a>
                                        </div>
                                    </div>
                                    <div class="reply">
                                        <a href="#comment-form" class="reply-btn" data-id="{{ $comment->id }}" data-name="{{ $comment->author_name }}">
                                            <i class="fa-solid fa-reply me-1"></i>{{ __('Reply') }}
                                        </a>
                                    </div>
                                </div>
                                <div class="space16"></div>
                                <p>{{ $comment->content }}</p>
                            </div>

                            @foreach ($comment->replies as $reply)
                                <div class="space16"></div>
                                <div class="comments-boxarea boxarea2">
                                    <div class="comment-auhtor-box">
                                        <div class="all-content">
                                            <div class="img1">
                                                <img alt="" src="{{ $reply->avatar_url }}" alt="{{ $reply->author_name }}" loading="lazy">
                                            </div>
                                            <div class="content-area">
                                                <a href="#">{{ $reply->author_name }}</a>
                                                <a href="#" class="date">{{ $reply->created_at->translatedFormat('d M, Y') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space16"></div>
                                    <p>{{ $reply->content }}</p>
                                </div>
                            @endforeach

                            <div class="space32"></div>
                        @endforeach
                    @endif

                    <div class="contact-submit-boxarea" id="comment-form">
                        <h4>{{ __('Leave a Reply') }}</h4>
                        <div id="comment-alert" class="mb-3" style="display:none"></div>
                        <p id="reply-notice" class="text-muted mb-3" style="display:none">
                            {{ __('Replying to') }}: <strong id="reply-to-name"></strong>
                            <a href="#" id="cancel-reply" class="ms-2 text-danger" style="font-size:12px">{{ __('Cancel') }}</a>
                        </p>
                        <form id="comment-submit-form" novalidate>
                            @csrf
                            <input type="hidden" name="parent_id" id="parent_id" value="">
                            <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="input-area">
                                        <input type="text" name="author_name" id="author_name"
                                               placeholder="{{ __('Your name') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-area">
                                        <input type="email" name="author_email" id="author_email"
                                               placeholder="{{ __('Your email') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-area">
                                        <textarea name="content" id="comment_content"
                                                  placeholder="{{ __('Your comment...') }}"
                                                  cols="30" rows="6" required></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-area1">
                                        <button type="submit" class="header-btn1" id="comment-submit-btn">
                                            {{ __('Submit comment') }} <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            @endif

        </div>{{-- /.blog-leftside-area --}}
    </div>{{-- /.blog-leftside-section-area --}}

    {{-- Posts relacionados --}}
    @php
        $relatedPosts = $relatedPosts ?? (function_exists('get_related_posts') ? get_related_posts($post->id, 2) : collect());
    @endphp
    @if ($relatedPosts->count())
        <div class="space48"></div>
        <div class="blog1-section-area">
            <h4 class="mb-20">{{ __('Related Articles') }}</h4>
            <div class="row">
                @foreach ($relatedPosts as $relatedItem)
                    @php $relatedCategory = $relatedItem->categories->first(); @endphp
                    <div class="col-lg-6 col-md-6">
                        <div class="blog-auhtor-boxarea">
                            <div class="img1">
                                <a href="{{ $relatedItem->url }}">
                                    <img alt="" src="{{ RvMedia::getImageUrl($relatedItem->image, 'medium', false, RvMedia::getDefaultImage()) }}"
                                         alt="{{ $relatedItem->title }}" loading="lazy">
                                </a>
                            </div>
                            <div class="blog-position">
                                <a href="{{ $relatedItem->url }}" class="heading">{{ $relatedItem->title }}</a>
                                <div class="blog-content-area">
                                    <ul>
                                        <li>
                                            <a href="#"><i class="fa-solid fa-calendar-days"></i> {{ $relatedItem->created_at->translatedFormat('d M, Y') }}</a>
                                        </li>
                                        @if ($relatedCategory)
                                            <li>
                                                <a href="{{ $relatedCategory->url }}"><i class="fa-solid fa-tag"></i> {{ $relatedCategory->name }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                    <a href="{{ $relatedItem->url }}" class="learnmore">{{ __('Read more') }} <i class="fa-solid fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

@endsection

@push('scripts')
<script>
$(function () {
    var commentForm = document.getElementById('comment-submit-form');
    if (!commentForm) return;
    var _form    = $(commentForm);
    var _btn     = $('#comment-submit-btn');
    var postUrl  = @json(route('blog.public.comments.store', $post->slug));
    var pendingMsg = @json(__('Your comment is pending moderation.'));
    var errorMsg   = @json(__('An error occurred. Please try again.'));

    $(document).on('click', '.reply-btn', function (e) {
        e.preventDefault();
        var id   = $(this).data('id');
        var name = $(this).data('name');
        $('#parent_id').val(id);
        $('#reply-to-name').text(name);
        $('#reply-notice').show();
        $('html, body').animate({ scrollTop: $('#comment-form').offset().top - 80 }, 400);
    });

    $('#cancel-reply').on('click', function (e) {
        e.preventDefault();
        $('#parent_id').val('');
        $('#reply-notice').hide();
    });

    function showAlert(type, msg) {
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        $('#comment-alert')
            .html('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                '<i class="fas ' + icon + ' me-2"></i>' + msg +
                '<button aria-label="Cerrar" type="button" class="btn-close" data-bs-dismiss="alert"></button></div>')
            .show();
    }

    _form.on('submit', function (e) {
        e.preventDefault();
        _btn.prop('disabled', true);
        $('#comment-alert').hide();

        $.ajax({
            url: postUrl,
            method: 'POST',
            data: _form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                showAlert('success', res.message || pendingMsg);
                _form[0].reset();
                $('#parent_id').val('');
                $('#reply-notice').hide();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var first  = Object.values(errors)[0];
                    showAlert('danger', Array.isArray(first) ? first[0] : first);
                } else {
                    showAlert('danger', xhr.responseJSON?.message || errorMsg);
                }
            },
            complete: function () {
                _btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
