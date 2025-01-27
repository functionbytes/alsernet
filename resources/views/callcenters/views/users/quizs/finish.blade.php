@extends('layouts.callcenters')

@section('title',$course->title)

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
                                    TU PUNTUACIÓN DEL QUIZ
                                </span>
                                <h2 class="fs-9 fw-semibold mb-1">{{ Str::upper($lesson->title) }}</h2>


                                <div class="card-body text-center">


                                    @if ($topic->quiz_again >= 1)
                                        @if ($score >= 80)

                                            <img src="/customers/images/examing/success.svg" alt="" class="img-fluid mb-4" width="150">
                                            <h5 class="fw-semibold fs-5 mb-2">
                                                Has superado el quiz con
                                                {{ $correct > 0 ? $correct : 0 }}  de  {{ $count > 0 ? $count : 0 }} preguntas
                                            </h5>
                                            <p class="mb-5 px-xl-5">Felicitaciones, toque para continuar con la siguiente tarea.</p>
                                            <div class="mt-5">

                                                <form action="{{ route('customer.quiz.realized') }}" method="POST">
                                                    {{ csrf_field() }}

                                                    <input type="hidden" name="course" value="{{ $course->id }}">
                                                    <input type="hidden" name="lesson" value="{{ $lesson->id }}">
                                                    <input type="hidden" name="user" value="{{ $user->id }}">

                                                    <button type="submit" class="btn btn-light-primary text-primary w-100 mt-3"
                                                        href="{{ route('customer.quiz.tryagain', $quiz->id) }}">Siguente</button>


                                                </form>

                                            </div>
                                        @elseif($quiz->score < 80)

                                            <img src="/customers/images/examing/failed.svg" alt="" class="img-fluid mb-4" width="150">
                                            <h5 class="fw-semibold fs-5 mb-2">
                                                Has perdido el quiz con
                                                {{ $wrong > 0 ? $wrong : 0 }}  de  {{ $count > 0 ? $count : 0 }} preguntas
                                        </h5>
                                            <p class="mb-5 px-xl-5">Toque para volver a contestar el quiz.</p>
                                            <div class="mt-5">
                                            <a class="btn btn-light-primary text-primary w-100  mt-3" href="{{ route('customer.quiz.tryagain', $quiz->id) }}">Reintentar</a>
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
