
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-content">

        <div class="tab-content">
            <div  class="note-has-grid row">

                    <div class="col-md-6">
                        <div class="card">
                            <a class="card-body text-center" href="{{ route('warehouse.warehouses.location.manual', [$warehouse->uid ,$location->uid,$section->uid]) }}" >
                                <i class="fa-duotone fa-light fa-scanner-keyboard"></i>
                                <h5 class="fw-semibold fs-5 mb-2">Manual</h5>
                                <p class="mb-3 ">Validar los productos de forma manual.</p>
                            </a>
                        </div>
                    </div>

                <div class="col-md-6">
                    <div class="card">
                        <a class="card-body text-center" href="{{ route('warehouse.warehouses.location.automatic', [$warehouse->uid ,$location->uid,$section->uid]) }}" >
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
