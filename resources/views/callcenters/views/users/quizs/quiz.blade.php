@extends('layouts.callcenters')
@section('title', "$course->title")
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
                    <div class="card-body p-4 justify-content-center row">
                    <div class="col-md-10 col-sm-12 text-center">
                        <span class="badge text-bg-light fs-2 rounded-4 py-1 px-2 lh-sm  mt-3">
                             QUIZ
                        </span>
                    <h2 class="fs-9 fw-semibold mb-4">{{ Str::upper($lessoning->title) }}</h2>
                    </div>
                </div>
                <div class="card-body border-top p-4">
                    <div class="lesion-content-wrapper rbt-article-content-wrapper">

                        <div class="content">

                            <div id="question_block" class="question-block">

                                <input type="hidden" id="type" name="type" value="{{ $topic->type }}">

                                @php

                                    $users = $answers;
                                    $que_count = $questions->count();
                                    $count = 1;

                                @endphp


                                @if ($topic->type == 0)
                                    <div class="question" id="question-div">
                                        <form action="{{ route('customer.quiz.store', $topic->id) }}" method="POST"
                                              id="question-form">

                                            {{ csrf_field() }}

                                            @php
                                                $count = 1;
                                            @endphp

                                            <div class="row justify-content-center">

                                                <input type="hidden" id="quiz" name="quiz" value="{{ $quiz->id }}">

                                                <input type="hidden" id="question_id[{{ $count }}]"
                                                       name="question_id[{{ $count }}]"
                                                       value="{{ $questions[0]['id'] }}">
                                                <input type="hidden" id="canswer[{{ $count }}]"
                                                       name="canswer[{{ $count }}]"
                                                       value="{{ $questions[0]['answer'] }}">

                                                <div id="more_quiz0">
                                                    <div class="jumbotron" id="quiz1">
                                                        <div class="middle-answer row">
                                                            <div class="step">
                                                                <div class="form-group">
                                                                    <div class="content-header">
                                                                        <h3 class="main_question">
                                                                            <i class="bx bx-right-arrow-alt"></i>
                                                                            {{ $questions[0]['question'] }}
                                                                        </h3>
                                                                        <span class="step-count">
                                                                <span id="quizNumber"
                                                                      class="current-step">{{ $count }}</span>
                                                                /
                                                                <span
                                                                        class="total-step">{{ $que_count }}</span>
                                                            </span>
                                                                    </div>

                                                                    <div class="form-group">
                                                                        <label class="container_radio version_2">
                                                                            <input type="radio"
                                                                                   name="answer[{{ $count }}]" value="true"
                                                                                   class="required">
                                                                            Verdadero
                                                                            <span class="checkmark"></span>
                                                                        </label>
                                                                        <label class="container_radio version_2">
                                                                            <input type="radio"
                                                                                   name="answer[{{ $count }}]"
                                                                                   value="false" class="required">
                                                                            Falso
                                                                            <span class="checkmark"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                @foreach ($questions as $key => $question)

                                                    @if ($key > 0)

                                                        <div style="display: none;" id="more_quiz{{ $key }}">

                                                            <div class="jumbotron" id="quiz{{ $key + 1 }}">
                                                                <div class="middle-answer row">

                                                                    <input type="hidden" id="question_id[{{ $count }}]" name="question_id[{{ $count }}]" value="{{ $question['id'] }}">
                                                                    <input type="hidden" id="canswer[{{ $count }}]" name="canswer[{{ $count }}]" value="{{ $question['answer'] }}">

                                                                    <div class="step">
                                                                        <div class="content-header">
                                                                            <h3 class="main_question">
                                                                                <i class="bx bx-right-arrow-alt"></i>
                                                                                {{ $question['question'] }}
                                                                            </h3>
                                                                            <span class="step-count">
                                                                                <span id="quizNumber"
                                                                                      class="current-step">{{ $count }}</span> /
                                                                                <span class="total-step">{{ $que_count }}</span>
                                                                            </span>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="container_radio version_2">
                                                                                <input type="radio" name="answer[{{ $count }}]"  value="true" class="required">
                                                                                Verdadero
                                                                                <span class="checkmark"></span>
                                                                            </label>
                                                                            <label class="container_radio version_2">
                                                                                <input type="radio" name="answer[{{ $count }}]" value="false" class="required">
                                                                                Falso
                                                                                <span class="checkmark"></span>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    @endif

                                                    @php
                                                        $count++;
                                                    @endphp

                                                @endforeach


                                                <div class="p-4 border-top mt-3  middle-actions">
                                                    <div class="row text-center owl-nav">
                                                            <div class="col-6">
                                                                <a id="prev" class="owl" value="1">
                                                                    <i class="fa-duotone fa-arrow-left"></i>
                                                                </a>
                                                            </div>

                                                        @if ($que_count >= 2)

                                                            <div class="col-6">
                                                                <a id="next" class="owl" value="0">
                                                                    <i class="fa-duotone fa-arrow-right"></i>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if ($count == $que_count)

                                                            <div class="col-12">
                                                            <a id="finish"  class="owl">
                                                                <i class="ti ti-artboard me-1 fs-6"></i>
                                                                Finalizar
                                                            </a>
                                                            </div>
                                                        @endif

                                                    </div>

                                                </div>


                                            </div>

                                        </form>
                                    </div>
                                @endif

                                @if ($topic->type == 1)
                                    <div id="question_block" class="question-block">
                                        <div class="question" id="question-div">
                                            <form action="{{ route('customer.quiz.store', $topic->id) }}" method="POST"
                                                  id="question-form">

                                                {{ csrf_field() }}

                                                @php
                                                    $count = 1;
                                                @endphp

                                                <div class="row justify-content-center">

                                                    <input type="hidden" id="quiz" name="quiz" value="{{ $quiz->id }}">
                                                    <input type="hidden" id="question_id[{{ $count }}]"
                                                           name="question_id[{{ $count }}]" value="{{ $questions[0]['id'] }}">
                                                    <input type="hidden" id="canswer[{{ $count }}]"
                                                           name="canswer[{{ $count }}]" value="{{ $questions[0]['answer'] }}">

                                                    <div id="more_quiz0">

                                                        <div class="jumbotron" id="quiz1">
                                                            <div class="middle-answer row">
                                                                <div class="step">
                                                                    <div class="form-group">

                                                                        <div class="content-header">
                                                                            <h3 class="main_question">
                                                                                <i class="bx bx-right-arrow-alt"></i>
                                                                                {{ $questions[0]['question'] }}
                                                                            </h3>
                                                                            <span class="step-count">
                                                                        <span id="quizNumber"
                                                                              class="current-step">{{ $count }}</span> /
                                                                        <span class="total-step">{{ $que_count }}</span>
                                                                    </span>
                                                                        </div>

                                                                        <div class="form-group examps">
                                                                            <label class="container_radio version_2">
                                                                                <input type="checkbox"
                                                                                       name="answer[{{ $count }}][]" value="a"
                                                                                       class="required">
                                                                                {{ $questions[0]['a'] }}
                                                                                <span class="checkmark"></span>
                                                                            </label>
                                                                            <label class="container_radio version_2">
                                                                                <input type="checkbox"
                                                                                       name="answer[{{ $count }}][]" value="b"
                                                                                       class="required">
                                                                                {{ $questions[0]['b'] }}
                                                                                <span class="checkmark"></span>
                                                                            </label>
                                                                            <label class="container_radio version_2">
                                                                                <input type="checkbox"
                                                                                       name="answer[{{ $count }}][]" value="c"
                                                                                       class="required">
                                                                                {{ $questions[0]['c'] }}
                                                                                <span class="checkmark"></span>
                                                                            </label>
                                                                            <label class="container_radio version_2">
                                                                                <input type="checkbox"
                                                                                       name="answer[{{ $count }}][]" value="d"
                                                                                       class="required">
                                                                                {{ $questions[0]['d'] }}
                                                                                <span class="checkmark"></span>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>


                                                    @foreach ($questions as $key => $question)

                                                        @if ($key > 0)

                                                            <div style="display: none;" id="more_quiz{{ $key }}">

                                                                <div class="jumbotron" id="quiz{{ $key + 1 }}">
                                                                    <div class="middle-answer row">

                                                                        <input type="hidden" id="question_id[{{ $count }}]"
                                                                               name="question_id[{{ $count }}]"
                                                                               value="{{ $question['id'] }}">
                                                                        <input type="hidden" id="canswer[{{ $count }}]"
                                                                               name="canswer[{{ $count }}]"
                                                                               value="{{ $question['answer'] }}">

                                                                        <div class="step">

                                                                            <div class="content-header">
                                                                                <h3 class="main_question">
                                                                                    <i class="bx bx-right-arrow-alt"></i>
                                                                                    {{ $question['question'] }}
                                                                                </h3>
                                                                                <span class="step-count">
                                                                            <span id="quizNumber"
                                                                                  class="current-step">{{ $count }}</span>
                                                                            /
                                                                            <span
                                                                                    class="total-step">{{ $que_count }}</span>
                                                                        </span>
                                                                            </div>

                                                                            <div class="form-group examps">
                                                                                <label class="container_radio version_2">
                                                                                    <input type="checkbox"
                                                                                           name="answer[{{ $count }}][]"
                                                                                           value="a" class="required">
                                                                                    {{ $question['a'] }}
                                                                                    <span class="checkmark"></span>
                                                                                </label>
                                                                                <label class="container_radio version_2">
                                                                                    <input type="checkbox"
                                                                                           name="answer[{{ $count }}][]"
                                                                                           value="b" class="required">
                                                                                    {{ $question['b'] }}
                                                                                    <span class="checkmark"></span>
                                                                                </label>
                                                                                <label class="container_radio version_2">
                                                                                    <input type="checkbox"
                                                                                           name="answer[{{ $count }}][]"
                                                                                           value="c" class="required">
                                                                                    {{ $question['c'] }}
                                                                                    <span class="checkmark"></span>
                                                                                </label>
                                                                                <label class="container_radio version_2">
                                                                                    <input type="checkbox"
                                                                                           name="answer[{{ $count }}][]"
                                                                                           value="d" class="required">
                                                                                    {{ $question['d'] }}
                                                                                    <span class="checkmark"></span>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        @endif

                                                        @php
                                                            $count++;
                                                        @endphp

                                                    @endforeach

                                                    <div class="p-4 border-top mt-3  middle-actions">

                                                        <div class="row text-center owl-nav">

                                                            <div class="col-6">
                                                                <a id="prev" class="owl" value="1">
                                                                    <i class="fa-duotone fa-arrow-left"></i>
                                                                </a>
                                                            </div>

                                                            @if ($que_count >= 2)

                                                                <div class="col-6">
                                                                    <a id="next" class="owl" value="0">
                                                                        <i class="fa-duotone fa-arrow-right"></i>
                                                                    </a>
                                                                </div>
                                                            @endif

                                                            @if ($count == $que_count)

                                                                <div class="col-12">
                                                                    <a id="finish"  class="owl">
                                                                        <i class="ti ti-artboard me-1 fs-6"></i>
                                                                        Finalizar
                                                                    </a>
                                                                </div>
                                                            @endif

                                                        </div>

                                                    </div>


                                                </div>


                                            </form>
                                        </div>
                                    </div>
                                @endif

                            </div>


                        </div>



                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection


        @push('scripts')
            <script type="text/javascript">
                var totalques = 0;


                $(document).ready(function() {

                    totalques = $('.jumbotron').length;

                    var i = 1;
                    var count = 0;

                    $('#next').click(function() {


                        var totalques = $('.jumbotron').length;
                        var type = $('#type').val();
                        var x = $('#next').val();
                        var y = $('#prev').val();

                        if (type == 0) {


                            var numberNotChecked = $('#more_quiz' + count).find('input[type="radio"]:checked')
                                .length;


                            if (numberNotChecked > 0) {


                                i++;
                                x++;

                                $('#prev').show();

                                if (x < totalques) {

                                    var z = x - 1;

                                    $('#more_quiz' + x).show('fast');
                                    $('#more_quiz' + z).hide('fast');
                                    $('#next').val(x);
                                    $('#prev').val(x);


                                    if (i == totalques) {
                                        $('#next').attr('type', 'submit');
                                    }

                                }

                                if (x == totalques) {
                                    $('#question-form').submit();
                                }

                                progres = (x / totalques) * 100;
                                $('#progressbar').css('width', progres + '%');

                                count++;

                            }

                        }

                        if (type == 1) {


                            $('#prev').show();


                            var numberNotChecked = $('#more_quiz' + count).find('input:checkbox:not(":checked")')
                                .length;

                            if (numberNotChecked != 4) {

                                i++;
                                x++;

                                $('#prev').show();

                                if (x < totalques) {

                                    var z = x - 1;

                                    $('#more_quiz' + x).show('fast');
                                    $('#more_quiz' + z).hide('fast');
                                    $('#next').val(x);
                                    $('#prev').val(x);


                                    if (i == totalques) {
                                        $('#next').attr('type', 'submit');
                                    }

                                }

                                if (x == totalques)
                                    $('#question-form').submit();
                            }

                            progres = (x / totalques) * 100;
                            $('#progressbar').css('width', progres + '%');

                            count++;



                        }


                    });

                    $('#prev').click(function() {

                        i--;
                        count--;

                        var totalques = $('.jumbotron').length;
                        var x = $('#next').val();
                        var y = $('#prev').val();

                        $('#next').removeAttr('type');

                        $('#next').show();

                        y--;

                        if (y == 0) {
                            $('#next').val(0);
                            $('#prev').val(1);
                            $('#prev').hide();
                        } else {
                            $('#next').val(y);
                            $('#prev').val(y);
                        }

                        $('#more_quiz' + y).show('fast');
                        $('#more_quiz' + x).hide();


                        progres = (x / totalques) * 100;
                        $('#progressbar').css('width', progres + '%');

                    });


                });
            </script>

    @endpush
