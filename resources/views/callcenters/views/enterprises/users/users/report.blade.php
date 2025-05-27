@extends('layouts.callcenters')

@section('content')

    <div class="page-content-wrapper ">

        <div class="content ">


            <div class=" container-fluid   container-fixed-lg">


                <div id="rootwizard" class="m-t-50">
                    <div class="tab-content">

                        <div class="pane padding-20 sm-no-padding">

                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('callcenter.dashboard') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('callcenter.users') }}">Usuarios</a>
                                </li>
                                <li class="breadcrumb-item active">Exportar
                                </li>
                            </ul>



                            <div class="row row-same-height">
                                <div class="col-md-12">
                                    <div class="padding-30 sm-padding-5">

                                        {!! Form::open(['route' => ['enterprises.users.generate'], 'method' => 'POST', 'files' => true, 'enctype' => 'multipart/form-data']) !!}
                                        {{ csrf_field() }}

                                        <input name="enterprise" type="hidden" value="{{ $enterprise->id }}">

                                        <div class="form-group-attached">
                                            <div class="row clearfix">
                                                <div class="col-sm-12">
                                                    <div class="form-group form-group-default disabled">
                                                        <label>EMPRESA</label>
                                                        {!! Form::text('enterprises', $enterprise->title, ['class' => 'form-control' . ($errors->has('firstname') ? ' is-invalid' : ''), 'disabled']) !!}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row clearfix">
                                                <div class="col-sm-12">
                                                    <div
                                                        class="form-group form-group-default form-group-default-select2 required">
                                                        <label>Estado</label>
                                                        {!! Form::select('modalitie', $listmodalities, null, ['class' => 'full-width', 'id' => 'modalitie', 'data-init-plugin' => 'select2', 'required']) !!}
                                                    </div>
                                                    <label id="modalitie-error" class="error d-none" for="modalitie"></label>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row m-t-25">
                                            <div class="col-xl-12">
                                                {!! Form::submit(__('Guardar'), ['class' => 'btn btn-primary pull-right btn-lg btn-block']) !!}
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


@endsection

@push('scripts')

    <script type="text/javascript">

    </script>

@endpush
