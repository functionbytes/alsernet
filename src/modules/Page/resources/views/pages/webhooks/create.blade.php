@extends('layouts.theme')

@section('title', 'Nuevo webhook')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo webhook'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="{{ route('pages.webhooks.store') }}">
                    @csrf

                    <div class="card">
                        <div class="card-header p-3 bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">Nuevo webhook</h5>
                                    <p class="small mb-0 text-muted">Configura una URL que recibirá notificaciones de eventos en páginas</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            @include('page::pages.webhooks._form', ['webhook' => null])
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-1">
                                Guardar webhook
                            </button>
                            <a href="{{ route('pages.webhooks.index') }}" class="btn btn-light w-100">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                @include('page::pages.webhooks._sidebar')
            </div>
        </div>
    </div>
@endsection
