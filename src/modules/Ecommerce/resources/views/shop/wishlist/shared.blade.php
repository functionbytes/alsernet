@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Lista de deseos de '.$customer->name)

@section('content')
<div class="container my-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold mb-2">Lista de deseos de {{ $customer->name }}</h2>
        <p class="text-muted">{{ $items->count() }} {{ $items->count() === 1 ? 'producto guardado' : 'productos guardados' }}</p>
    </div>

    @if($items->count() > 0)
        <div class="row">
            @foreach($items as $item)
                @if($item->product)
                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                    @include('ecommerce::shop.partials._product-card', ['product' => $item->product])
                </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="alert alert-info text-center">
            <i class="fas fa-heart fa-2x mb-2 opacity-50"></i>
            <p class="mb-0">Esta lista de deseos está vacía.</p>
        </div>
    @endif
</div>
@endsection
