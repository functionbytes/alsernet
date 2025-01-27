@extends('layouts.inventaries')

@section('content')
    <div class="container-fluid">
        <div  class="row">
            <div class="col-md-4">
                <div class="barcode">
                    <p><strong>{{ $location->name }}</strong></p>
                    {!! DNS1D::getBarcodeHTML($location->barcode, 'C39') !!}
                    <p>{{ $location->barcode }}</p>
                    <p>{{ $location->reference }}</p>
                </div>
            </div>
        </div>

    </div>
@endsection



@push('scripts')



@endpush
