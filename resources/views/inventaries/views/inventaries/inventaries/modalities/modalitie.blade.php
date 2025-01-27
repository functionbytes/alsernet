
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-content">

        <div class="tab-content">
            <div  class="note-has-grid row">

                @if(!$shop->generate_location)
                    <div class="col-md-6">
                        <div class="card">
                            <a class="card-body text-center" href="{{ route('inventarie.inventarie.location.manual', $location->uid ) }}" >
                                <i class="fa-duotone fa-light fa-scanner-keyboard"></i>
                                <h5 class="fw-semibold fs-5 mb-2">Manual</h5>
                                <p class="mb-3 ">Validar los productos de forma manual.</p>
                            </a>
                        </div>
                    </div>
                @endif

                <div class="col-md-6">
                    <div class="card">
                        <a class="card-body text-center" href="{{ route('inventarie.inventarie.location.automatic', $location->uid ) }}" >
                            <i class="fa-duotone fa-light fa-scanner-gun"></i>
                            <h5 class="fw-semibold fs-5 mb-2">Automatico</h5>
                            <p class="mb-3 ">Validar los productos de forma automatica.</p>
                        </a>
                    </div>
                </div>


            </div>
        </div>
    </div>

@endsection





@push('scripts')


@endpush
