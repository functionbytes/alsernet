<div class="mc-content-header">
    <div class="mc-content-header-inner">
        <div>
            <h1 class="mc-page-title">{{ $title }}</h1>
            @isset($subtitle)
                <p class="mc-page-subtitle">{{ $subtitle }}</p>
            @elseif(isset($description))
                <p class="mc-section-desc">{{ $description }}</p>
            @else
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        @if(isset($breadcrumbs) && is_array($breadcrumbs))
                            @foreach($breadcrumbs as $crumb)
                                @if($loop->last)
                                    <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
                                @else
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ $crumb['url'] ?? '#' }}">{{ $crumb['label'] }}</a>
                                    </li>
                                @endif
                            @endforeach
                        @else
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ url('panel/dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                        @endif
                    </ol>
                </nav>
            @endisset
        </div>

        @isset($actions)
            <div class="mc-banner-actions">
                {!! $actions !!}
            </div>
        @endisset
    </div>
</div>
