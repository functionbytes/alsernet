@extends('layouts.theme')

@section('title', 'Editar webhook')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar webhook'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="{{ route('pages.webhooks.update', $webhook) }}">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-header p-3 bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">{{ $webhook->name }}</h5>
                                    <p class="small mb-0 text-muted">Editar configuracion del webhook</p>
                                </div>
                                <div class="d-flex align-items-center gap-3 text-muted">
                                    <span title="Éxitos">
                                        <i class="fas fa-check-circle text-success me-1"></i>{{ number_format($webhook->success_count) }}
                                    </span>
                                    <span title="Fallos">
                                        <i class="fas fa-times-circle text-danger me-1"></i>{{ number_format($webhook->fail_count) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            @include('page::pages.webhooks._form', ['webhook' => $webhook])
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-1">
                                Actualizar webhook
                            </button>
                            <a href="{{ route('pages.webhooks.index') }}" class="btn btn-light w-100">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                @include('page::pages.webhooks._sidebar')

                @if($webhook->last_triggered_at)
                    <div class="card mt-3">
                        <div class="card-header p-3 bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">Actividad reciente</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-sm-6 text-muted">Ultimo disparo</dt>
                                <dd class="col-sm-6">{{ $webhook->last_triggered_at->diffForHumans() }}</dd>
                                @if($webhook->last_success_at)
                                    <dt class="col-sm-6 text-muted">Ultimo éxito</dt>
                                    <dd class="col-sm-6 text-success">{{ $webhook->last_success_at->diffForHumans() }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
