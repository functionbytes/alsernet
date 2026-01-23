
<div class="card position-relative overflow-hidden">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-12 col-md-9">
                <h5 class="fw-semibold mb-1 text-uppercase">
                    @if(isset($icon))
                        <i class="fa {{ $icon }} me-2"></i>
                    @endif
                    {{ $title }}
                </h5>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Dashboard</a>
                        </li>
                        @if(isset($breadcrumbs))
                            @foreach($breadcrumbs as $breadcrumb)
                                @if(isset($breadcrumb['url']))
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                                    </li>
                                @else
                                    <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['label'] }}</li>
                                @endif
                            @endforeach
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                        @endif
                    </ol>
                </nav>
            </div>
            @if(isset($image))
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="text-center mb-n5">
                        <img src="{{ $image }}" alt="" class="img-fluid mb-n4">
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
