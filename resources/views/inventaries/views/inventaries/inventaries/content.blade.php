
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid inventaries-content">

        <div class="tab-content">
            <div  class="note-has-grid row">

                <div class="col-md-6">
                    <div class="card">
                        <a class="card-body text-center" href="{{ route('inventarie.inventarie.arrange', $inventarie->uid ) }}" >
                            <i class="fa-duotone fa-solid fa-rectangle-barcode"></i>
                            <h5 class="fw-semibold fs-5 mb-2">Gestionar</h5>
                            <p class="mb-3 ">Gestionar la validacion de las ubicaciones.</p>
                        </a>
                    </div>
                </div>

{{--                <div class="col-md-6">--}}
{{--                    <div class="card">--}}
{{--                        <a class="card-body text-center" href="{{ route('inventarie.inventarie.close', $inventarie->uid ) }}" >--}}
{{--                            <i class="fa-duotone fa-regular fa-door-closed"></i>--}}
{{--                            <h5 class="fw-semibold fs-5 mb-2">Cerrar</h5>--}}
{{--                            <p class="mb-3 ">Cerrar el inventrario para el cierre.</p>--}}
{{--                        </a>--}}
{{--                    </div>--}}
{{--                </div>--}}


            </div>
        </div>
    </div>

@endsection





@push('scripts')


@endpush
