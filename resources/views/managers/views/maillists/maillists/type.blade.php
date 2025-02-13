@extends('layouts.managers')

@section('content')


    @include('managers.includes.card', ['title' => 'Tipo de Campaña'. $campaign->type])


    <div class="row my-sm-5 my-4 select-types">


        @foreach ($types as $key => $type)

            <div class="col-xl-6 col-sm-6 mb-4 select-types-item">
                <div class="card p-7 mb-0 rounded-3 ">

                    <a href="{{route('manager.campaigns.create', ["type" => $type['key']])}}">
                        <div class="types-icon">
                            @if ($type['key'] == 'regular')
                                <img width="40px" class="icon-img d-inline-block me-4" src="{{ url('images/icons/regular.svg') }}" />
                            @elseif ($type['key'] == 'plain-text')
                                <img width="40px" class="icon-img d-inline-block me-4" src="{{ url('images/icons/plain.svg') }}" />
                            @endif
                        </div>
                        <div class="types-content">
                            <h3 class="fs-6 fw-bolder mb-0">{{ $type['title'] }}</h3>
                            <p class=" mt-3 mb-0 pb-sm-7 pb-3  ">
                                {{  $type['description'] }}
                            </p>
                            <a href="{{route('manager.campaigns.create', ["type" => $type['key']])}}" class="btn btn-primary mt-2 border-top">Seleccionar</a>
                        </div>
                    </a>
                </div>
            </div>

        @endforeach


    </div>
@endsection

@push('scripts')

@endpush
