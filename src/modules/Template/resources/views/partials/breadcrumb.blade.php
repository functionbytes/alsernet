@if ($breadcrumb->enabled() && count($crumbs = $breadcrumb->getCrumbs()) > 0)
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            @foreach($crumbs as $index => $crumb)
                @if($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
                @else
                    <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{!! $crumb['label'] !!}</a></li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
