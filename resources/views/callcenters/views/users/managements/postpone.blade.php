@extends('layouts.callcenters')

@section('content')

    <div class="page-content-wrapper ">

        <div class="content ">


            <div class=" container-fluid   container-fixed-lg">


                <div id="rootwizard" class="m-t-50">
                    <div class="tab-content">

                        <div class="pane padding-20 sm-no-padding">

                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('callcenter.dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a>Cursos</a></li>
                                <li class="breadcrumb-item"><a>{{ $course->title }}</a></li>
                                <li class="breadcrumb-item active">Ampliar</li>
                            </ul>



                            <div class="row row-same-height">
                                <div class="col-md-12">
                                    <div class="padding-30 sm-padding-5">

                                        {!! Form::open(['route' => ['manager.enterprises.courses.action'], 'method' => 'POST', 'files' => true, 'enctype' => 'multipart/form-data']) !!}
                                        {{ csrf_field() }}



                                        <input name="order" type="hidden" value="{{ $order->uid }}">


                                        <div class="form-group-attached">
                                            <div class="row clearfix">
                                                <div class="col-sm-12">
                                                    <div class="form-group form-group-default disabled">
                                                        <label>Usuario</label>
                                                        {!! Form::text('user', strtoupper($user->firstname) .' '.strtoupper($user->lastname), ['class' => 'form-control' . ($errors->has('user') ? ' is-invalid' : ''), 'disabled']) !!}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row clearfix">
                                                <div class="col-sm-12">
                                                    <div class="form-group form-group-default disabled">
                                                        <label>Curso</label>
                                                        {!! Form::text('course', $course->title, ['class' => 'form-control' . ($errors->has('firstname') ? ' is-invalid' : ''), 'disabled']) !!}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row clearfix">

                                                <div class="col-md-6">
                                                    <div class="form-group form-group-default input-group">
                                                        <div class="form-input-group">
                                                            <label>Fecha Inicial</label>
                                                            <input  name="start" type="text"  value="{{ input_date($order->enroll_start) }}" required
                                                                class="form-control datepicker" data-date-format="dd-mm-yyyy"
                                                                autocomplete="off">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group form-group-default input-group ">
                                                        <div class="form-input-group">
                                                            <label>Fecha Final</label>
                                                            <input  name="expire"
                                                                value="{{ input_date($order->enroll_expire) }}" type="text"
                                                                 class="form-control datepicker"
                                                                data-date-format="dd/mm/yyyy" autocomplete="off">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row m-t-25">
                                            <div class="col-xl-12">
                                                {!! Form::submit(__('Actualizar'), ['class' => 'btn btn-primary pull-right btn-lg btn-block']) !!}
                                            </div>
                                        </div>

                                    </div>


                                    {!! Form::close() !!}

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    </div>

    @include ('distributors.partials.sections.enterprises.wrapper')

@endsection

@push('scripts')

    <script type="text/javascript">
        $('.datepicker').datepicker({
            prevText: '<Ant',
            nextText: 'Sig>',
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre',
                'Octubre', 'Noviembre', 'Diciembre'
            ],
            monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            changeMonth: true,
            changeYear: true,
            format: 'dd-mm-yyyy',
            minDate: '0',
        });
    </script>
@endpush

