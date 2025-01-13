@extends('layouts.inventaries')

@section('content')
    <div class="container-fluid">
        <div  class="row">
            @foreach($products as $product)
                <div class="col-md-6">
                    <div class="barcode">
                        <p><strong>{{ $product->reference }}</strong></p>
                        {!! DNS1D::getBarcodeHTML($product->barcode, 'C39') !!}
                        <p>{{ $product->barcode }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')

@endpush
