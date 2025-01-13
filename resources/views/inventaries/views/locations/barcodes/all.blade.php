@extends('layouts.inventaries')

@section('content')
    <div class="container-fluid">
        <div  class="row">
            @foreach($locations as $location)
                <div class="col-md-6">
                    <div class="barcode">
                        {!! DNS1D::getBarcodeHTML($location->title, 'C39') !!}
                        <p><strong>{{ $location->title }}</strong></p>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
@endsection


@push('scripts')



@endpush
