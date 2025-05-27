@extends('layouts.core.backend', [
    'menu' => 'template',
])

@section('title', trans('messages.upload_template'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\TemplateController@index") }}">{{ trans('messages.templates') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">file_upload</span> {{ trans('messages.upload_template') }}</span>
        </h1>
    </div>

@endsection

@section('content')

    <div class="row">
        <div class="col-md-8">
            <p>Seleccione un paquete de plantilla desde su PC para cargarlo. Un paquete de plantilla debe ser un archivo .ZIP que contenga un archivo index.html y otros recursos como CSS, imágenes... Puedes descargarlo y probarlo con un paquete de plantilla de muestra. </p>

            <div class="alert alert-info">
                Es posible que las plantillas cargadas no funcionen bien con el generador de arrastrar y soltar. Siempre se recomienda crear una nueva plantilla basada en una de las increíbles plantillas disponibles, para optimizar la compatibilidad y la eficiencia.
            </div>

            <form enctype="multipart/form-data" action="{{ route('manager.templates.uploadTemplate') }}" method="POST" class="ajax_upload_form form-validate-jquery">

                {{ csrf_field() }}

                <input type="hidden" name="type" value="{{ App\Models\Template\Template::TYPE_EMAIL }}" />

                @include('helpers.form_control', ['required' => true, 'type' => 'text', 'label' => 'Nombre de la plantilla' , 'name' => 'title', 'value' => old('title'), 'rules' => ['title' => 'required']])

                @include('helpers.form_control', ['required' => true, 'type' => 'file', 'label' => 'Elija un archivo para cargar', 'name' => 'file'])

                <hr>

                <div class="">
                    <div class="mt-2">
                        <button class="btn btn-primary bg-grey-600 me-1 mt-4  w-100"  type="submit"  >
                            Cargar
                        </button>
                        <a class="btn btn-secondary bg-grey-600 me-1 mt-1 w-100" href="{{ route('manager.templates') }}">
                            Cancelar
                        </a>
                    </div>
                </div>


            </form>

        </div>
    </div>

@endsection
