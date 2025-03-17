@php use App\Models\Course\CourseProgress;
@endphp

@extends('layouts.callcenters')

@section('content')

    @include('distributors.includes.card', ['title' => 'Resultados - ' . $course->title ])

    <div class="row">
        <div class="col-lg-4 col-md-4 col-sm-12">
            <!-- Yearly Breakup -->
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="row align-items-center">
                        <h5 class="card-title mb-3 fw-semibold">Preguntas incorrectas</h5>
                        <h4 class="fw-semibold mb-3">{{ $wrongs }}</h4>

                        <div id="customers"></div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-12">
            <!-- Yearly Breakup -->

            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="row align-items-center">
                        <h5 class="card-title mb-3 fw-semibold">Preguntas correctas</h5>
                        <h4 class="fw-semibold mb-3">{{ $corrects  }}</h4>

                        <div id="customers1"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-12">

            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="row align-items-center">
                        <h5 class="card-title mb-3 fw-semibold">Calificación</h5>
                        <h4 class="fw-semibold mb-3">{{ $exam->score }}</h4>

                        <div id="customers2"></div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 col-lg-12">

        <div class="row">
            <div class="card card-body">
                <div class="table-responsive">
                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                        <tr>
                            <th>Pregunta</th>
                            <th>R. usuario</th>
                            <th>R. correcta</th>
                            <th>Estado</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach ($answers as $key => $answer)
                            <tr class="search-items">

                                <td>
                                    <span class="usr-email-addr" data-email="{{ ucfirst($answer->question->question) }}">{{ Str::words($answer->question->question, 12, '...')  }}</span>
                                </td>
                                <td>
                                    <span class="usr-email-addr" data-email="{{ $answer->user_answer }}">{{ $answer->user_answer }}</span>
                                </td>
                                <td>
                                    <span class="usr-email-addr" data-email="{{ $answer->answer }}">{{ $answer->answer }}</span>
                                </td>
                                <td>
                                  <span class="badge {{ $answer->approved == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">

                                       {{ $answer->approved == 1 ? 'Correcta' : 'Incorrecta' }}
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
            new ApexCharts(document.querySelector('#customers2'), customers).render();
        });

    </script>


@endpush
