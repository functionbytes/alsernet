@php use Carbon\Carbon; @endphp

@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')
    <div class="container-fluid note-has-grid">

        @include('inventaries.includes.card', ['title' => 'Inventarios'])


        <div class="tab-content">
            <div id="note-full-container" class="note-has-grid row">

                @foreach($inventaries as $inventarie)
                    <div class="col-xl-4 col-md-6 col-sm-12 single-note-item order-all ">
                        <div class="card p-4" >
                            <a class="card-body p-0" href="{{ route('inventarie.inventarie.content', $inventarie->uid ) }}">
                                <span class="bg-light-primary text-primary badge mb-3 fw-semibold">{{ date('Y', strtotime($inventarie->closet_at)) }}</span>
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="fw-semibold mb-0 text-black">
                                            {{ $inventarie->shop->title }}
                                    </h4>
                                </div>

                                <div class="col-12">
                                    <div class="progress bg-light">
                                        <div class="progress-bar bg-info" role="progressbar"
                                             style="width: {{ $inventarie->complete == 1 ? 100 : 0 }}%; height: 6px;" aria-valuenow="25"
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between mt-2 pb-3 ">
                                    <div class="text-start">
                                        <h6 class="mb-0 fw-semibold">{{ $inventarie->complete == 1 ? 100 : 0 }}%</h6>
                                    </div>
                                    @if ($inventarie->complete == 1 )
                                        <div class="text-end">
                                            <span class="fs-3">Fecha de cierre</span>
                                            <h6 class="mb-0 fw-semibold">{{ $inventarie->close_at }}</h6>
                                        </div>
                                    @endif
                                </div>

                                @if ($inventarie->complete == 1 )
                                    <p>
                                        <a class="btn btn-light-primary text-primary w-100 mt-1"
                                       href="{{ route('inventarie.inventarie.report', $inventarie->uid) }}"
                                       target="_blank"> Reporte </a>
                                    </p>
                                @endif
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection





@push('scripts')

    <script src="{{ url('managers/js/apps/notes.js') }}" type="text/javascript"></script>

@endpush
