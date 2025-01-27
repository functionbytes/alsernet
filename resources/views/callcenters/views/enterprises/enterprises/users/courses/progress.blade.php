@php use App\Models\Course\CourseProgress;
 @endphp

@extends('layouts.callcenters')

@section('content')

    @include('distributors.includes.card', ['title' => "Progreso - " . $course->title ])

    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12">
            <!-- Yearly Breakup -->
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="row align-items-center">
                            <h5 class="card-title mb-9 fw-semibold">CLASES </h5>
                            <h4 class="fw-semibold mb-3">{{ count($class) }}</h4>

                            <div id="customers"></div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12">
            <!-- Yearly Breakup -->
            @php

                $total_class = $class;
                $total_count = count($total_class);

                $total_per = 100;
                $read_class = $progress;
                $read_count = count($read_class);

                if($read_count == 0){
                    $progres = 0;
                }else{
                    $progres = ($total_count / $read_count) * $total_per;
                }

            @endphp


            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="row align-items-center">
                            <h5 class="card-title mb-9 fw-semibold">PORCENTAJE</h5>
                            <h4 class="fw-semibold mb-3">{{ round($progres)  }}%</h4>

                            <div id="customers1"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="col-md-12 col-lg-12">
        @php
            $chapters = $class->groupBy('chapter_id');

        @endphp

        <div class="row">
            @foreach ($chapters->sortBy('position') as $key => $chapter)
                <div class="col-md-6 col-lg-12">
                    <div class="card w-100">
                        <div class="card-body">
                            <div class="d-sm-flex d-block align-items-center justify-content-between mb-3">
                                <div class="mb-3 mb-sm-0">
                                    <h5 class="card-title fw-semibold">{{ $chapter->first()->chapter->title }}</h5>
                                    <p class="card-subtitle">Detalle del curso y seguimiento del progreso</p>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle text-nowrap mb-0">
                                    <thead>

                                    </thead>
                                    <tbody class="border-top">

                                    @foreach ($chapter as $key => $class)

                                        @php
                                        $validate = App\Models\Course\CourseProgress::validate($class->id,$order->id,$user->id);
                                        @endphp

                                        <tr>
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="fw-semibold mb-1">{{ $class->title }}</h6>
                                                        <p class="fs-2 mb-0 text-muted">{{ $class->chapter->title }}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                    <span class="badge {{ $validate  ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                                    {{ $validate == 1 ? 'Culminado' : 'Pendiente' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
@endsection



@push('scripts')


    <script src="{{ url('managers/libs/owl.carousel/dist/owl.carousel.min.js') }}" type="text/javascript"></script>
    <script src="{{ url('managers/libs/apexcharts/dist/apexcharts.min.js') }}" type="text/javascript"></script>

    <script>
        $(document).ready(function() {


            // =====================================
            // Customers
            // =====================================
            var customers = {
                chart: {
                    id: "sparkline3",
                    type: "area",
                    fontFamily: "Plus Jakarta Sans', sans-serif",
                    foreColor: "#4784d9",
                    height: 60,
                    sparkline: {
                        enabled: true,
                    },
                    group: "sparklines",
                },
                series: [
                    {
                        name: "Clientes",
                        color: "#000",
                        data: [30, 25, 35, 20, 30, 40],
                    },
                ],
                stroke: {
                    curve: "smooth",
                    width: 2,
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 0,
                        inverseColors: false,
                        opacityFrom: 0.12,
                        opacityTo: 0,
                        stops: [20, 180],
                    },
                },
                markers: {
                    size: 0,
                },
                tooltip: {
                    theme: "dark",
                    fixed: {
                        enabled: true,
                        position: "right",
                    },
                    x: {
                        show: false,
                    },
                },
            };
            new ApexCharts(document.querySelector('#customers'), customers).render();
            new ApexCharts(document.querySelector('#customers1'), customers).render();
        });

    </script>


@endpush
