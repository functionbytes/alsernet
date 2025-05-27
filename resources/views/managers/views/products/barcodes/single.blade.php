@extends('layouts.inventaries')

@section('content')
    <div class="container-fluid">
        <div  class="row">
            <div class="col-md-4">
                <div class="barcode">
                    <p><strong>{{ $product->name }}</strong></p>
                    {!! DNS1D::getBarcodeHTML($product->barcode, 'C39') !!}
                    <p>{{ $product->barcode }}</p>
                    <p>{{ $product->reference }}</p>
                </div>
            </div>
        </div>

    </div>
@endsection



@push('scripts')



@endpush
