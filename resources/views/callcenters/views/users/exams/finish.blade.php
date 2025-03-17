@extends('layouts.callcenters')

@section('title', $course->title )

@section('head')
    @php
        $url = URL::current();
    @endphp
    <meta name="title" content="{{ $course->title }}">
    <meta name="description" content="{{ $course->short_detail }} ">
    <meta property="og:title" content="{{ $course->title }} ">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:description" content="{{ $course->short_detail }}">
    <meta property="og:image" content="{{ asset('images/course/' . $course->preview_image) }}">
    <meta itemprop="image" content="{{ asset('images/course/' . $course->preview_image) }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('images/course/' . $course->preview_image) }}">
    <meta property="twitter:title" content="{{ $course->title }} ">
    <meta property="twitter:description" content="{{ $course->short_detail }}">
    <meta name="twitter:site" content="{{ url()->full() }}" />
    <link rel="canonical" href="{{ url()->full() }}" />
    <meta name="robots" content="all">
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="card rounded-2 overflow-hidden">
                <div class="position-relative">

                    <div class="row justify-content-center" >
                        <div class="col-sm-12 col-md-10">
                            <div class="card-body p-4 text-center">
                                <span class="badge text-bg-light fs-2 rounded-4 py-1 px-2 lh-sm  mt-3">
                                     TU PUNTUACIÓN DEL EXAMEN
                                </span>

                                <h2 class="fs-9 fw-semibold mb-1">{{ Str::upper($course->title) }}</h2>

                                <div class="card-body text-center">
                                    @if ($topic->quiz_again >= 1)
                                        @if ($score >= 80)
                                            <img src="/customers/images/examing/exam.svg" alt="" class="img-fluid mb-4" width="150">
                                            <h5 class="fw-semibold fs-5 mb-2">
                                                Has superado el examen con
                                                {{ $correct > 0 ? $correct : 0 }}  de  {{ $count > 0 ? $count : 0 }} preguntas
                                            </h5>
                                            <p class="mb-5 px-xl-5">Felicitaciones, toque para generar el certificado.</p>
                                            <div class="mt-5">
                                                <a class="btn btn-light-primary text-primary  w-100 mt-3" href="{{ route('customer.certificate.download', $certificate->uid) }}">Certificado</a>
                                                <a class="btn btn-light-primary text-primary  w-100 mt-3" href="{{ route('customer.dashboard') }}">Volver</a>
                                            </div>

                                        @elseif($score < 80)

                                            <img src="/customers/images/examing/failed.svg" alt="" class="img-fluid mb-4" width="150">
                                            <h5 class="fw-semibold fs-5 mb-2">
                                                Has perdido el examen con
                                                {{ $wrong > 0 ? $wrong : 0 }}  de  {{ $count > 0 ? $count : 0 }} preguntas
                                            </h5>
                                            <p class="mb-5 px-xl-5">Aca tienes el detalle de las preeguntas del examen.</p>


                                            <div class="mt-2">
                                                <div class="row justify-content-center" >
                                                    <div class="col-sm-12 col-md-10 ">
                                                        @foreach ($answers as $answer)
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class="flex-grow-1">
                                                                    <h6 class="text-sm-start fw-normal text-black mb-0 pe-sm-1">{{ $answer->question->question }}</h6>
                                                                </div>
                                                                <div class="ms-auto justify-content-start align-items">
                                                                    @if ($answer->approved == 1)
                                                                        <i class="fa-solid fa-circle-check fs-4 "></i>
                                                                    @else
                                                                        <i class="fa-solid fa-circle-xmark fs-4 "></i>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-5">
                                                <a class="btn btn-light-primary text-primary  w-100 mt-3" href="{{ route('customer.exam.tryagain', $exam->id) }}">Reintentar</a>
                                            </div>


                                        @endif
                                    @endif


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
@endsection
